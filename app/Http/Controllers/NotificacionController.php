<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request): void {}

    public function update(Request $request, Notificacion $notificacion): RedirectResponse
    {
        abort_unless($notificacion->user_id === $request->user()->id, 403);

        $notificacion->update(['leida' => true]);

        $enlaceInterno = $notificacion->enlace !== null
            && str_starts_with($notificacion->enlace, '/')
            && ! str_starts_with($notificacion->enlace, '//');

        return $enlaceInterno ? redirect($notificacion->enlace) : back();
    }

    public function marcarTodasLeidas(Request $request): RedirectResponse
    {
        $request->user()->notificaciones()->noLeidas()->update(['leida' => true]);

        return back()->with('status', 'Notificaciones marcadas como leídas.');
    }
}
