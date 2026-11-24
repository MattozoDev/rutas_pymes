<?php

use App\Http\Controllers\Auth\RegistroController;
use App\Http\Controllers\Auth\SesionController;
use App\Http\Controllers\CierreEntregaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\EstadoRutaController;
use App\Http\Controllers\EvidenciaEntregaController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\MedicionPlanificacionController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\RepartidorController;
use App\Http\Controllers\RutaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehiculoController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [SesionController::class, 'create'])->name('login');
    Route::post('/login', [SesionController::class, 'store'])->middleware('throttle:6,1')->name('login.store');

    Route::get('/registro', [RegistroController::class, 'create'])->name('registro');
    Route::post('/registro', [RegistroController::class, 'store'])->middleware('throttle:6,1')->name('registro.store');
});

Route::middleware(['auth', 'activo'])->group(function () {
    Route::post('/logout', [SesionController::class, 'destroy'])->name('logout');
    Route::get('/', InicioController::class)->name('inicio');

    Route::get('/rutas/{ruta}', [RutaController::class, 'show'])->name('rutas.show');
    Route::patch('/rutas/{ruta}/estado', [EstadoRutaController::class, 'update'])->name('rutas.estado.update');
    Route::get('/entregas/{entrega}', [EntregaController::class, 'show'])->name('entregas.show');
    Route::get('/entregas/{entrega}/evidencia', EvidenciaEntregaController::class)->name('entregas.evidencia');

    Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::patch('/notificaciones/{notificacion}', [NotificacionController::class, 'update'])->name('notificaciones.update');
    Route::post('/notificaciones/leidas', [NotificacionController::class, 'marcarTodasLeidas'])->name('notificaciones.leidas');

    Route::middleware('rol:repartidor')->group(function () {
        Route::get('/mis-entregas', [EntregaController::class, 'index'])->name('entregas.index');
        Route::patch('/entregas/{entrega}/en-camino', [EntregaController::class, 'update'])->name('entregas.update');
        Route::post('/entregas/{entrega}/cierre', [CierreEntregaController::class, 'store'])->name('entregas.cierre.store');
    });

    Route::middleware('rol:administrador')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/empresa', [EmpresaController::class, 'edit'])->name('empresa.edit');
        Route::put('/empresa', [EmpresaController::class, 'update'])->name('empresa.update');

        Route::resource('usuarios', UserController::class)
            ->except('show')
            ->parameters(['usuarios' => 'usuario']);

        Route::resource('clientes', ClienteController::class)
            ->parameters(['clientes' => 'cliente']);

        Route::resource('vehiculos', VehiculoController::class)
            ->except('show')
            ->parameters(['vehiculos' => 'vehiculo']);

        Route::resource('repartidores', RepartidorController::class)
            ->parameters(['repartidores' => 'repartidor']);

        Route::resource('pedidos', PedidoController::class)
            ->parameters(['pedidos' => 'pedido']);

        Route::resource('rutas', RutaController::class)
            ->except('show')
            ->parameters(['rutas' => 'ruta']);

        Route::resource('comprobantes', ComprobanteController::class)
            ->only(['index', 'show'])
            ->parameters(['comprobantes' => 'comprobante']);

        Route::resource('mediciones', MedicionPlanificacionController::class)
            ->only(['index', 'create', 'store', 'destroy'])
            ->parameters(['mediciones' => 'medicion']);
    });
});
