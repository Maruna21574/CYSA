<?php

namespace App\Services\Files;

use App\Enums\MaterialType;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Material;
use App\Rules\AllowedMaterialFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Storage abstraction for private files. Files get random names, live on a non-public disk
 * and are streamed only after authorization. Works with a local disk or with S3.
 */
class MaterialStorage
{
    public function disk(): string
    {
        return config('cysa.materials.disk');
    }

    /**
     * Stores an already validated upload and returns the attributes for the Material record.
     *
     * @return array{type: MaterialType, disk: string, path: string, original_name: string, mime_type: string, size: int}
     */
    public function storeMaterial(UploadedFile $file, Chapter $chapter): array
    {
        $extension = AllowedMaterialFile::extensionOf($file);

        return [
            'type' => AllowedMaterialFile::typeFor($extension),
            'disk' => $this->disk(),
            'path' => $this->put($file, "courses/{$chapter->course_id}/chapters/{$chapter->id}", $extension),
            'original_name' => Str::limit(basename($file->getClientOriginalName()), 250, ''),
            'mime_type' => AllowedMaterialFile::CANONICAL_MIME[$extension],
            'size' => (int) $file->getSize(),
        ];
    }

    public function storeCover(UploadedFile $file, Course $course): string
    {
        return $this->put($file, "courses/{$course->id}/cover", AllowedMaterialFile::extensionOf($file));
    }

    public function delete(?string $path, ?string $disk = null): void
    {
        if ($path) {
            Storage::disk($disk ?? $this->disk())->delete($path);
        }
    }

    public function materialResponse(Material $material, bool $download = false): Response
    {
        $extension = pathinfo((string) $material->path, PATHINFO_EXTENSION);
        $name = (Str::slug($material->title) ?: 'material').'.'.$extension;

        return $this->respond(
            (string) $material->disk,
            (string) $material->path,
            (string) $material->mime_type,
            $name,
            $download || ! $material->isInline(),
        );
    }

    public function coverResponse(Course $course): Response
    {
        $extension = pathinfo((string) $course->cover_path, PATHINFO_EXTENSION);

        return $this->respond($this->disk(), (string) $course->cover_path, AllowedMaterialFile::CANONICAL_MIME[$extension] ?? 'image/jpeg', 'cover.'.$extension, false);
    }

    private function put(UploadedFile $file, string $directory, string $extension): string
    {
        // Random name: the original file name never reaches the file system.
        return Storage::disk($this->disk())->putFileAs($directory, $file, Str::uuid()->toString().'.'.$extension);
    }

    private function respond(string $disk, string $path, string $mime, string $name, bool $attachment): Response
    {
        $storage = Storage::disk($disk);
        abort_unless($path !== '' && $storage->exists($path), 404);

        $disposition = $attachment ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE;

        if (config("filesystems.disks.{$disk}.driver") !== 'local') {
            return redirect()->away($storage->temporaryUrl($path, now()->addMinutes(10), [
                'ResponseContentType' => $mime,
                'ResponseContentDisposition' => $disposition.'; filename="'.Str::ascii($name).'"',
            ]));
        }

        // BinaryFileResponse supports HTTP range requests, so videos can be seeked.
        $response = response()->file($storage->path($path), [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
        $response->setContentDisposition($disposition, $name, Str::ascii($name));

        return $response;
    }
}
