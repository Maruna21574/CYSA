<?php

namespace App\Services\Files\TextExtraction;

use App\Models\Material;
use App\Models\MaterialText;
use App\Services\AI\AiException;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

/**
 * Plain text of an uploaded study material (PDF, DOCX, PPTX, ODT, ODP, TXT), cached in
 * material_texts. Office files are ZIP containers; only the XML parts with text are read
 * and their uncompressed size is limited (zip bomb protection).
 */
class TextExtractor
{
    public const SUPPORTED = ['pdf', 'docx', 'pptx', 'odt', 'odp', 'txt'];

    /** Upper bound for the uncompressed XML read from one office document. */
    private const MAX_UNCOMPRESSED_BYTES = 30 * 1024 * 1024;

    public static function supports(Material $material): bool
    {
        return $material->isFile() && in_array(strtolower(pathinfo((string) $material->path, PATHINFO_EXTENSION)), self::SUPPORTED, true);
    }

    /**
     * @throws AiException when the file cannot be read
     */
    public function textOf(Material $material): string
    {
        $cached = MaterialText::find($material->id);

        if ($cached !== null) {
            return $cached->content;
        }

        $text = $this->extract($material);

        MaterialText::create(['material_id' => $material->id, 'content' => $text, 'extracted_at' => now()]);

        return $text;
    }

    /**
     * @throws AiException
     */
    public function extract(Material $material): string
    {
        if (! self::supports($material)) {
            throw new AiException(__('Z tohto typu súboru nie je možné čítať text.'));
        }

        $extension = strtolower(pathinfo((string) $material->path, PATHINFO_EXTENSION));
        [$path, $temporary] = $this->localPath($material);

        try {
            $text = match ($extension) {
                'pdf' => (new PdfParser)->parseFile($path)->getText(),
                'docx' => $this->zipText($path, ['word/document.xml'], '</w:p>'),
                'pptx' => $this->zipText($path, $this->slides($path), '</a:p>'),
                'odt', 'odp' => $this->zipText($path, ['content.xml'], '</text:p>'),
                'txt' => (string) file_get_contents($path),
            };
        } catch (AiException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            throw new AiException(__('Súbor sa nepodarilo prečítať. Skontrolujte, či nie je poškodený alebo chránený heslom.'), previous: $e);
        } finally {
            if ($temporary) {
                @unlink($path);
            }
        }

        return $this->normalize($text);
    }

    /**
     * Local file path; a copy is made when materials live on S3.
     *
     * @return array{0: string, 1: bool} path and whether it is a temporary copy
     */
    private function localPath(Material $material): array
    {
        $disk = Storage::disk((string) $material->disk);

        if (config("filesystems.disks.{$material->disk}.driver") === 'local') {
            return [$disk->path((string) $material->path), false];
        }

        $temporary = tempnam(sys_get_temp_dir(), 'cysa');
        file_put_contents($temporary, $disk->readStream((string) $material->path));

        return [$temporary, true];
    }

    /**
     * @param  list<string>  $entries
     */
    private function zipText(string $path, array $entries, string $paragraphEnd): string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new AiException(__('Súbor sa nepodarilo otvoriť.'));
        }

        $total = 0;
        $parts = [];

        foreach ($entries as $entry) {
            $stat = $zip->statName($entry);

            if ($stat === false) {
                continue;
            }

            $total += $stat['size'];

            if ($total > self::MAX_UNCOMPRESSED_BYTES) {
                $zip->close();

                throw new AiException(__('Dokument je príliš veľký na spracovanie.'));
            }

            $xml = (string) $zip->getFromName($entry);
            $parts[] = strip_tags(str_replace([$paragraphEnd, '<w:br/>', '<w:tab/>'], ["\n", "\n", ' '], $xml));
        }

        $zip->close();

        return html_entity_decode(implode("\n\n", $parts), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Slide XML parts of a presentation in slide order.
     *
     * @return list<string>
     */
    private function slides(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return [];
        }

        $slides = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);

            if (preg_match('~^ppt/slides/slide(\d+)\.xml$~', $name, $matches)) {
                $slides[(int) $matches[1]] = $name;
            }
        }

        $zip->close();
        ksort($slides);

        return array_values($slides);
    }

    private function normalize(string $text): string
    {
        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = (string) @iconv('WINDOWS-1250', 'UTF-8//IGNORE', $text);
        }

        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text) ?? $text;
        $text = preg_replace("/\n\s*\n\s*\n+/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
