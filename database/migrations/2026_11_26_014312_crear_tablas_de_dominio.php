<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const TABLAS = [
        'empresas', 'tipos_vehiculo', 'clientes', 'vehiculos', 'repartidores', 'pedidos',
        'ia_sugerencias_ruta', 'rutas', 'entregas', 'comprobantes', 'contadores', 'auditoria',
        'notificaciones', 'mediciones_planificacion',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            create table public.empresas (
                id uuid primary key default gen_random_uuid(),
                nombre text not null,
                rut text unique,
                email text,
                telefono text,
                direccion text,
                comuna text,
                latitud numeric(9,6),
                longitud numeric(9,6),
                activa boolean not null default true,
                created_at timestamptz not null default now(),
                updated_at timestamptz not null default now()
            );

            alter table public.users
                add column empresa_id uuid references public.empresas (id) on delete restrict,
                add column rol public.rol_usuario not null default 'administrador',
                add column telefono text,
                add column activo boolean not null default true;
            create index users_empresa_idx on public.users (empresa_id);

            create table public.tipos_vehiculo (
                codigo text primary key,
                nombre text not null,
                capacidad_kg_defecto numeric(8,2) not null check (capacidad_kg_defecto > 0),
                bajas_emisiones boolean not null default false,
                orden smallint not null default 0
            );
            insert into public.tipos_vehiculo (codigo, nombre, capacidad_kg_defecto, bajas_emisiones, orden) values
                ('bicicleta', 'Bicicleta', 15, true, 1),
                ('torito', 'Torito', 120, false, 2),
                ('auto', 'Auto', 300, false, 3),
                ('furgon', 'Furgón', 800, false, 4);

            create table public.clientes (
                id uuid primary key default gen_random_uuid(),
                empresa_id uuid not null references public.empresas (id) on delete restrict,
                nombre text not null,
                telefono text,
                email text,
                direccion text,
                comuna text,
                latitud numeric(9,6),
                longitud numeric(9,6),
                notas text,
                activo boolean not null default true,
                created_at timestamptz not null default now(),
                updated_at timestamptz not null default now(),
                unique (id, empresa_id)
            );
            create index clientes_empresa_nombre_idx on public.clientes (empresa_id, nombre);

            create table public.vehiculos (
                id uuid primary key default gen_random_uuid(),
                empresa_id uuid not null references public.empresas (id) on delete restrict,
                tipo text not null references public.tipos_vehiculo (codigo),
                identificador text not null,
                capacidad_kg numeric(8,2) not null check (capacidad_kg > 0),
                activo boolean not null default true,
                created_at timestamptz not null default now(),
                updated_at timestamptz not null default now(),
                unique (empresa_id, identificador),
                unique (id, empresa_id)
            );
            create index vehiculos_empresa_tipo_idx on public.vehiculos (empresa_id, tipo);

            create table public.repartidores (
                id uuid primary key default gen_random_uuid(),
                empresa_id uuid not null references public.empresas (id) on delete restrict,
                user_id bigint unique references public.users (id) on delete set null,
                nombre text not null,
                telefono text,
                vehiculo_id uuid references public.vehiculos (id) on delete set null,
                estado public.estado_repartidor not null default 'disponible',
                created_at timestamptz not null default now(),
                updated_at timestamptz not null default now(),
                unique (id, empresa_id)
            );
            create index repartidores_empresa_estado_idx on public.repartidores (empresa_id, estado);
            create index repartidores_vehiculo_idx on public.repartidores (vehiculo_id);

            create table public.pedidos (
                id uuid primary key default gen_random_uuid(),
                empresa_id uuid not null references public.empresas (id) on delete restrict,
                numero integer,
                codigo text,
                cliente_id uuid not null,
                fecha_entrega date not null default current_date,
                franja_inicio time,
                franja_fin time,
                direccion_entrega text not null,
                comuna text,
                referencia text,
                latitud numeric(9,6),
                longitud numeric(9,6),
                bultos integer not null default 1 check (bultos > 0),
                peso_kg numeric(8,2) not null check (peso_kg > 0),
                tamano public.tamano_carga not null default 'mediano',
                descripcion_carga text,
                observaciones text,
                estado public.estado_pedido not null default 'pendiente',
                creado_por bigint references public.users (id) on delete set null,
                created_at timestamptz not null default now(),
                updated_at timestamptz not null default now(),
                unique (empresa_id, numero),
                unique (id, empresa_id),
                foreign key (cliente_id, empresa_id) references public.clientes (id, empresa_id) on delete restrict,
                check (franja_inicio is null or franja_fin is null or franja_fin > franja_inicio)
            );
            create index pedidos_cliente_idx on public.pedidos (cliente_id);
            create index pedidos_empresa_fecha_estado_idx on public.pedidos (empresa_id, fecha_entrega, estado);
            create index pedidos_empresa_estado_idx on public.pedidos (empresa_id, estado);

            create table public.ia_sugerencias_ruta (
                id uuid primary key default gen_random_uuid(),
                empresa_id uuid not null references public.empresas (id) on delete restrict,
                solicitado_por bigint references public.users (id) on delete set null,
                vehiculo_id uuid references public.vehiculos (id) on delete set null,
                pedidos_ids uuid[] not null default '{}',
                proveedor text,
                modelo text,
                entrada jsonb,
                respuesta jsonb,
                aceptada boolean not null default false,
                latencia_ms integer,
                created_at timestamptz not null default now()
            );

            create table public.rutas (
                id uuid primary key default gen_random_uuid(),
                empresa_id uuid not null references public.empresas (id) on delete restrict,
                repartidor_id uuid not null,
                vehiculo_id uuid not null,
                fecha date not null default current_date,
                estado public.estado_ruta not null default 'planificada',
                origen public.origen_ruta not null default 'manual',
                ia_sugerencia_id uuid references public.ia_sugerencias_ruta (id) on delete set null,
                distancia_km numeric(8,2),
                duracion_min integer,
                trayecto jsonb,
                observaciones text,
                creado_por bigint references public.users (id) on delete set null,
                iniciada_at timestamptz,
                finalizada_at timestamptz,
                created_at timestamptz not null default now(),
                updated_at timestamptz not null default now(),
                unique (id, empresa_id),
                foreign key (repartidor_id, empresa_id) references public.repartidores (id, empresa_id) on delete restrict,
                foreign key (vehiculo_id, empresa_id) references public.vehiculos (id, empresa_id) on delete restrict
            );
            create index rutas_empresa_fecha_idx on public.rutas (empresa_id, fecha, estado);
            create index rutas_repartidor_idx on public.rutas (repartidor_id, fecha);

            create table public.entregas (
                id uuid primary key default gen_random_uuid(),
                empresa_id uuid not null references public.empresas (id) on delete restrict,
                pedido_id uuid not null unique,
                ruta_id uuid,
                repartidor_id uuid not null,
                vehiculo_id uuid not null,
                orden_parada integer,
                estado public.estado_entrega not null default 'asignada',
                observaciones_repartidor text,
                receptor_nombre text,
                motivo_no_entrega text,
                evidencia_path text,
                latitud_confirmacion numeric(9,6),
                longitud_confirmacion numeric(9,6),
                finalizada_at timestamptz,
                created_at timestamptz not null default now(),
                updated_at timestamptz not null default now(),
                unique (id, empresa_id),
                unique (ruta_id, orden_parada),
                foreign key (pedido_id, empresa_id) references public.pedidos (id, empresa_id) on delete restrict,
                foreign key (ruta_id, empresa_id) references public.rutas (id, empresa_id) on delete restrict,
                foreign key (repartidor_id, empresa_id) references public.repartidores (id, empresa_id) on delete restrict,
                foreign key (vehiculo_id, empresa_id) references public.vehiculos (id, empresa_id) on delete restrict
            );
            create index entregas_repartidor_estado_idx on public.entregas (repartidor_id, estado);
            create index entregas_ruta_idx on public.entregas (ruta_id);

            create table public.comprobantes (
                id uuid primary key default gen_random_uuid(),
                empresa_id uuid not null references public.empresas (id) on delete restrict,
                numero integer,
                codigo text,
                entrega_id uuid not null unique,
                pedido_id uuid not null,
                repartidor_id uuid not null,
                resultado public.resultado_entrega not null,
                emitido_at timestamptz not null default now(),
                receptor_nombre text,
                evidencia_path text,
                motivo_no_entrega text,
                observaciones text,
                pdf_path text,
                enviado_cliente_at timestamptz,
                enviado_a text,
                created_at timestamptz not null default now(),
                unique (empresa_id, numero),
                unique (id, empresa_id),
                foreign key (entrega_id, empresa_id) references public.entregas (id, empresa_id) on delete restrict,
                foreign key (pedido_id, empresa_id) references public.pedidos (id, empresa_id) on delete restrict,
                foreign key (repartidor_id, empresa_id) references public.repartidores (id, empresa_id) on delete restrict
            );
            create index comprobantes_empresa_fecha_idx on public.comprobantes (empresa_id, emitido_at);

            create table public.contadores (
                empresa_id uuid not null references public.empresas (id) on delete cascade,
                tipo text not null,
                ultimo integer not null default 0,
                primary key (empresa_id, tipo)
            );

            create table public.auditoria (
                id bigint generated always as identity primary key,
                empresa_id uuid,
                user_id bigint,
                tabla text not null,
                registro_id uuid,
                accion text not null,
                datos_anteriores jsonb,
                datos_nuevos jsonb,
                created_at timestamptz not null default now()
            );
            create index auditoria_empresa_fecha_idx on public.auditoria (empresa_id, created_at);
            create index auditoria_registro_idx on public.auditoria (tabla, registro_id);

            create table public.notificaciones (
                id uuid primary key default gen_random_uuid(),
                empresa_id uuid not null references public.empresas (id) on delete restrict,
                user_id bigint not null references public.users (id) on delete cascade,
                titulo text not null,
                mensaje text,
                enlace text,
                leida boolean not null default false,
                created_at timestamptz not null default now()
            );
            create index notificaciones_usuario_idx on public.notificaciones (user_id, leida);

            create table public.mediciones_planificacion (
                id uuid primary key default gen_random_uuid(),
                empresa_id uuid not null references public.empresas (id) on delete restrict,
                fecha date not null default current_date,
                escenario public.escenario_medicion not null,
                grupo_comparacion text,
                n_pedidos integer not null check (n_pedidos > 0),
                tipo_vehiculo text references public.tipos_vehiculo (codigo),
                tiempo_planificacion_min numeric(8,2) not null check (tiempo_planificacion_min >= 0),
                km_recorridos numeric(8,2) check (km_recorridos >= 0),
                km_motorizados numeric(8,2) check (km_motorizados >= 0),
                notas text,
                registrado_por bigint references public.users (id) on delete set null,
                created_at timestamptz not null default now()
            );
            create index mediciones_empresa_fecha_idx on public.mediciones_planificacion (empresa_id, fecha);
            SQL);

        foreach (self::TABLAS as $tabla) {
            DB::unprepared("alter table public.{$tabla} enable row level security");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_reverse(self::TABLAS) as $tabla) {
            DB::unprepared("drop table if exists public.{$tabla} cascade");
        }

        DB::unprepared(<<<'SQL'
            alter table public.users
                drop column if exists empresa_id,
                drop column if exists rol,
                drop column if exists telefono,
                drop column if exists activo;
            SQL);
    }
};
