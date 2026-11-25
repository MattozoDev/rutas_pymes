<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SoloAdministradores;
use App\Filament\Resources\Pedidos\PedidoResource;
use App\Filament\Widgets\PedidosDeHoy;
use App\Filament\Widgets\RepartidoresDeHoy;
use App\Filament\Widgets\ResumenDelDia;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;
use Filament\Support\Components\Attributes\Unexposed;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\Exceptions\HttpResponseException;

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

    public function getSubheading(): string
    {
        return ucfirst(today(config('app.zona_horaria_negocio'))->locale('es')->isoFormat('dddd D [de] MMMM'));
    }

    public function getWidgets(): array
    {
        return [
            ResumenDelDia::class,
            PedidosDeHoy::class,
            RepartidoresDeHoy::class,
        ];
    }

    public function getColumns(): int
    {
        return 3;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nuevoPedido')
                ->label('Nuevo pedido')
                ->icon(Heroicon::Plus)
                ->url(PedidoResource::getUrl('create')),
        ];
    }
}
