<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SoloAdministradores;
use App\Filament\Resources\Pedidos\PedidoResource;
use App\Filament\Resources\Rutas\RutaResource;
use App\Filament\Widgets\EquipoDeHoy;
use App\Filament\Widgets\PorAsignar;
use App\Filament\Widgets\ResumenDelDia;
use App\Filament\Widgets\UltimasEntregas;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;
use Filament\Support\Components\Attributes\Unexposed;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

class Inicio extends Dashboard
{
    use SoloAdministradores;

    protected static ?string $title = 'Panel';

    protected static ?string $navigationLabel = 'Panel';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    #[Unexposed]
    public function mountCanAuthorizeAccess(): void
    {
        if (MisEntregas::canAccess()) {
            throw new HttpResponseException(redirect(MisEntregas::getUrl()));
        }

        parent::mountCanAuthorizeAccess();
    }

    public function getHeading(): string|Htmlable
    {
        $hora = (int) now(config('app.zona_horaria_negocio'))->format('G');
        $saludo = match (true) {
            $hora < 12 => 'Buenos días',
            $hora < 20 => 'Buenas tardes',
            default => 'Buenas noches',
        };

        return $saludo.', '.Str::before((string) auth()->user()?->name, ' ');
    }

    public function getSubheading(): string
    {
        $empresa = auth()->user()?->empresa?->nombre;

        return ucfirst(today(config('app.zona_horaria_negocio'))->locale('es')->isoFormat('dddd D [de] MMMM'))
            .($empresa ? ' · '.$empresa : '');
    }

    public function getWidgets(): array
    {
        return [
            ResumenDelDia::class,
            PorAsignar::class,
            EquipoDeHoy::class,
            UltimasEntregas::class,
        ];
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'lg' => 3];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('asignarEntrega')
                ->label('Asignar entrega')
                ->icon(Heroicon::OutlinedMap)
                ->color('gray')
                ->url(RutaResource::getUrl('create')),
            Action::make('nuevoPedido')
                ->label('Nuevo pedido')
                ->icon(Heroicon::Plus)
                ->url(PedidoResource::getUrl('create')),
        ];
    }
}
