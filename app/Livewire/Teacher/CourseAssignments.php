<?php

namespace App\Livewire\Teacher;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\CourseNotifier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Assigns a course to classrooms or individual students of the course's school.
 */
class CourseAssignments extends Component
{
    public Course $course;

    public string $classroomId = '';

    public string $studentSearch = '';

    public ?string $availableFrom = null;

    public ?string $dueAt = null;

    public function mount(Course $course): void
    {
        $this->authorize('assign', $course);
        $this->course = $course;
    }

    public function assignClassroom(AuditLogger $audit): void
    {
        $this->authorize('assign', $this->course);
        $this->validate([
            'classroomId' => ['required', 'integer'],
            ...$this->dateRules(),
        ], attributes: ['classroomId' => __('trieda')]);

        $classroom = Classroom::where('school_id', $this->course->school_id)->findOrFail((int) $this->classroomId);

        $this->assign(['classroom_id' => $classroom->id], $audit);
        $this->reset('classroomId');
    }

    public function assignStudent(int $studentId, AuditLogger $audit): void
    {
        $this->authorize('assign', $this->course);
        $this->validate($this->dateRules());

        $student = $this->studentQuery()->findOrFail($studentId);

        $this->assign(['user_id' => $student->id], $audit);
    }

    public function unassign(int $assignmentId, AuditLogger $audit): void
    {
        $this->authorize('assign', $this->course);

        $assignment = $this->course->assignments()->findOrFail($assignmentId);
        $assignment->delete();

        $audit->log(AuditAction::CourseUnassigned, $this->course, $assignment->only(['classroom_id', 'user_id']));
        $this->dispatch('toast', message: __('Priradenie bolo zrušené.'));
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function studentResults(): Collection
    {
        if (mb_strlen(trim($this->studentSearch)) < 2) {
            return new Collection;
        }

        return $this->studentQuery()
            ->search($this->studentSearch)
            ->whereNotIn('id', $this->course->assignments()->whereNotNull('user_id')->select('user_id'))
            ->orderBy('last_name')
            ->limit(8)
            ->get();
    }

    /**
     * @param  array{classroom_id?: int, user_id?: int}  $target
     */
    private function assign(array $target, AuditLogger $audit): void
    {
        $assignment = $this->course->assignments()->updateOrCreate($target, [
            'assigned_by' => auth()->id(),
            'available_from' => $this->availableFrom ?: null,
            'due_at' => $this->dueAt ?: null,
        ]);

        $audit->log(AuditAction::CourseAssigned, $this->course, newValues: $assignment->only(['classroom_id', 'user_id', 'available_from', 'due_at']));

        if ($assignment->wasRecentlyCreated) {
            app(CourseNotifier::class)->courseAvailable($this->course, $assignment);
        }

        unset($this->studentResults);
        $this->dispatch('toast', message: __('Kurz bol priradený.'));
    }

    /**
     * @return Builder<User>
     */
    private function studentQuery(): Builder
    {
        return User::query()
            ->where('school_id', $this->course->school_id)
            ->where('role', UserRole::Student)
            ->active();
    }

    /**
     * @return array<string, list<string>>
     */
    private function dateRules(): array
    {
        return [
            'availableFrom' => ['nullable', 'date'],
            'dueAt' => ['nullable', 'date', 'after_or_equal:availableFrom'],
        ];
    }

    public function render(): View
    {
        return view('livewire.teacher.course-assignments', [
            'assignments' => $this->course->assignments()
                ->with(['classroom' => fn ($query) => $query->withCount('students'), 'user:id,first_name,last_name,email'])
                ->get()
                ->sortBy(fn (CourseAssignment $assignment): string => $assignment->classroom_id ? '0'.$assignment->classroom?->name : '1'.$assignment->user?->last_name),
            'classrooms' => Classroom::where('school_id', $this->course->school_id)
                ->whereNotIn('id', $this->course->assignments()->whereNotNull('classroom_id')->select('classroom_id'))
                ->orderByDesc('school_year')
                ->orderBy('name')
                ->get(['id', 'name', 'school_year']),
        ]);
    }
}
