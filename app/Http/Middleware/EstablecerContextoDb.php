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
        if ($request->user() !== null && DB::connection()->getDriverName() === 'pgsql') {
            DB::select("select set_config('app.user_id', ?, false)", [(string) $request->user()->getKey()]);
        }

        return $next($request);
    }
}
