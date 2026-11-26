<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, list<string>>
     */
    private const ENUMS = [
        'escenario_medicion' => ['manual', 'sistema'],
        'estado_entrega' => ['asignada', 'en_camino', 'entregada', 'no_entregada'],
        'estado_pedido' => ['pendiente', 'asignado', 'en_reparto', 'entregado', 'no_entregado', 'cancelado'],
        'estado_repartidor' => ['disponible', 'en_ruta', 'inactivo'],
        'estado_ruta' => ['borrador', 'planificada', 'en_curso', 'completada', 'cancelada'],
        'origen_ruta' => ['manual', 'ia'],
        'resultado_entrega' => ['entregado', 'no_entregado'],
        'rol_usuario' => ['administrador', 'repartidor'],
        'tamano_carga' => ['pequeno', 'mediano', 'grande'],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared('create schema if not exists app');

        foreach (self::ENUMS as $tipo => $valores) {
            $lista = implode(', ', array_map(fn (string $valor): string => "''{$valor}''", $valores));

            DB::unprepared(<<<SQL
                do $$ begin
                    if not exists (
                        select 1 from pg_type t join pg_namespace n on n.oid = t.typnamespace
                        where t.typname = '{$tipo}' and n.nspname = 'public'
                    ) then
                        execute 'create type public.{$tipo} as enum ({$lista})';
                    end if;
                end $$;
                SQL);
        }

        DB::unprepared(<<<'SQL'
            create or replace function app.usuario_actual()
             returns bigint
             language sql
             stable
            as $function$
              select nullif(current_setting('app.user_id', true), '')::bigint
            $function$;

            create or replace function app.set_updated_at()
             returns trigger
             language plpgsql
            as $function$
            begin new.updated_at := now(); return new; end $function$;

            create or replace function app.asignar_numero()
             returns trigger
             language plpgsql
            as $function$
            declare v_prefijo text := tg_argv[0]; v_tipo text := tg_argv[1]; v_ancho int := tg_argv[2]::int; v_num int;
            begin
              if new.numero is null then
                insert into public.contadores as c (empresa_id, tipo, ultimo) values (new.empresa_id, v_tipo, 1)
                on conflict (empresa_id, tipo) do update set ultimo = c.ultimo + 1
                returning ultimo into v_num;
                new.numero := v_num;
              end if;
              new.codigo := v_prefijo || lpad(new.numero::text, v_ancho, '0');
              return new;
            end $function$;

            create or replace function app.auditar()
             returns trigger
             language plpgsql
            as $function$
            declare v_old jsonb; v_new jsonb; v_row jsonb;
            begin
              if tg_op in ('UPDATE','DELETE') then v_old := to_jsonb(old); end if;
              if tg_op in ('INSERT','UPDATE') then v_new := to_jsonb(new); end if;
              v_row := coalesce(v_new, v_old);
              insert into public.auditoria (empresa_id, user_id, tabla, registro_id, accion, datos_anteriores, datos_nuevos)
              values ((v_row->>'empresa_id')::uuid, app.usuario_actual(), tg_table_name, (v_row->>'id')::uuid, tg_op, v_old, v_new);
              return null;
            end $function$;

            create or replace function app.entrega_fecha_cierre()
             returns trigger
             language plpgsql
            as $function$
            begin
              if new.estado in ('entregada','no_entregada') and new.finalizada_at is null then new.finalizada_at := now();
              elsif new.estado in ('asignada','en_camino') then new.finalizada_at := null; end if;
              return new;
            end $function$;

            create or replace function app.generar_comprobante()
             returns trigger
             language plpgsql
            as $function$
            begin
              if new.estado in ('entregada','no_entregada') and (tg_op = 'INSERT' or old.estado is distinct from new.estado) then
                insert into public.comprobantes (empresa_id, entrega_id, pedido_id, repartidor_id, resultado,
                                                 emitido_at, receptor_nombre, evidencia_path, motivo_no_entrega)
                values (new.empresa_id, new.id, new.pedido_id, new.repartidor_id,
                        case new.estado when 'entregada' then 'entregado'::public.resultado_entrega
                                        else 'no_entregado'::public.resultado_entrega end,
                        coalesce(new.finalizada_at, now()), new.receptor_nombre, new.evidencia_path, new.motivo_no_entrega)
                on conflict (entrega_id) do nothing;
              end if;
              return new;
            end $function$;

            create or replace function app.sincronizar_estado_pedido()
             returns trigger
             language plpgsql
            as $function$
            begin
              if tg_op = 'DELETE' then
                update public.pedidos set estado = 'pendiente' where id = old.pedido_id and estado in ('asignado','en_reparto');
                return old;
              end if;
              update public.pedidos set estado = case new.estado
                  when 'asignada'     then 'asignado'::public.estado_pedido
                  when 'en_camino'    then 'en_reparto'::public.estado_pedido
                  when 'entregada'    then 'entregado'::public.estado_pedido
                  when 'no_entregada' then 'no_entregado'::public.estado_pedido end
              where id = new.pedido_id;
              return new;
            end $function$;

            create or replace function app.sincronizar_estado_repartidor()
             returns trigger
             language plpgsql
            as $function$
            begin
              if new.estado = 'en_curso' then
                update public.repartidores set estado = 'en_ruta' where id = new.repartidor_id and estado <> 'inactivo';
                new.iniciada_at := coalesce(new.iniciada_at, now());
              elsif new.estado in ('completada','cancelada') then
                update public.repartidores set estado = 'disponible' where id = new.repartidor_id and estado = 'en_ruta';
                if new.estado = 'completada' then new.finalizada_at := coalesce(new.finalizada_at, now()); end if;
              end if;
              return new;
            end $function$;

            create or replace function app.validar_capacidad_entrega()
             returns trigger
             language plpgsql
            as $function$
            declare v_cap numeric; v_peso numeric; v_otros numeric := 0;
            begin
              select capacidad_kg into v_cap  from public.vehiculos where id = new.vehiculo_id;
              select peso_kg      into v_peso from public.pedidos   where id = new.pedido_id;
              if new.ruta_id is not null then
                select coalesce(sum(p.peso_kg), 0) into v_otros
                from public.entregas e join public.pedidos p on p.id = e.pedido_id
                where e.ruta_id = new.ruta_id and e.id <> new.id;
              end if;
              if v_peso + v_otros > v_cap then
                raise exception 'Capacidad insuficiente: % kg exceden los % kg del vehículo', v_peso + v_otros, v_cap
                  using errcode = 'check_violation';
              end if;
              return new;
            end $function$;

            create or replace function app.vehiculo_capacidad_defecto()
             returns trigger
             language plpgsql
            as $function$
            begin
              if new.capacidad_kg is null then
                select capacidad_kg_defecto into new.capacidad_kg from public.tipos_vehiculo where codigo = new.tipo;
              end if;
              return new;
            end $function$;
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared('drop schema if exists app cascade');

        foreach (array_keys(self::ENUMS) as $tipo) {
            DB::unprepared("drop type if exists public.{$tipo} cascade");
        }
    }
};
