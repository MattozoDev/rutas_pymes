<?php

namespace App\Providers;

use App\Database\ConexionPostgres;
use Illuminate\Database\Connection;
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

    public function register(): void
    {
        Connection::resolverFor('pgsql', fn ($pdo, string $database, string $prefix, array $config): ConexionPostgres => new ConexionPostgres($pdo, $database, $prefix, $config));
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->environment('production'));

        foreach (self::PARAMETROS_UUID as $parametro) {
            Route::pattern($parametro, '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}');
        }

        Route::pattern('usuario', '[0-9]+');
    }
}
