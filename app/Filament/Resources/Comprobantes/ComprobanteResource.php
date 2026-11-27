<?php

namespace App\Filament\Resources\Comprobantes;

use App\Filament\Concerns\SoloAdministradores;
use App\Filament\Resources\Comprobantes\Pages\ListComprobantes;
use App\Filament\Resources\Comprobantes\Pages\ViewComprobante;
use App\Filament\Resources\Comprobantes\Schemas\ComprobanteInfolist;
use App\Filament\Resources\Comprobantes\Tables\ComprobantesTable;
use App\Models\Comprobante;
use App\Services\ComprobanteDigital;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ComprobanteResource extends Resource
{
    use SoloAdministradores;

    protected static ?string $model = Comprobante::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|UnitEnum|null $navigationGroup = 'Registros';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'comprobante';

    protected static ?string $pluralModelLabel = 'comprobantes';

    protected static ?string $recordTitleAttribute = 'codigo';

    /**
     * @return Builder<Comprobante>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(ComprobanteDigital::RELACIONES);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return ComprobanteInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ComprobantesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComprobantes::route('/'),
            'view' => ViewComprobante::route('/{record}'),
        ];
    }

    public static function accionDocumento(): Action
    {
        return Action::make('documento')
            ->label('Descargar PDF')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->url(fn (Comprobante $record): string => route('comprobantes.show', $record), shouldOpenInNewTab: true);
    }

    public static function accionEnviar(): Action
    {
        return Action::make('enviar')
            ->label(fn (Comprobante $record): string => $record->enviado_cliente_at ? 'Reenviar al cliente' : 'Enviar al cliente')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('gray')
            ->modalHeading('Enviar comprobante por correo')
            ->modalSubmitActionLabel('Enviar')
            ->fillForm(fn (Comprobante $record): array => ['correo' => $record->enviado_a ?? $record->pedido?->cliente?->email])
            ->schema([
                TextInput::make('correo')->label('Correo del cliente')->email()->required(),
            ])
            ->action(function (Comprobante $record, array $data): void {
                app(ComprobanteDigital::class)->enviarAlCliente($record, $data['correo']);

                Notification::make()->success()->title('Comprobante enviado a '.$data['correo'])->send();
            });
    }

    public static function accionWhatsapp(): Action
    {
        return Action::make('whatsapp')
            ->label('Compartir por WhatsApp')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('success')
            ->url(fn (Comprobante $record): string => app(ComprobanteDigital::class)->enlaceWhatsapp($record), shouldOpenInNewTab: true);
    }
}
