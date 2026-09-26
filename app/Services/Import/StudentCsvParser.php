<?php

namespace App\Services\Import;

use Illuminate\Support\Str;

/**
 * Parses a student list exported from Excel or Google Sheets.
 *
 * Accepts ";", "," or tab as delimiter, UTF-8 (with or without BOM) or Windows-1250
 * (the default of Slovak Excel), and Slovak or English column names.
 */
class StudentCsvParser
{
    public const MAX_ROWS = 1000;

    /** Normalized header name => attribute. */
    private const HEADER_ALIASES = [
        'meno' => 'first_name',
        'krstne meno' => 'first_name',
        'first_name' => 'first_name',
        'first name' => 'first_name',
        'priezvisko' => 'last_name',
        'last_name' => 'last_name',
        'last name' => 'last_name',
        'surname' => 'last_name',
        'email' => 'email',
        'e-mail' => 'email',
        'mail' => 'email',
    ];

    /**
     * @return list<array{line: int, first_name: string, last_name: string, email: string}>
     *
     * @throws CsvImportException
     */
    public function parse(string $contents): array
    {
        $contents = $this->toUtf8($contents);
        $delimiter = $this->detectDelimiter($contents);

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        $columns = $this->mapHeader(fgetcsv($stream, null, $delimiter, '"', '') ?: []);

        $rows = [];
        $line = 1;

        while (($fields = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            $line++;

            if ($this->isEmpty($fields)) {
                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                fclose($stream);

                throw new CsvImportException(__('Súbor môže obsahovať najviac :max študentov.', ['max' => self::MAX_ROWS]));
            }

            $row = ['line' => $line];

            foreach ($columns as $attribute => $index) {
                $row[$attribute] = trim((string) ($fields[$index] ?? ''));
            }

            $row['email'] = Str::lower($row['email']);
            $rows[] = $row;
        }

        fclose($stream);

        if ($rows === []) {
            throw new CsvImportException(__('Súbor neobsahuje žiadnych študentov.'));
        }

        return $rows;
    }

    private function toUtf8(string $contents): string
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

        if (! mb_check_encoding($contents, 'UTF-8')) {
            $converted = @iconv('WINDOWS-1250', 'UTF-8//IGNORE', $contents);

            if ($converted === false) {
                throw new CsvImportException(__('Kódovanie súboru sa nepodarilo rozpoznať. Uložte ho ako „CSV UTF-8“.'));
            }

            $contents = $converted;
        }

        return str_replace(["\r\n", "\r"], "\n", $contents);
    }

    private function detectDelimiter(string $contents): string
    {
        $firstLine = strtok($contents, "\n") ?: '';

        $counts = [';' => substr_count($firstLine, ';'), ',' => substr_count($firstLine, ','), "\t" => substr_count($firstLine, "\t")];
        arsort($counts);

        return array_key_first($counts);
    }

    /**
     * @param  array<int, string|null>  $header
     * @return array{first_name: int, last_name: int, email: int}
     */
    private function mapHeader(array $header): array
    {
        $columns = [];

        foreach ($header as $index => $name) {
            $normalized = Str::lower(trim(Str::ascii((string) $name)));
            $attribute = self::HEADER_ALIASES[$normalized] ?? null;

            if ($attribute !== null && ! isset($columns[$attribute])) {
                $columns[$attribute] = $index;
            }
        }

        $missing = array_diff(['first_name', 'last_name', 'email'], array_keys($columns));

        if ($missing !== []) {
            throw new CsvImportException(__('Prvý riadok súboru musí obsahovať stĺpce „meno“, „priezvisko“ a „email“.'));
        }

        return $columns;
    }

    /**
     * @param  array<int, string|null>  $fields
     */
    private function isEmpty(array $fields): bool
    {
        return trim(implode('', array_map('strval', $fields))) === '';
    }
}
