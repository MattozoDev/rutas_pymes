<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportadorCsv
{
    /**
     * @param  list<string>  $encabezados
     * @param  iterable<int, list<string|int|float|null>>  $filas
     */
    public static function descargar(string $nombreArchivo, array $encabezados, iterable $filas): StreamedResponse
    {
        return response()->streamDownload(function () use ($encabezados, $filas): void {
            $salida = fopen('php://output', 'w');

            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, $encabezados, ';');

            foreach ($filas as $fila) {
                fputcsv($salida, array_map(self::celdaSegura(...), $fila), ';');
            }

            fclose($salida);
        }, $nombreArchivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function celdaSegura(string|int|float|null $valor): string
    {
        $texto = (string) $valor;

        return $texto !== '' && in_array($texto[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'".$texto
            : $texto;
    }

    public static function numero(float|int|string|null $valor): string
    {
        return $valor === null || $valor === '' ? '' : number_format((float) $valor, 2, ',', '');
    }
}
