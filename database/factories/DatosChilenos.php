<?php

namespace Database\Factories;

final class DatosChilenos
{
    /**
     * @var array<string, array{lat: float, lng: float, calles: list<string>}>
     */
    public const COMUNAS = [
        'Santiago' => ['lat' => -33.4489, 'lng' => -70.6693, 'calles' => ['Av. Matta', 'San Diego', 'Santa Rosa', 'Agustinas', 'Bandera', 'Av. Portugal', 'Lord Cochrane']],
        'Providencia' => ['lat' => -33.4314, 'lng' => -70.6093, 'calles' => ['Av. Providencia', 'Pedro de Valdivia', 'Manuel Montt', 'Los Leones', 'Av. Italia']],
        'Ñuñoa' => ['lat' => -33.4569, 'lng' => -70.5973, 'calles' => ['Av. Irarrázaval', 'José Pedro Alessandri', 'Av. Grecia', 'Simón Bolívar', 'Los Aromos']],
        'Las Condes' => ['lat' => -33.4080, 'lng' => -70.5670, 'calles' => ['Av. Apoquindo', 'Manquehue Sur', 'Tomás Moro', 'Av. Cristóbal Colón']],
        'La Florida' => ['lat' => -33.5227, 'lng' => -70.5980, 'calles' => ['Av. Vicuña Mackenna', 'Walker Martínez', 'Av. La Florida', 'Av. Departamental']],
        'Maipú' => ['lat' => -33.5108, 'lng' => -70.7579, 'calles' => ['Av. Pajaritos', '5 de Abril', 'Av. Los Pajaritos', 'Camino a Rinconada']],
        'Puente Alto' => ['lat' => -33.6117, 'lng' => -70.5758, 'calles' => ['Av. Concha y Toro', 'Av. Gabriela', 'Av. Eyzaguirre', 'Av. San Carlos']],
        'San Miguel' => ['lat' => -33.4970, 'lng' => -70.6510, 'calles' => ['Gran Avenida José Miguel Carrera', 'Av. Departamental', 'Salesianos', 'Llano Subercaseaux']],
        'Estación Central' => ['lat' => -33.4600, 'lng' => -70.7000, 'calles' => ['Av. Libertador Bernardo O\'Higgins', 'Las Rejas Sur', 'Av. Ecuador', 'San Borja']],
        'Pedro Aguirre Cerda' => ['lat' => -33.4880, 'lng' => -70.6780, 'calles' => ['Av. Lo Valledor', 'Club Hípico', 'Av. Departamental', 'Av. La Marina']],
        'Recoleta' => ['lat' => -33.4080, 'lng' => -70.6400, 'calles' => ['Av. Recoleta', 'Dominica', 'Av. El Salto', 'Av. Perú']],
        'Independencia' => ['lat' => -33.4170, 'lng' => -70.6650, 'calles' => ['Av. Independencia', 'Av. Fermín Vivaceta', 'Gamero', 'Av. Santa María']],
        'Macul' => ['lat' => -33.4910, 'lng' => -70.5990, 'calles' => ['Av. Macul', 'Quilín', 'Av. Los Plátanos', 'Av. Exequiel Fernández']],
        'La Reina' => ['lat' => -33.4450, 'lng' => -70.5350, 'calles' => ['Av. Larraín', 'Av. Ossa', 'Echeñique', 'Av. Príncipe de Gales']],
        'Vitacura' => ['lat' => -33.3900, 'lng' => -70.5700, 'calles' => ['Av. Vitacura', 'Padre Hurtado Norte', 'Av. Alonso de Córdova', 'Av. Nueva Costanera']],
        'Quinta Normal' => ['lat' => -33.4280, 'lng' => -70.6970, 'calles' => ['Av. San Pablo', 'Av. Carrascal', 'Av. Mapocho', 'Walker Martínez']],
        'San Joaquín' => ['lat' => -33.4960, 'lng' => -70.6280, 'calles' => ['Av. Carlos Valdovinos', 'Av. Vicuña Mackenna', 'Santa Rosa', 'Av. Isabel Riquelme']],
        'Peñalolén' => ['lat' => -33.4850, 'lng' => -70.5410, 'calles' => ['Av. Grecia', 'Av. Tobalaba', 'Av. José Arrieta', 'Av. Consistorial']],
        'La Cisterna' => ['lat' => -33.5300, 'lng' => -70.6640, 'calles' => ['Gran Avenida José Miguel Carrera', 'Av. Fernández Albano', 'Av. Lo Ovalle']],
    ];

    /**
     * @var list<string>
     */
    private const NOMBRES = [
        'Camila', 'Valentina', 'Javiera', 'Constanza', 'Catalina', 'Francisca', 'Fernanda', 'Daniela', 'Paula', 'Carla', 'Ana', 'Josefa',
        'Matías', 'Benjamín', 'Vicente', 'Joaquín', 'Tomás', 'Sebastián', 'Cristóbal', 'Diego', 'Jorge', 'Luis', 'Pedro', 'Felipe',
    ];

    /**
     * @var list<string>
     */
    private const APELLIDOS = [
        'González', 'Muñoz', 'Rojas', 'Díaz', 'Pérez', 'Soto', 'Contreras', 'Silva', 'Martínez', 'Sepúlveda', 'Morales', 'Rodríguez',
        'López', 'Fuentes', 'Hernández', 'Torres', 'Araya', 'Flores', 'Espinoza', 'Valenzuela', 'Castillo', 'Tapia', 'Reyes', 'Vera',
    ];

    /**
     * @var list<string>
     */
    private const NEGOCIOS = [
        'Minimarket El Sol', 'Botillería Central', 'Panadería La Espiga', 'Ferretería Lo Valledor', 'Almacén Doña Rosa',
        'Verdulería Los Andes', 'Bazar Cordillera', 'Librería El Estudiante', 'Carnicería El Huaso', 'Confitería La Pérgola',
        'Botillería Don Lucho', 'Minimarket Santa Elena', 'Cafetería Plaza Ñuñoa', 'Pastelería Mamá Inés', 'Distribuidora Los Pinos',
    ];

    /**
     * @var list<string>
     */
    private const REFERENCIAS = ['Depto 302', 'Local 5', 'Casa B', 'Portón verde', 'Torre A, depto 1104', 'Frente a la plaza', 'Timbre no funciona'];

    public static function nombrePersona(): string
    {
        return fake()->randomElement(self::NOMBRES).' '.fake()->randomElement(self::APELLIDOS);
    }

    public static function nombreCliente(): string
    {
        return fake()->boolean(45) ? fake()->randomElement(self::NEGOCIOS) : self::nombrePersona();
    }

    public static function celular(): string
    {
        return sprintf('+56 9 %04d %04d', fake()->numberBetween(2000, 9999), fake()->numberBetween(0, 9999));
    }

    public static function telefonoFijo(): string
    {
        return sprintf('+56 2 %04d %04d', fake()->numberBetween(2000, 2999), fake()->numberBetween(0, 9999));
    }

    public static function referencia(): ?string
    {
        return fake()->boolean(35) ? fake()->randomElement(self::REFERENCIAS) : null;
    }

    /**
     * @return array{direccion: string, comuna: string, latitud: float, longitud: float}
     */
    public static function direccion(?string $comuna = null): array
    {
        $comuna ??= fake()->randomElement(array_keys(self::COMUNAS));
        $datos = self::COMUNAS[$comuna];

        return [
            'direccion' => fake()->randomElement($datos['calles']).' '.fake()->numberBetween(100, 9800),
            'comuna' => $comuna,
            'latitud' => round($datos['lat'] + fake()->randomFloat(4, -0.008, 0.008), 6),
            'longitud' => round($datos['lng'] + fake()->randomFloat(4, -0.008, 0.008), 6),
        ];
    }

    public static function rut(?int $numero = null): string
    {
        $numero ??= fake()->numberBetween(76_000_000, 78_999_999);
        $suma = 0;
        $factor = 2;

        foreach (array_reverse(str_split((string) $numero)) as $digito) {
            $suma += (int) $digito * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }

        $dv = 11 - ($suma % 11);
        $dv = match ($dv) {
            11 => '0',
            10 => 'K',
            default => (string) $dv,
        };

        return number_format($numero, 0, ',', '.').'-'.$dv;
    }

    public static function patente(): string
    {
        $letras = 'BCDFGHJKLPRSTVWXYZ';

        return implode('', array_map(fn (): string => $letras[fake()->numberBetween(0, strlen($letras) - 1)], range(1, 4)))
            .fake()->numerify('##');
    }
}
