<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streamed CSV download that opens correctly in Slovak Excel (UTF-8 BOM, semicolon)
 * and is protected against CSV/formula injection.
 */
class CsvExport
{
    /**
     * @param  list<string>  $header
     * @param  iterable<array<int, scalar|null>>  $rows
     */
    public static function download(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header, ';', '"', '');

            foreach ($rows as $row) {
                fputcsv($out, array_map(self::cell(...), $row), ';', '"', '');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * Values starting with = + - @ (or control characters) would be executed as formulas by
     * spreadsheet programs; they are prefixed with an apostrophe. Numbers stay numbers.
     */
    public static function cell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return str_replace('.', ',', (string) $value);
        }

        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
