<?php

namespace App\Filament\Pages;

use App\Enums\EstadoEntrega;
use App\Models\Entrega;
use App\Models\Repartidor;
use App\Services\CierreEntrega;
use App\Services\ComprobanteDigital;
use App\Services\PlanificadorRuta;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Components\Attributes\Unexposed;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;

class MisEntregas extends Page
{
    protected string $view = 'filament.pages.mis-entregas';

    protected static ?string $title = 'Mis entregas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?int $navigationSort = -1;

    public static function canAccess(): bool
    {
        $usuario = auth()->user();

        return $usuario !== null && $usuario->esRepartidor() && $usuario->repartidor !== null;
    }

    #[Unexposed]
    public function mountCanAuthorizeAccess(): void
    {
        if (Inicio::canAccess()) {
            throw new HttpResponseException(redirect(Inicio::getUrl()));
        }

        parent::mountCanAuthorizeAccess();
    }

    public function getSubheading(): string
    {
        return ucfirst(today(config('app.zona_horaria_negocio'))->locale('es')->isoFormat('dddd D/MM')).' · '.$this->repartidor?->nombre;
    }

    #[Computed]
    public function repartidor(): ?Repartidor
    {
        return Repartidor::query()
            ->with('vehiculo')
            ->where('user_id', auth()->id())
            ->first();
    }

    /**
     * @return Collection<int, Entrega>
     */
    #[Computed]
    public function entregas(): Collection
    {
        $hoy = today(config('app.zona_horaria_negocio'))->toDateString();

        return Entrega::query()
            ->with(['pedido.cliente', 'ruta', 'comprobante', 'vehiculo'])
            ->where('repartidor_id', $this->repartidor?->id)
            ->where(fn (Builder $query) => $query
                ->whereHas('ruta', fn (Builder $ruta) => $ruta->where('fecha', $hoy))
                ->orWhereIn('estado', [EstadoEntrega::Asignada, EstadoEntrega::EnCamino]))
            ->get()
            ->sortBy(fn (Entrega $entrega): string => sprintf('%s-%04d', $entrega->ruta?->fecha?->toDateString(), $entrega->orden_parada ?? 9999))
            ->values();
    }

    public function urlMapa(): ?string
    {
        $destinos = $this->entregas
            ->reject(fn (Entrega $entrega): bool => $entrega->estaFinalizada())
            ->map(fn (Entrega $entrega): string => $entrega->pedido->latitud !== null
                ? $entrega->pedido->latitud.','.$entrega->pedido->longitud
                : trim($entrega->pedido->direccion_entrega.', '.$entrega->pedido->comuna.', Chile', ', '))
            ->values()
            ->all();

        return PlanificadorRuta::urlGoogleMaps($destinos, null, $this->repartidor?->vehiculo?->tipo);
    }

    public function enCaminoAction(): Action
    {
        return Action::make('enCamino')
            ->label('Voy en camino')
            ->icon(Heroicon::OutlinedTruck)
            ->color('gray')
            ->action(function (array $arguments): void {
                app(CierreEntrega::class)->marcarEnCamino($this->entregaPropia($arguments['entrega']));

                unset($this->entregas);
            });
    }

    public function confirmarAction(): Action
    {
        return Action::make('confirmar')
            ->label('Llegué · Confirmar entrega')
            ->icon(Heroicon::OutlinedMapPin)
            ->size('lg')
            ->modalHeading('Confirmar entrega')
            ->modalDescription(function (array $arguments): string {
                $pedido = $this->entregaPropia($arguments['entrega'])->pedido;

                return sprintf('Pedido %s · %s · %s · %s', $pedido->codigo, $pedido->cliente?->nombre, $pedido->direccion_entrega, $pedido->resumenCarga());
            })
            ->modalSubmitActionLabel('Confirmar entrega')
            ->schema([
                ToggleButtons::make('estado')
                    ->label('Estado de la entrega')
                    ->options([
                        EstadoEntrega::Entregada->value => 'Entregado',
                        EstadoEntrega::NoEntregada->value => 'No entregado',
                    ])
                    ->icons([
                        EstadoEntrega::Entregada->value => Heroicon::OutlinedCheckCircle,
                        EstadoEntrega::NoEntregada->value => Heroicon::OutlinedXCircle,
                    ])
                    ->colors([
                        EstadoEntrega::Entregada->value => 'success',
                        EstadoEntrega::NoEntregada->value => 'danger',
                    ])
                    ->default(EstadoEntrega::Entregada->value)
                    ->inline()
                    ->required()
                    ->live(),
                TextInput::make('receptor_nombre')
                    ->label('Nombre de quien recibe')
                    ->placeholder('Ej: Ana Pérez')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => $get('estado') === EstadoEntrega::Entregada->value)
                    ->required(fn (Get $get): bool => $get('estado') === EstadoEntrega::Entregada->value),
                Textarea::make('motivo_no_entrega')
                    ->label('Motivo')
                    ->placeholder('Ej: local cerrado, cliente no contesta')
                    ->maxLength(1000)
                    ->visible(fn (Get $get): bool => $get('estado') === EstadoEntrega::NoEntregada->value)
                    ->required(fn (Get $get): bool => $get('estado') === EstadoEntrega::NoEntregada->value),
                FileUpload::make('evidencia')
                    ->label('Evidencia')
                    ->helperText('Arrastra tu archivo o toma una foto.')
                    ->image()
                    ->disk('local')
                    ->directory(fn (): string => 'evidencias/'.auth()->user()->empresa_id)
                    ->visibility('private')
                    ->automaticallyResizeImagesMode('contain')
                    ->automaticallyResizeImagesToWidth('1600')
                    ->automaticallyResizeImagesToHeight('1600')
                    ->maxSize(5120)
                    ->extraInputAttributes(['capture' => 'environment']),
                Textarea::make('observaciones_repartidor')
                    ->label('Observaciones')
                    ->maxLength(2000)
                    ->rows(2),
                Text::make('Fecha y hora: se registra al confirmar.'),
            ])
            ->action(function (array $data, array $arguments): void {
                $comprobante = app(CierreEntrega::class)->cerrar($this->entregaPropia($arguments['entrega']), [
                    'estado' => $data['estado'],
                    'receptor_nombre' => $data['receptor_nombre'] ?? null,
                    'motivo_no_entrega' => $data['motivo_no_entrega'] ?? null,
                    'observaciones_repartidor' => $data['observaciones_repartidor'] ?? null,
                    'evidencia_path' => $data['evidencia'] ?? null,
                ]);

                unset($this->entregas);

                $notificacion = Notification::make()
                    ->success()
                    ->title('Entrega registrada')
                    ->persistent();

                if ($comprobante !== null) {
                    $comprobante->load(ComprobanteDigital::RELACIONES);

                    $notificacion
                        ->body(sprintf(
                            'Comprobante %s emitido%s.',
                            $comprobante->codigo,
                            $comprobante->enviado_cliente_at ? ' y enviado al cliente por correo' : '',
                        ))
                        ->actions([
                            Action::make('verComprobante')
                                ->label('Ver comprobante')
                                ->button()
                                ->url(route('comprobantes.show', $comprobante), shouldOpenInNewTab: true),
                            Action::make('compartir')
                                ->label('Compartir por WhatsApp')
                                ->color('success')
                                ->url(app(ComprobanteDigital::class)->enlaceWhatsapp($comprobante), shouldOpenInNewTab: true),
                        ]);
                }

                $notificacion->send();
            });
    }

    private function entregaPropia(string $entregaId): Entrega
    {
        $entrega = Entrega::query()
            ->with('pedido.cliente')
            ->whereKey($entregaId)
            ->where('repartidor_id', $this->repartidor?->id)
            ->firstOrFail();

        Gate::authorize('update', $entrega);

        return $entrega;
    }
}
