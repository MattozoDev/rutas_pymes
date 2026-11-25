<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Services\ComprobanteDigital;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComprobanteController extends Controller
{
    public function __construct(private ComprobanteDigital $comprobanteDigital) {}

    public function show(Comprobante $comprobante): View
    {
        Gate::authorize('view', $comprobante);

        return $this->documento($comprobante->load(ComprobanteDigital::RELACIONES), esVistaPublica: false);
    }

    public function publico(string $comprobante): View
    {
        $registro = Comprobante::query()
            ->withoutGlobalScopes()
            ->with(ComprobanteDigital::RELACIONES)
            ->findOrFail($comprobante);

        return $this->documento($registro, esVistaPublica: true);
    }

    public function evidencia(string $comprobante): StreamedResponse
    {
        $registro = Comprobante::query()->withoutGlobalScopes()->findOrFail($comprobante);

        abort_if($registro->evidencia_path === null || ! Storage::exists($registro->evidencia_path), 404);

        return Storage::response($registro->evidencia_path);
    }

    private function documento(Comprobante $comprobante, bool $esVistaPublica): View
    {
        return view('comprobantes.documento', [
            'comprobante' => $comprobante,
            'esVistaPublica' => $esVistaPublica,
            'urlPublica' => $this->comprobanteDigital->urlPublica($comprobante),
            'urlEvidencia' => $this->comprobanteDigital->urlEvidencia($comprobante),
            'codigoVerificacion' => $this->comprobanteDigital->codigoVerificacion($comprobante),
            'enlaceWhatsapp' => $this->comprobanteDigital->enlaceWhatsapp($comprobante),
        ]);
    }
}
