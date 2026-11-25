<?php

namespace App\Filament\Resources\Clientes\Schemas;

use App\Filament\Resources\Pedidos\Schemas\PedidoForm;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClienteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del cliente')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('nombre')->required()->maxLength(255),
                        TextInput::make('telefono')->label('Teléfono')->tel()->placeholder('+56 9 1234 5678')->maxLength(30),
                        TextInput::make('email')->label('Correo')->email()->helperText('Se usa para enviar el comprobante de entrega.')->maxLength(255),
                        TextInput::make('direccion')->label('Dirección')->maxLength(255),
                        TextInput::make('comuna')->datalist(PedidoForm::COMUNAS_FRECUENTES)->maxLength(100),
                        Toggle::make('activo')->default(true)->inline(false),
                        Fieldset::make('Ubicación (opcional)')
                            ->columns(2)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('latitud')->numeric()->minValue(-90)->maxValue(90),
                                TextInput::make('longitud')->numeric()->minValue(-180)->maxValue(180),
                            ]),
                        Textarea::make('notas')->maxLength(2000)->columnSpanFull(),
                    ]),
            ]);
    }
}
