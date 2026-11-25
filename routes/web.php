<?php

use App\Http\Controllers\ComprobanteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('/repartidor', fn () => redirect()->route('filament.admin.auth.login', ['modo' => 'repartidor']))
    ->name('repartidor.ingreso');

Route::middleware(['auth'])->group(function () {
    Route::get('/comprobantes/{comprobante}/documento', [ComprobanteController::class, 'show'])
        ->name('comprobantes.show');
});

Route::middleware(['signed', 'throttle:60,1'])->group(function () {
    Route::get('/c/{comprobante}', [ComprobanteController::class, 'publico'])
        ->name('comprobantes.publico');
    Route::get('/c/{comprobante}/evidencia', [ComprobanteController::class, 'evidencia'])
        ->name('comprobantes.evidencia');
});
