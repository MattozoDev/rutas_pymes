<?php

namespace App\Services;

use App\Models\TipoVehiculo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class CapacidadVehiculo
{
    /**
     * @param  iterable<float|int|string|null>  $pesosKg
     */
    public static function evaluar(float|int|string $capacidadKg, iterable $pesosKg): EvaluacionCapacidad
    {
        $carga = 0.0;

        foreach ($pesosKg as $peso) {
            $carga += (float) $peso;
        }

        return new EvaluacionCapacidad(round($carga, 2), round((float) $capacidadKg, 2));
    }

    /**
     * @param  array<string, float|int|string>  $capacidadesPorTipo
     * @return list<string>
     */
    public static function tiposAptos(float|int|string $pesoKg, array $capacidadesPorTipo): array
    {
        asort($capacidadesPorTipo);

        return array_keys(array_filter(
            $capacidadesPorTipo,
            fn (float|int|string $capacidad): bool => (float) $capacidad >= (float) $pesoKg,
        ));
    }

    /**
     * @param  array<string, float|int|string>  $capacidadesPorTipo
     */
    public static function tipoMinimo(float|int|string $pesoKg, array $capacidadesPorTipo): ?string
    {
        return self::tiposAptos($pesoKg, $capacidadesPorTipo)[0] ?? null;
    }

    /**
     * @return Collection<string, TipoVehiculo>
     */
    public static function tiposDeVehiculo(): Collection
    {
        return once(function (): Collection {
            $filas = Cache::store('file')->remember(
                'catalogo.tipos_vehiculo.v2',
                now()->addHours(12),
                fn (): array => TipoVehiculo::query()->orderBy('orden')->toBase()->get()->map(fn (object $fila): array => (array) $fila)->all(),
            );

            return TipoVehiculo::hydrate($filas)->keyBy('codigo');
        });
    }

    /**
     * @return array<string, string>
     */
    public static function opcionesDeTipo(): array
    {
        return self::tiposDeVehiculo()->map(fn (TipoVehiculo $tipo): string => $tipo->nombre)->all();
    }

    public static function nombreDeTipo(?string $codigo): string
    {
        return self::tiposDeVehiculo()->get((string) $codigo)?->nombre ?? ucfirst((string) $codigo);
    }

    public static function capacidadPorDefecto(?string $codigo): ?string
    {
        return self::tiposDeVehiculo()->get((string) $codigo)?->capacidad_kg_defecto;
    }

    public static function recomendacionParaPeso(float|int|string|null $pesoKg): ?string
    {
        if ($pesoKg === null || $pesoKg === '' || ! is_numeric($pesoKg) || (float) $pesoKg <= 0) {
            return null;
        }

        $tipos = self::tiposDeVehiculo();
        $capacidades = $tipos->map(fn (TipoVehiculo $tipo): string => (string) $tipo->capacidad_kg_defecto)->all();
        $aptos = self::tiposAptos($pesoKg, $capacidades);

        if ($aptos === []) {
            return sprintf('Ningún tipo de vehículo soporta %s kg en un solo viaje. Divide el pedido.', self::formatearKg($pesoKg));
        }

        $minimo = $tipos[$aptos[0]];
        $noAptos = $tipos
            ->reject(fn (TipoVehiculo $tipo): bool => in_array($tipo->codigo, $aptos, true))
            ->map(fn (TipoVehiculo $tipo): string => sprintf('%s (%s kg)', $tipo->nombre, self::formatearKg($tipo->capacidad_kg_defecto)))
            ->implode(', ');

        $texto = sprintf('Vehículo mínimo: %s (%s kg).', $minimo->nombre, self::formatearKg($minimo->capacidad_kg_defecto));

        if ($noAptos !== '') {
            $texto .= " No aptos: {$noAptos}.";
        }

        return $texto.' Al asignar se valida la suma de todos los pedidos de la ruta contra la capacidad del vehículo.';
    }

    public static function formatearKg(float|int|string|null $kg): string
    {
        $valor = round((float) $kg, 2);

        return floor($valor) === $valor
            ? number_format($valor, 0, ',', '.')
            : number_format($valor, 2, ',', '.');
    }
}
