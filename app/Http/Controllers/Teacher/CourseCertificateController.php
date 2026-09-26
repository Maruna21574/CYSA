<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AuditAction;
use App\Enums\QuizStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Certificates\CertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Certificate conditions of a course and the list of issued certificates.
 */
class CourseCertificateController extends Controller
{
    public function edit(Course $course): View
    {
        Gate::authorize('update', $course);

        $requirements = $course->certificateRequirements()->get();

        return view('teacher.courses.certificate', [
            'course' => $course,
            'chapters' => $course->orderedChapters(publishedOnly: true),
            'quizzes' => $course->quizzes()->where('status', QuizStatus::Published)->orderBy('title')->get(),
            'selectedChapters' => $requirements->pluck('chapter_id')->filter()->all(),
            'selectedQuizzes' => $requirements->pluck('quiz_id')->filter()->all(),
            'certificates' => $course->certificates()->with('user:id,first_name,last_name')->latest('issued_at')->get(),
        ]);
    }

    public function update(Request $request, Course $course, AuditLogger $audit, CertificateService $certificates): RedirectResponse
    {
        Gate::authorize('update', $course);

        $data = $request->validate([
            'certificate_enabled' => ['boolean'],
            'certificate_min_percentage' => ['required', 'integer', 'between:0,100'],
            'chapters' => ['array'],
            'chapters.*' => ['integer', Rule::exists('chapters', 'id')->where('course_id', $course->id)],
            'quizzes' => ['array'],
            'quizzes.*' => ['integer', Rule::exists('quizzes', 'id')->where('course_id', $course->id)],
        ]);

        DB::transaction(function () use ($course, $data): void {
            $course->forceFill([
                'certificate_enabled' => (bool) ($data['certificate_enabled'] ?? false),
                'certificate_min_percentage' => $data['certificate_min_percentage'],
            ])->save();

            $course->certificateRequirements()->delete();

            foreach (array_unique($data['chapters'] ?? []) as $chapterId) {
                $course->certificateRequirements()->create(['chapter_id' => $chapterId]);
            }

            foreach (array_unique($data['quizzes'] ?? []) as $quizId) {
                $course->certificateRequirements()->create(['quiz_id' => $quizId]);
            }
        });

        $audit->log(AuditAction::CertificateSettingsChanged, $course, newValues: $data);

        // Students who already meet the (possibly relaxed) conditions get the certificate now.
        if ($course->certificate_enabled) {
            User::whereIn('id', DB::table('course_progress')->where('course_id', $course->id)->select('user_id'))
                ->each(fn (User $student) => $certificates->issueIfEligible($student, $course));
        }

        return back()->with('success', __('Podmienky certifikátu boli uložené.'));
    }
}
