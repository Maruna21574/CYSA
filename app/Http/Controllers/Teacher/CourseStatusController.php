<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AuditAction;
use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Publishing, returning to draft and archiving of a course.
 */
class CourseStatusController extends Controller
{
    public function __invoke(Request $request, Course $course, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('update', $course);

        $status = CourseStatus::from($request->validate([
            'status' => ['required', Rule::enum(CourseStatus::class)],
        ])['status']);

        if ($status === $course->status) {
            return back();
        }

        if ($status === CourseStatus::Published && ! $course->chapters()->where('is_published', true)->exists()) {
            return back()->with('error', __('Kurz musí mať aspoň jednu zverejnenú kapitolu.'));
        }

        $old = $course->status;

        $course->forceFill([
            'status' => $status,
            'published_at' => $status === CourseStatus::Published ? ($course->published_at ?? now()) : $course->published_at,
            'archived_at' => $status === CourseStatus::Archived ? now() : null,
        ])->save();

        $audit->log(match ($status) {
            CourseStatus::Published => AuditAction::CoursePublished,
            CourseStatus::Archived => AuditAction::CourseArchived,
            CourseStatus::Draft => AuditAction::CourseUnpublished,
        }, $course, ['status' => $old->value], ['status' => $status->value]);

        return back()->with('success', match ($status) {
            CourseStatus::Published => __('Kurz bol publikovaný. Študenti, ktorým je priradený, ho už vidia.'),
            CourseStatus::Archived => __('Kurz bol archivovaný.'),
            CourseStatus::Draft => __('Kurz bol vrátený do konceptu.'),
        });
    }
}
