<?php

namespace Tests\Unit;

use App\Services\Import\CsvImportException;
use App\Services\Import\StudentCsvParser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StudentCsvParserTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function delimiterProvider(): array
    {
        return [
            'semicolon' => ["meno;priezvisko;email\nJana;Kováčová;jana@example.com\n"],
            'comma' => ["meno,priezvisko,email\nJana,Kováčová,jana@example.com\n"],
            'tab' => ["meno\tpriezvisko\temail\nJana\tKováčová\tjana@example.com\n"],
        ];
    }

    #[DataProvider('delimiterProvider')]
    public function test_delimiter_is_detected(string $csv): void
    {
        $rows = (new StudentCsvParser)->parse($csv);

        $this->assertSame([['line' => 2, 'first_name' => 'Jana', 'last_name' => 'Kováčová', 'email' => 'jana@example.com']], $rows);
    }

    public function test_windows_1250_file_from_excel_is_converted(): void
    {
        $csv = iconv('UTF-8', 'WINDOWS-1250', "Meno;Priezvisko;E-mail\r\nĽubomír;Šťastný;lubo@example.com\r\n");

        $rows = (new StudentCsvParser)->parse($csv);

        $this->assertSame('Ľubomír', $rows[0]['first_name']);
        $this->assertSame('Šťastný', $rows[0]['last_name']);
    }

    public function test_bom_english_headers_column_order_and_blank_lines(): void
    {
        $csv = "\xEF\xBB\xBFemail;Last Name;First Name\n\nMAREK@Example.com ; Horváth ; Marek \n;;\n";

        $rows = (new StudentCsvParser)->parse($csv);

        $this->assertCount(1, $rows);
        $this->assertSame(['line' => 3, 'email' => 'marek@example.com', 'last_name' => 'Horváth', 'first_name' => 'Marek'], $rows[0]);
    }

    public function test_quoted_values_may_contain_delimiter(): void
    {
        $rows = (new StudentCsvParser)->parse("meno,priezvisko,email\n\"Anna, Mária\",Nová,anna@example.com\n");

        $this->assertSame('Anna, Mária', $rows[0]['first_name']);
    }

    public function test_missing_columns_are_reported(): void
    {
        $this->expectException(CsvImportException::class);

        (new StudentCsvParser)->parse("meno;email\nJana;jana@example.com\n");
    }

    public function test_empty_file_is_reported(): void
    {
        $this->expectException(CsvImportException::class);

        (new StudentCsvParser)->parse("meno;priezvisko;email\n");
    }

    public function test_row_limit_is_enforced(): void
    {
        $csv = "meno;priezvisko;email\n".str_repeat("A;B;a@example.com\n", StudentCsvParser::MAX_ROWS + 1);

        $this->expectException(CsvImportException::class);

        (new StudentCsvParser)->parse($csv);
    }
}
