<?php

namespace App\Rules;

use App\Enums\MaterialType;
use Closure;
use finfo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Upload whitelist for study materials. The file must have an allowed extension AND its
 * content (detected by finfo, not the browser) must match one of the MIME types expected
 * for that extension. Active formats such as HTML, SVG, PHP or JS are never allowed.
 */
class AllowedMaterialFile implements ValidationRule
{
    /** Office Open XML / OpenDocument files are ZIP containers; some finfo databases report them as such. */
    private const ZIP_MIMES = ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'];

    /**
     * extension => [material type, accepted detected MIME types]
     *
     * @var array<string, array{MaterialType, list<string>}>
     */
    public const ALLOWED = [
        'pdf' => [MaterialType::Document, ['application/pdf']],
        'docx' => [MaterialType::Document, ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', ...self::ZIP_MIMES]],
        'pptx' => [MaterialType::Document, ['application/vnd.openxmlformats-officedocument.presentationml.presentation', ...self::ZIP_MIMES]],
        'xlsx' => [MaterialType::Document, ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', ...self::ZIP_MIMES]],
        'odt' => [MaterialType::Document, ['application/vnd.oasis.opendocument.text', ...self::ZIP_MIMES]],
        'odp' => [MaterialType::Document, ['application/vnd.oasis.opendocument.presentation', ...self::ZIP_MIMES]],
        'txt' => [MaterialType::Document, ['text/plain']],
        'jpg' => [MaterialType::Image, ['image/jpeg']],
        'jpeg' => [MaterialType::Image, ['image/jpeg']],
        'png' => [MaterialType::Image, ['image/png']],
        'webp' => [MaterialType::Image, ['image/webp']],
        'gif' => [MaterialType::Image, ['image/gif']],
        'mp4' => [MaterialType::Video, ['video/mp4']],
        'webm' => [MaterialType::Video, ['video/webm']],
        'mp3' => [MaterialType::Audio, ['audio/mpeg', 'audio/mp3']],
    ];

    /** Canonical MIME type stored for each extension (used when serving the file). */
    public const CANONICAL_MIME = [
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'txt' => 'text/plain',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mp3' => 'audio/mpeg',
    ];

    /**
     * @param  list<string>|null  $only  restrict to a subset of extensions (e.g. images for covers)
     */
    public function __construct(private ?array $only = null) {}

    public static function imagesOnly(): self
    {
        return new self(['jpg', 'jpeg', 'png', 'webp']);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail(__('Súbor sa nepodarilo nahrať.'));

            return;
        }

        $extension = self::extensionOf($value);
        $allowed = $this->only ?? array_keys(self::ALLOWED);

        if (! in_array($extension, $allowed, true) || ! isset(self::ALLOWED[$extension])) {
            $fail(__('Tento typ súboru nie je povolený. Povolené: :types.', ['types' => implode(', ', $allowed)]));

            return;
        }

        if ($value->getSize() > config('cysa.materials.max_upload_kb') * 1024) {
            $fail(__('Súbor je príliš veľký (najviac :size MB).', ['size' => (int) (config('cysa.materials.max_upload_kb') / 1024)]));

            return;
        }

        // Always sniff the bytes on disk; never trust the browser-supplied or name-derived type.
        $detected = (string) (new finfo(FILEINFO_MIME_TYPE))->file($value->getRealPath());
        [, $mimes] = self::ALLOWED[$extension];

        if (! in_array($detected, $mimes, true)) {
            $fail(__('Obsah súboru nezodpovedá jeho prípone.'));

            return;
        }

        if (in_array($detected, self::ZIP_MIMES, true) && ! $this->startsWithZipSignature($value)) {
            $fail(__('Obsah súboru nezodpovedá jeho prípone.'));
        }
    }

    public static function extensionOf(UploadedFile $file): string
    {
        return strtolower($file->getClientOriginalExtension());
    }

    public static function typeFor(string $extension): MaterialType
    {
        return self::ALLOWED[$extension][0];
    }

    private function startsWithZipSignature(UploadedFile $file): bool
    {
        $handle = fopen($file->getRealPath(), 'rb');
        $signature = fread($handle, 4);
        fclose($handle);

        return $signature === "PK\x03\x04";
    }
}
