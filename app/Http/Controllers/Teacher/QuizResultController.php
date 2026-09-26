<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Quiz;
use App\Services\Analytics\AnalyticsFilter;
use App\Services\Analytics\QuizAnalytics;
use App\Services\Audit\AuditLogger;
use App\Support\CsvExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuizResultController extends Controller
{
    public function __construct(private QuizAnalytics $analytics) {}

    public function show(Request $request, Quiz $quiz): View
    {
        Gate::authorize('viewResults', $quiz);

        $filter = AnalyticsFilter::fromRequest($request)->withQuiz($quiz->id);

        return view('teacher.quizzes.results', [
            'quiz' => $quiz->load('course'),
            'filter' => $filter,
            'summary' => $this->analytics->summary($filter),
            'attempts' => $this->analytics->attempts($filter)
                ->with('user:id,first_name,last_name')
                ->orderByDesc('finished_at')
                ->paginate(30)
                ->withQueryString(),
            'questions' => $this->analytics->questionStats($filter, 'asc', null),
            'wrongAnswers' => $this->analytics->commonWrongAnswers($filter, 8),
            'classrooms' => Classroom::visibleTo($request->user())->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    /**
     * Results of the quiz for the teacher's gradebook. Contains names (the teacher's own
     * students) but no e-mails or other contact data; the export itself is audited.
     */
    public function export(Request $request, Quiz $quiz, AuditLogger $audit): StreamedResponse
    {
        Gate::authorize('viewResults', $quiz);

        $filter = AnalyticsFilter::fromRequest($request)->withQuiz($quiz->id);
        $audit->log(AuditAction::DataExported, $quiz, metadata: ['type' => 'quiz_results', ...$filter->toQuery()]);

        $rows = $this->analytics->attempts($filter)
            ->with('user:id,first_name,last_name')
            ->orderBy('user_id')
            ->orderBy('attempt_number')
            ->lazy()
            ->map(fn ($attempt): array => [
                $attempt->user->last_name,
                $attempt->user->first_name,
                $attempt->attempt_number,
                $attempt->started_at?->format('Y-m-d H:i'),
                $attempt->finished_at?->format('Y-m-d H:i'),
                $attempt->time_spent_seconds,
                (float) $attempt->score,
                (float) $attempt->max_score,
                (float) $attempt->percentage,
                $attempt->passed ? 'áno' : 'nie',
                $attempt->timed_out ? 'áno' : 'nie',
            ]);

        return CsvExport::download(
            'vysledky-'.$quiz->id.'-'.now()->format('Ymd').'.csv',
            ['Priezvisko', 'Meno', 'Pokus', 'Začiatok', 'Koniec', 'Čas (s)', 'Body', 'Max. body', 'Úspešnosť (%)', 'Úspešný', 'Časový limit vypršal'],
            $rows,
        );
    }
}
