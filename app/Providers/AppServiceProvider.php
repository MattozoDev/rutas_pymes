<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @var list<string>
     */
    private const PARAMETROS_UUID = [
        'cliente', 'vehiculo', 'repartidor', 'pedido', 'ruta', 'entrega',
        'comprobante', 'medicion', 'notificacion',
    ];

    public function register(): void {}

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->environment('production'));

        foreach (self::PARAMETROS_UUID as $parametro) {
            Route::pattern($parametro, '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}');
        }

        Route::pattern('usuario', '[0-9]+');
    }
}
