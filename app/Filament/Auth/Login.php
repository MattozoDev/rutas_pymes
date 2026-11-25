<?php

namespace App\Filament\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Support\Components\Attributes\Unexposed;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Locked;

class Login extends BaseLogin
{
    #[Locked]
    public bool $esRepartidor = false;

    #[Unexposed]
    public function mount(): void
    {
        $this->esRepartidor = request()->query('modo') === 'repartidor';

        parent::mount();
    }

    #[Unexposed]
    public function getHeading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getHeading();
        }

        return $this->esRepartidor ? 'Ingreso de repartidor' : 'Inicia sesión';
    }

    #[Unexposed]
    public function getSubheading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getSubheading();
        }

        return $this->esRepartidor
            ? 'Usa el correo y la contraseña que te entregó tu empresa.'
            : 'Administra pedidos, rutas y comprobantes de tu pyme.';
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction()->label('Ingresar'),
            Action::make('cambiarModo')
                ->label($this->esRepartidor ? 'Entrar como administrador' : 'Entrar como repartidor')
                ->icon($this->esRepartidor ? Heroicon::OutlinedBuildingStorefront : Heroicon::OutlinedTruck)
                ->color('gray')
                ->outlined()
                ->url(filament()->getLoginUrl($this->esRepartidor ? [] : ['modo' => 'repartidor'])),
        ];
    }
}
