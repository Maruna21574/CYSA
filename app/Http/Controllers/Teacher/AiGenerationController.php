<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AiGenerationStatus;
use App\Enums\AuditAction;
use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateAiQuestions;
use App\Models\AiGeneration;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Material;
use App\Services\AI\AIQuizGenerationService;
use App\Services\Audit\AuditLogger;
use App\Services\Files\TextExtraction\TextExtractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AiGenerationController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAvailable();

        return view('teacher.ai.index', [
            'generations' => AiGeneration::where('user_id', $request->user()->id)
                ->with(['course:id,title', 'material:id,title', 'chapter:id,title'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensureAvailable();
        [$course, $chapter, $material] = $this->source($request);

        return view('teacher.ai.create', [
            'course' => $course,
            'chapter' => $chapter,
            'material' => $material,
            'usedToday' => $this->usedToday($request),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $this->ensureAvailable();
        [$course, $chapter, $material] = $this->source($request);

        $data = $request->validate([
            'count' => ['required', 'integer', 'between:1,'.config('cysa.ai.max_questions')],
            'types' => ['required', 'array', 'min:1'],
            'types.*' => [Rule::enum(QuestionType::class)],
            'difficulty' => ['nullable', Rule::enum(Difficulty::class)],
        ], attributes: ['count' => __('počet otázok'), 'types' => __('typy otázok')]);

        if ($this->usedToday($request) >= (int) config('cysa.ai.daily_limit_per_teacher')) {
            return back()->withInput()->with('error', __('Dosiahli ste denný limit generovaní pomocou AI. Skúste to zajtra.'));
        }

        $generation = new AiGeneration;
        $generation->forceFill([
            'user_id' => $request->user()->id,
            'course_id' => $course->id,
            'chapter_id' => $chapter?->id,
            'material_id' => $material?->id,
            'status' => AiGenerationStatus::Pending,
            'provider' => config('cysa.ai.provider'),
            'model' => config('cysa.ai.model'),
            'requested_count' => (int) $data['count'],
            'question_types' => array_values(array_unique($data['types'])),
            'difficulty' => $data['difficulty'] ?? null,
        ])->save();

        $audit->log(AuditAction::AiQuestionsRequested, $generation, newValues: [
            'course_id' => $course->id,
            'material_id' => $material?->id,
            'chapter_id' => $chapter?->id,
            'count' => $generation->requested_count,
        ]);

        GenerateAiQuestions::dispatch($generation->id);

        return redirect()->route('teacher.ai.show', $generation);
    }

    public function show(AiGeneration $generation): View
    {
        Gate::authorize('update', $generation->course);

        return view('teacher.ai.show', ['generation' => $generation->load(['course', 'material', 'chapter'])]);
    }

    /**
     * The source is either a study material or the text of a chapter; both must belong
     * to a course the teacher manages.
     *
     * @return array{0: Course, 1: Chapter|null, 2: Material|null}
     */
    private function source(Request $request): array
    {
        if ($request->filled('material')) {
            $material = Material::with('chapter.course')->findOrFail($request->integer('material'));
            Gate::authorize('update', $material->chapter->course);
            abort_unless(TextExtractor::supports($material), 422, __('Z tohto materiálu nie je možné generovať otázky.'));

            return [$material->chapter->course, $material->chapter, $material];
        }

        $chapter = Chapter::with('course')->findOrFail($request->integer('chapter'));
        Gate::authorize('update', $chapter->course);
        abort_if(blank(strip_tags((string) $chapter->content)), 422, __('Kapitola nemá žiadny text.'));

        return [$chapter->course, $chapter, null];
    }

    private function usedToday(Request $request): int
    {
        return AiGeneration::where('user_id', $request->user()->id)->where('created_at', '>=', now()->startOfDay())->count();
    }

    private function ensureAvailable(): void
    {
        abort_unless(request()->user()->canTeach() && AIQuizGenerationService::isAvailable(), 404);
    }
}
