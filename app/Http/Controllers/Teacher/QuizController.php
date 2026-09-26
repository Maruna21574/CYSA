<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\QuizRequest;
use App\Models\Course;
use App\Models\Quiz;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuizController extends Controller
{
    private const FIELDS = [
        'course_id', 'chapter_id', 'title', 'description', 'purpose', 'paired_quiz_id', 'pass_percentage',
        'max_attempts', 'time_limit_minutes', 'available_from', 'due_at', 'shuffle_questions', 'shuffle_options',
        'show_result', 'show_correct_answers',
    ];

    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Quiz::class);

        $search = trim((string) $request->query('q'));

        $quizzes = Quiz::query()
            ->manageableBy($request->user())
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($request->integer('course'), fn ($query, int $course) => $query->where('course_id', $course))
            ->with(['course:id,title', 'chapter:id,title'])
            ->withCount('questions')
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('teacher.quizzes.index', ['quizzes' => $quizzes, 'search' => $search]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Quiz::class);

        return view('teacher.quizzes.create', [
            'quiz' => new Quiz(['course_id' => $request->integer('course') ?: null, 'chapter_id' => $request->integer('chapter') ?: null]),
            ...$this->formOptions($request),
        ]);
    }

    public function store(QuizRequest $request): RedirectResponse
    {
        $quiz = DB::transaction(function () use ($request): Quiz {
            $quiz = new Quiz($request->safe()->only(self::FIELDS));
            $quiz->school_id = $request->user()->school_id;
            $quiz->author_id = $request->user()->id;
            $quiz->save();

            $this->linkPair($quiz);
            $this->audit->log(AuditAction::QuizCreated, $quiz, newValues: $quiz->only(['title', 'purpose', 'course_id']));

            return $quiz;
        });

        return redirect()->route('teacher.quizzes.show', $quiz)->with('success', __('Test bol vytvorený. Teraz doň pridajte otázky.'));
    }

    public function show(Quiz $quiz): View
    {
        Gate::authorize('update', $quiz);

        return view('teacher.quizzes.show', ['quiz' => $quiz->load(['course', 'chapter', 'pairedQuiz'])]);
    }

    public function edit(Request $request, Quiz $quiz): View
    {
        Gate::authorize('update', $quiz);

        return view('teacher.quizzes.edit', ['quiz' => $quiz, ...$this->formOptions($request, $quiz)]);
    }

    public function update(QuizRequest $request, Quiz $quiz): RedirectResponse
    {
        $quiz->fill($request->safe()->only(self::FIELDS));
        $changes = $quiz->getDirty();
        $old = Arr::only($quiz->getRawOriginal(), array_keys($changes));

        DB::transaction(function () use ($quiz, $changes, $old): void {
            $quiz->save();
            $this->linkPair($quiz);

            if ($changes !== []) {
                $this->audit->log(AuditAction::QuizUpdated, $quiz, $old, $changes);
            }
        });

        return redirect()->route('teacher.quizzes.show', $quiz)->with('success', __('Nastavenia testu boli uložené.'));
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        Gate::authorize('delete', $quiz);

        $quiz->delete();
        $this->audit->log(AuditAction::QuizDeleted, $quiz, $quiz->only(['title', 'status']));

        return redirect()->route('teacher.quizzes.index')->with('success', __('Test bol odstránený.'));
    }

    /**
     * Keeps a pre/post pair symmetric: A -> B implies B -> A, and old partners are released.
     */
    private function linkPair(Quiz $quiz): void
    {
        Quiz::where('paired_quiz_id', $quiz->id)->whereKeyNot($quiz->paired_quiz_id ?? 0)->update(['paired_quiz_id' => null]);

        if ($quiz->paired_quiz_id) {
            Quiz::whereKey($quiz->paired_quiz_id)->update(['paired_quiz_id' => $quiz->id]);
        }
    }

    /**
     * @return array{courses: array<int, string>, chapters: array<int, array<int, string>>, pairs: array<int, string>}
     */
    private function formOptions(Request $request, ?Quiz $quiz = null): array
    {
        $courses = Course::manageableBy($request->user())->orderBy('title')->get();

        return [
            'courses' => $courses->pluck('title', 'id')->all(),
            // Chapters per course for the dependent select (filtered on the client with Alpine).
            'chapters' => $courses->mapWithKeys(fn (Course $course): array => [
                $course->id => $course->orderedChapters()->pluck('title', 'id')->all(),
            ])->all(),
            'pairs' => Quiz::manageableBy($request->user())
                ->whereIn('purpose', ['pretest', 'posttest'])
                ->when($quiz, fn ($query) => $query->whereKeyNot($quiz->id))
                ->with('course:id,title')
                ->get()
                ->mapWithKeys(fn (Quiz $pair): array => [$pair->id => $pair->title.' ('.$pair->purpose->label().', '.$pair->course->title.')'])
                ->all(),
        ];
    }
}
