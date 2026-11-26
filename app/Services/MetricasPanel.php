<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class MetricasPanel
{
    /**
     * @return array{hoy_total: int, hoy_entregados: int, en_reparto: int, pendientes: int, peso_pendiente: float, atrasados: int, en_ruta: int, semana: list<array{dia: string, total: int, entregados: int, no_entregados: int}>}
     */
    public static function obtener(): array
    {
        return once(function (): array {
            $empresa = (string) auth()->user()->empresa_id;
            $hoy = today(config('app.zona_horaria_negocio'))->toDateString();

            $fila = DB::selectOne(<<<'SQL'
                with dias as (
                    select generate_series(cast(? as date) - 6, cast(? as date), interval '1 day')::date as dia
                ),
                semana as (
                    select fecha_entrega as dia,
                        count(*) as total,
                        count(*) filter (where estado = 'entregado') as entregados,
                        count(*) filter (where estado = 'no_entregado') as no_entregados
                    from pedidos
                    where empresa_id = ? and fecha_entrega between cast(? as date) - 6 and cast(? as date)
                    group by fecha_entrega
                )
                select
                    (select count(*) from pedidos where empresa_id = ? and fecha_entrega = cast(? as date)) as hoy_total,
                    (select count(*) from pedidos where empresa_id = ? and fecha_entrega = cast(? as date) and estado = 'entregado') as hoy_entregados,
                    (select count(*) from pedidos where empresa_id = ? and estado = 'en_reparto') as en_reparto,
                    (select count(*) from pedidos where empresa_id = ? and estado = 'pendiente') as pendientes,
                    (select coalesce(sum(peso_kg), 0) from pedidos where empresa_id = ? and estado = 'pendiente') as peso_pendiente,
                    (select count(*) from pedidos where empresa_id = ? and estado = 'pendiente' and fecha_entrega < cast(? as date)) as atrasados,
                    (select count(*) from repartidores where empresa_id = ? and estado = 'en_ruta') as en_ruta,
                    (select json_agg(json_build_object(
                        'dia', d.dia,
                        'total', coalesce(s.total, 0),
                        'entregados', coalesce(s.entregados, 0),
                        'no_entregados', coalesce(s.no_entregados, 0)
                    ) order by d.dia) from dias d left join semana s on s.dia = d.dia) as semana
                SQL, [
                $hoy, $hoy,
                $empresa, $hoy, $hoy,
                $empresa, $hoy,
                $empresa, $hoy,
                $empresa,
                $empresa,
                $empresa,
                $empresa, $hoy,
                $empresa,
            ]);

            return [
                'hoy_total' => (int) $fila->hoy_total,
                'hoy_entregados' => (int) $fila->hoy_entregados,
                'en_reparto' => (int) $fila->en_reparto,
                'pendientes' => (int) $fila->pendientes,
                'peso_pendiente' => (float) $fila->peso_pendiente,
                'atrasados' => (int) $fila->atrasados,
                'en_ruta' => (int) $fila->en_ruta,
                'semana' => array_map(fn (array $dia): array => [
                    'dia' => (string) $dia['dia'],
                    'total' => (int) $dia['total'],
                    'entregados' => (int) $dia['entregados'],
                    'no_entregados' => (int) $dia['no_entregados'],
                ], json_decode((string) $fila->semana, true) ?? []),
            ];
        });
    }
}
