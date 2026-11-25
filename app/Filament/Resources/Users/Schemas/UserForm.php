<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\RolUsuario;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->rule(Password::min(8))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Déjala vacía para mantener la actual.' : null),
                Select::make('rol')
                    ->options(RolUsuario::class)
                    ->default(RolUsuario::Administrador)
                    ->required()
                    ->helperText('Para repartidores, crea la cuenta desde "Repartidores y flota" para vincularla a su ficha.'),
                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->tel()
                    ->maxLength(30),
                Toggle::make('activo')
                    ->default(true)
                    ->inline(false),
            ]);
    }
}
