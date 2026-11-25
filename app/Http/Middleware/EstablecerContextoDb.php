<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EstablecerContextoDb
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $conexion = DB::connection();

        if ($request->user() === null || $conexion->getDriverName() !== 'pgsql') {
            return $next($request);
        }

        $usuarioId = (string) $request->user()->getKey();
        $establecido = false;

        $conexion->beforeExecuting(function (string $consulta) use ($conexion, $usuarioId, &$establecido): void {
            if ($establecido || ! preg_match('/^\s*(insert|update|delete)\b/i', $consulta)) {
                return;
            }

            $establecido = true;
            $conexion->select("select set_config('app.user_id', ?, false)", [$usuarioId]);
        });

        return $next($request);
    }
}
