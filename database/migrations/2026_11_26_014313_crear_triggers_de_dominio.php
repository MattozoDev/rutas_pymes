<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const CON_UPDATED_AT = ['empresas', 'clientes', 'vehiculos', 'repartidores', 'pedidos', 'rutas', 'entregas'];

    /**
     * @var list<string>
     */
    private const AUDITADAS = ['clientes', 'vehiculos', 'repartidores', 'pedidos', 'rutas', 'entregas', 'comprobantes'];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::CON_UPDATED_AT as $tabla) {
            DB::unprepared("create trigger {$tabla}_updated_at before update on public.{$tabla} for each row execute function app.set_updated_at()");
        }

        foreach (self::AUDITADAS as $tabla) {
            DB::unprepared("create trigger {$tabla}_auditoria after insert or update or delete on public.{$tabla} for each row execute function app.auditar()");
        }

        DB::unprepared(<<<'SQL'
            create trigger pedidos_numero before insert on public.pedidos
                for each row execute function app.asignar_numero('P-', 'pedido', '3');

            create trigger comprobantes_numero before insert on public.comprobantes
                for each row execute function app.asignar_numero('C-', 'comprobante', '4');

            create trigger vehiculos_capacidad_defecto before insert on public.vehiculos
                for each row execute function app.vehiculo_capacidad_defecto();

            create trigger rutas_sync_repartidor before insert or update on public.rutas
                for each row execute function app.sincronizar_estado_repartidor();

            create trigger entregas_capacidad before insert or update on public.entregas
                for each row execute function app.validar_capacidad_entrega();

            create trigger entregas_cierre before insert or update on public.entregas
                for each row execute function app.entrega_fecha_cierre();

            create trigger entregas_comprobante after insert or update on public.entregas
                for each row execute function app.generar_comprobante();

            create trigger entregas_sync_pedido after insert or update or delete on public.entregas
                for each row execute function app.sincronizar_estado_pedido();
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $triggers = [
            ...array_map(fn (string $tabla): array => [$tabla, "{$tabla}_updated_at"], self::CON_UPDATED_AT),
            ...array_map(fn (string $tabla): array => [$tabla, "{$tabla}_auditoria"], self::AUDITADAS),
            ['pedidos', 'pedidos_numero'],
            ['comprobantes', 'comprobantes_numero'],
            ['vehiculos', 'vehiculos_capacidad_defecto'],
            ['rutas', 'rutas_sync_repartidor'],
            ['entregas', 'entregas_capacidad'],
            ['entregas', 'entregas_cierre'],
            ['entregas', 'entregas_comprobante'],
            ['entregas', 'entregas_sync_pedido'],
        ];

        foreach ($triggers as [$tabla, $trigger]) {
            DB::unprepared("drop trigger if exists {$trigger} on public.{$tabla}");
        }
    }
};
