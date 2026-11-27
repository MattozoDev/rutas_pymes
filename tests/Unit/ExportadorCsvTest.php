<?php

namespace Tests\Unit;

use App\Services\ExportadorCsv;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExportadorCsvTest extends TestCase
{
    /**
     * @return array<string, array{string|int|float|null, string}>
     */
    public static function celdas(): array
    {
        return [
            'texto normal' => ['Botillería Central', 'Botillería Central'],
            'fórmula' => ['=HYPERLINK("http://malo")', '\'=HYPERLINK("http://malo")'],
            'suma' => ['+56 9 1234 5678', '\'+56 9 1234 5678'],
            'resta' => ['-1+1', '\'-1+1'],
            'arroba' => ['@SUM(A1)', '\'@SUM(A1)'],
            'número' => [42, '42'],
            'nulo' => [null, ''],
        ];
    }

    #[DataProvider('celdas')]
    public function test_neutraliza_valores_que_excel_interpretaria_como_formula(string|int|float|null $valor, string $esperado): void
    {
        $this->assertSame($esperado, ExportadorCsv::celdaSegura($valor));
    }

    public function test_formatea_numeros_con_coma_decimal(): void
    {
        $this->assertSame('36,00', ExportadorCsv::numero('36.00'));
        $this->assertSame('12,50', ExportadorCsv::numero(12.5));
        $this->assertSame('', ExportadorCsv::numero(null));
    }
}
