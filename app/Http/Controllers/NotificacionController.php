<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request): void {}

    public function update(Request $request, Notificacion $notificacion): void
    {
        abort_unless($notificacion->user_id === $request->user()->id, 403);

        $notificacion->update(['leida' => true]);
    }

    public function marcarTodasLeidas(Request $request): void
    {
        $request->user()->notificaciones()->noLeidas()->update(['leida' => true]);
    }
}
