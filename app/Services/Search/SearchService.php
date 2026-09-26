<?php

namespace App\Services\Search;

use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Material;
use App\Models\Question;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Global search. Every section reuses the same scopes as the listing pages, so the search
 * never shows anything the user could not open anyway.
 */
class SearchService
{
    private const LIMIT = 8;

    /**
     * @return array<string, Collection<int, array{title: string, subtitle: string|null, url: string}>>
     */
    public function search(User $user, string $term): array
    {
        $term = trim($term);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $like = '%'.addcslashes($term, '%_\\').'%';
        $sections = [];

        $sections[__('Kurzy')] = $this->courses($user, $like);
        $sections[__('Študijné materiály')] = $this->materials($user, $like);

        if ($user->canTeach()) {
            $sections[__('Otázky')] = Question::manageableBy($user)->where('body', 'like', $like)->limit(self::LIMIT)->get()
                ->map(fn (Question $question): array => ['title' => Str::limit($question->body, 100), 'subtitle' => $question->type->label(), 'url' => route('teacher.questions.edit', $question)]);
        }

        if ($user->role === UserRole::SchoolAdmin || $user->isSuperAdmin()) {
            $sections[__('Používatelia')] = User::manageableBy($user)->search($term)->limit(self::LIMIT)->get()
                ->map(fn (User $found): array => ['title' => $found->name, 'subtitle' => $found->role->label().' · '.$found->email, 'url' => route('users.edit', $found)]);
        }

        if ($user->role === UserRole::SchoolAdmin) {
            $sections[__('Triedy')] = Classroom::visibleTo($user)->where('name', 'like', $like)->limit(self::LIMIT)->get()
                ->map(fn (Classroom $classroom): array => ['title' => $classroom->name, 'subtitle' => $classroom->school_year, 'url' => route('school.classrooms.show', $classroom)]);
        }

        if ($user->isSuperAdmin()) {
            $sections[__('Školy')] = School::where('name', 'like', $like)->orWhere('city', 'like', $like)->limit(self::LIMIT)->get()
                ->map(fn (School $school): array => ['title' => $school->name, 'subtitle' => $school->city, 'url' => route('admin.schools.edit', $school)]);
        }

        return array_filter($sections, fn (Collection $results): bool => $results->isNotEmpty());
    }

    /**
     * @return Collection<int, array{title: string, subtitle: string|null, url: string}>
     */
    private function courses(User $user, string $like): Collection
    {
        $query = $user->role === UserRole::Student ? Course::availableTo($user) : Course::manageableBy($user);

        return $query->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('description', 'like', $like))
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Course $course): array => [
                'title' => $course->title,
                'subtitle' => $course->status->label(),
                'url' => $user->can('update', $course) ? route('teacher.courses.show', $course) : route('courses.show', $course),
            ]);
    }

    /**
     * @return Collection<int, array{title: string, subtitle: string|null, url: string}>
     */
    private function materials(User $user, string $like): Collection
    {
        $courses = $user->role === UserRole::Student ? Course::availableTo($user) : Course::manageableBy($user);

        return Material::query()
            ->whereHas('chapter', fn ($query) => $query
                ->whereIn('course_id', $courses->select('id'))
                ->when($user->role === UserRole::Student, fn ($query) => $query->where('is_published', true)))
            ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('original_name', 'like', $like))
            ->with('chapter:id,course_id,title')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Material $material): array => [
                'title' => $material->title,
                'subtitle' => $material->type->label().' · '.$material->chapter->title,
                'url' => route('chapters.show', [$material->chapter->course_id, $material->chapter_id]),
            ]);
    }
}
