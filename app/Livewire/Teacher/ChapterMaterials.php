<?php

namespace App\Livewire\Teacher;

use App\Enums\AuditAction;
use App\Enums\MaterialType;
use App\Models\Chapter;
use App\Models\Material;
use App\Rules\AllowedMaterialFile;
use App\Services\Audit\AuditLogger;
use App\Services\Files\MaterialStorage;
use App\Services\Notifications\CourseNotifier;
use App\Support\Positioning;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Materials of one chapter: file uploads, YouTube videos and external links.
 */
class ChapterMaterials extends Component
{
    use WithFileUploads;

    public Chapter $chapter;

    /** @var TemporaryUploadedFile|null */
    public $upload = null;

    public string $uploadTitle = '';

    public string $linkUrl = '';

    public string $linkTitle = '';

    public function mount(Chapter $chapter): void
    {
        $this->authorize('update', $chapter);
        $this->chapter = $chapter;
    }

    public function saveUpload(MaterialStorage $storage, AuditLogger $audit): void
    {
        $this->authorize('update', $this->chapter);

        $this->validate([
            'upload' => ['required', new AllowedMaterialFile],
            'uploadTitle' => ['nullable', 'string', 'max:255'],
        ], attributes: ['upload' => __('súbor'), 'uploadTitle' => __('názov')]);

        $attributes = $storage->storeMaterial($this->upload, $this->chapter);
        $title = trim($this->uploadTitle) !== '' ? trim($this->uploadTitle) : pathinfo($attributes['original_name'], PATHINFO_FILENAME);

        $material = $this->createMaterial([...$attributes, 'title' => $title]);
        $audit->log(AuditAction::MaterialUploaded, $material, newValues: ['title' => $title, 'size' => $attributes['size'], 'mime_type' => $attributes['mime_type']]);

        $this->upload->delete();
        $this->reset('upload', 'uploadTitle');
        $this->dispatch('toast', message: __('Materiál bol nahraný.'));
    }

    public function saveLink(): void
    {
        $this->authorize('update', $this->chapter);

        $this->validate([
            'linkUrl' => ['required', 'string', 'max:2048', 'url:https,http'],
            'linkTitle' => ['nullable', 'string', 'max:255'],
        ], attributes: ['linkUrl' => __('adresa'), 'linkTitle' => __('názov')]);

        $isYoutube = Material::parseYoutubeId($this->linkUrl) !== null;

        if (! $isYoutube && str_contains(strtolower($this->linkUrl), 'youtu')) {
            throw ValidationException::withMessages(['linkUrl' => __('Tento YouTube odkaz sa nepodarilo rozpoznať. Použite odkaz na konkrétne video.')]);
        }

        $this->createMaterial([
            'type' => $isYoutube ? MaterialType::YouTube : MaterialType::Link,
            'title' => trim($this->linkTitle) !== '' ? trim($this->linkTitle) : ($isYoutube ? __('Video') : parse_url($this->linkUrl, PHP_URL_HOST)),
            'url' => $this->linkUrl,
        ]);

        $this->reset('linkUrl', 'linkTitle');
        $this->dispatch('toast', message: $isYoutube ? __('YouTube video bolo pridané.') : __('Odkaz bol pridaný.'));
    }

    public function sortMaterial(int $materialId, int $position): void
    {
        $this->authorize('update', $this->chapter);

        Positioning::moveTo(Material::where('chapter_id', $this->chapter->id), $this->chapter->materials()->findOrFail($materialId), $position);
    }

    public function delete(int $materialId, MaterialStorage $storage, AuditLogger $audit): void
    {
        $material = $this->chapter->materials()->findOrFail($materialId);
        $this->authorize('delete', $material);

        $material->delete();
        $storage->delete($material->path, $material->disk);
        $audit->log(AuditAction::MaterialDeleted, $material, ['title' => $material->title]);

        $this->dispatch('toast', message: __('Materiál bol odstránený.'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createMaterial(array $attributes): Material
    {
        $material = $this->chapter->materials()->create([
            ...$attributes,
            'uploaded_by' => auth()->id(),
            'position' => Positioning::next(Material::where('chapter_id', $this->chapter->id)),
        ]);

        app(CourseNotifier::class)->materialAdded($material);

        return $material;
    }

    public function render(): View
    {
        return view('livewire.teacher.chapter-materials', [
            'materials' => $this->chapter->materials()->get(),
            'maxUploadMb' => (int) (config('cysa.materials.max_upload_kb') / 1024),
            'extensions' => implode(', ', array_keys(AllowedMaterialFile::ALLOWED)),
        ]);
    }
}
