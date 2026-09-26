<?php

namespace App\Livewire\Teacher;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Module;
use App\Support\Positioning;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Course structure editor: modules and chapters with drag-and-drop ordering.
 * Every action re-authorizes and loads records only through the course.
 */
class CourseBuilder extends Component
{
    public Course $course;

    #[Validate('required|string|max:255', as: 'názov modulu')]
    public string $newModuleTitle = '';

    public ?int $editingModuleId = null;

    #[Validate('required|string|max:255', as: 'názov modulu')]
    public string $editingModuleTitle = '';

    public function mount(Course $course): void
    {
        $this->authorize('update', $course);
        $this->course = $course;
    }

    public function addModule(): void
    {
        $this->authorize('update', $this->course);
        $this->validateOnly('newModuleTitle');

        $this->course->modules()->create([
            'title' => $this->newModuleTitle,
            'position' => Positioning::next(Module::where('course_id', $this->course->id)),
        ]);

        $this->reset('newModuleTitle');
        $this->course->touch();
    }

    public function editModule(int $moduleId): void
    {
        $module = $this->module($moduleId);
        $this->editingModuleId = $module->id;
        $this->editingModuleTitle = $module->title;
    }

    public function saveModule(): void
    {
        $this->authorize('update', $this->course);
        $this->validateOnly('editingModuleTitle');

        $this->module((int) $this->editingModuleId)->update(['title' => $this->editingModuleTitle]);
        $this->reset('editingModuleId', 'editingModuleTitle');
    }

    public function deleteModule(int $moduleId): void
    {
        $this->authorize('update', $this->course);
        $module = $this->module($moduleId);

        if ($module->chapters()->withTrashed()->exists()) {
            $this->dispatch('toast', type: 'error', message: __('Modul obsahuje kapitoly. Najprv ich presuňte alebo odstráňte.'));

            return;
        }

        $module->delete();
    }

    /**
     * wire:sort handler for modules.
     */
    public function sortModule(int $moduleId, int $position): void
    {
        $this->authorize('update', $this->course);

        Positioning::moveTo(Module::where('course_id', $this->course->id), $this->module($moduleId), $position);
    }

    /**
     * wire:sort handler for chapters; the group id is the target module (chapters can move between modules).
     */
    public function sortChapter(int $chapterId, int $position, int|string|null $moduleId = null): void
    {
        $this->authorize('update', $this->course);

        $chapter = $this->course->chapters()->findOrFail($chapterId);
        $module = $this->module((int) ($moduleId ?? $chapter->module_id));

        if ($chapter->module_id !== $module->id) {
            $chapter->forceFill(['module_id' => $module->id])->save();
        }

        Positioning::moveTo(Chapter::where('module_id', $module->id), $chapter, $position);
        $this->course->touch();
    }

    public function deleteChapter(int $chapterId): void
    {
        $this->authorize('update', $this->course);

        $this->course->chapters()->findOrFail($chapterId)->delete();
        $this->dispatch('toast', message: __('Kapitola bola odstránená.'));
    }

    private function module(int $moduleId): Module
    {
        return $this->course->modules()->findOrFail($moduleId);
    }

    public function render(): View
    {
        return view('livewire.teacher.course-builder', [
            'modules' => $this->course->modules()->with(['chapters' => fn ($query) => $query->withCount('materials')])->get(),
        ]);
    }
}
