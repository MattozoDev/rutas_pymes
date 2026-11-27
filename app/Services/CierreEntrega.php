<?php

namespace App\Services;

use App\Enums\EstadoEntrega;
use App\Enums\EstadoRuta;
use App\Models\Comprobante;
use App\Models\Entrega;
use App\Models\Ruta;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CierreEntrega
{
    public function __construct(private ComprobanteDigital $comprobanteDigital) {}

    public function marcarEnCamino(Entrega $entrega): void
    {
        DB::transaction(function () use ($entrega): void {
            $this->iniciarRutaSiCorresponde($entrega);

            if ($entrega->estado === EstadoEntrega::Asignada) {
                $entrega->update(['estado' => EstadoEntrega::EnCamino]);
            }
        });
    }

    /**
     * @param  array{estado: string, receptor_nombre?: string|null, motivo_no_entrega?: string|null, observaciones_repartidor?: string|null, evidencia_path?: string|null, latitud_confirmacion?: float|null, longitud_confirmacion?: float|null}  $datos
     */
    public function cerrar(Entrega $entrega, array $datos): ?Comprobante
    {
        $rutaCompletada = null;

        $comprobante = DB::transaction(function () use ($entrega, $datos, &$rutaCompletada): ?Comprobante {
            $this->iniciarRutaSiCorresponde($entrega);

            $entrega->update(Arr::only($datos, [
                'estado', 'receptor_nombre', 'motivo_no_entrega', 'observaciones_repartidor',
                'evidencia_path', 'latitud_confirmacion', 'longitud_confirmacion',
            ]));

            $rutaCompletada = $this->completarRutaSiTermino($entrega);

            return Comprobante::query()->where('entrega_id', $entrega->id)->first();
        });

        if ($comprobante !== null) {
            try {
                $this->comprobanteDigital->enviarAlCliente($comprobante->load(ComprobanteDigital::RELACIONES));
            } catch (Throwable $excepcion) {
                report($excepcion);
            }
        }

        Avisos::entregaCerrada($entrega, $comprobante);

        if ($rutaCompletada !== null) {
            Avisos::rutaFinalizada($rutaCompletada);
        }

        return $comprobante;
    }

    private function iniciarRutaSiCorresponde(Entrega $entrega): void
    {
        $ruta = $entrega->ruta()->first();

        if ($ruta !== null && $ruta->estado->puedeCambiarA(EstadoRuta::EnCurso)) {
            $ruta->estado = EstadoRuta::EnCurso;
            $ruta->save();
        }
    }

    private function completarRutaSiTermino(Entrega $entrega): ?Ruta
    {
        $ruta = $entrega->ruta()->first();

        if ($ruta === null || $ruta->estado !== EstadoRuta::EnCurso) {
            return null;
        }

        $quedanAbiertas = $ruta->entregas()
            ->whereIn('estado', [EstadoEntrega::Asignada, EstadoEntrega::EnCamino])
            ->exists();

        if ($quedanAbiertas) {
            return null;
        }

        $ruta->estado = EstadoRuta::Completada;
        $ruta->save();

        return $ruta;
    }
}
