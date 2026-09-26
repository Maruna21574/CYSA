<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Quiz;
use App\Services\Analytics\ResearchService;
use App\Services\Audit\AuditLogger;
use App\Support\CsvExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pre/post test comparison for the research. Everything is pseudonymous (research codes).
 */
class ResearchController extends Controller
{
    public function __construct(private ResearchService $research) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->canTeach(), 403);

        $pairs = $this->research->pairs($request->user());
        $pre = $pairs->firstWhere('id', $request->integer('pair')) ?? $pairs->first();
        $classroomId = $this->classroomId($request);

        return view('teacher.research.index', [
            'pairs' => $pairs,
            'pre' => $pre,
            'classroomId' => $classroomId,
            'classrooms' => Classroom::visibleTo($request->user())->orderBy('name')->pluck('name', 'id')->all(),
            'result' => $pre ? $this->research->comparison($pre, $pre->pairedQuiz, $classroomId) : null,
        ]);
    }

    public function export(Request $request, Quiz $quiz, AuditLogger $audit): StreamedResponse
    {
        Gate::authorize('viewResults', $quiz);
        abort_unless($quiz->pairedQuiz && Gate::allows('viewResults', $quiz->pairedQuiz), 404);

        [$pre, $post] = $quiz->purpose->value === 'pretest' ? [$quiz, $quiz->pairedQuiz] : [$quiz->pairedQuiz, $quiz];
        $classroomId = $this->classroomId($request);

        $audit->log(AuditAction::DataExported, $pre, metadata: ['type' => 'research', 'classroom_id' => $classroomId]);

        return CsvExport::download(
            'vyskum-pre-post-'.$pre->id.'-'.now()->format('Ymd').'.csv',
            ResearchService::exportHeader(),
            $this->research->exportRows($pre, $post, $classroomId),
        );
    }

    private function classroomId(Request $request): ?int
    {
        $id = $request->integer('classroom') ?: null;

        return $id && Classroom::visibleTo($request->user())->whereKey($id)->exists() ? $id : null;
    }
}
