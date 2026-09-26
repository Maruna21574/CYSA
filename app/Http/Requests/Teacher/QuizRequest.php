<?php

namespace App\Http\Requests\Teacher;

use App\Enums\QuizPurpose;
use App\Enums\ResultVisibility;
use App\Models\Course;
use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class QuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quiz = $this->route('quiz');

        return $quiz instanceof Quiz
            ? $this->user()->can('update', $quiz)
            : $this->user()->can('create', Quiz::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer'],
            'chapter_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'purpose' => ['required', Rule::enum(QuizPurpose::class)],
            'paired_quiz_id' => ['nullable', 'integer'],
            'pass_percentage' => ['required', 'integer', 'between:0,100'],
            'max_attempts' => ['nullable', 'integer', 'between:1,100'],
            'time_limit_minutes' => ['nullable', 'integer', 'between:1,600'],
            'available_from' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', Rule::when($this->filled('available_from'), ['after:available_from'])],
            'shuffle_questions' => ['boolean'],
            'shuffle_options' => ['boolean'],
            'show_result' => ['required', Rule::enum(ResultVisibility::class)],
            'show_correct_answers' => ['required', Rule::enum(ResultVisibility::class)],
        ];
    }

    /**
     * Course, chapter and paired quiz must belong to the teacher / school - checked here
     * so a forged ID can never link a quiz to someone else's content.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $course = Course::manageableBy($this->user())->find($this->integer('course_id'));

                if (! $course) {
                    $validator->errors()->add('course_id', __('Vyberte jeden zo svojich kurzov.'));

                    return;
                }

                if ($this->filled('chapter_id') && ! $course->chapters()->whereKey($this->integer('chapter_id'))->exists()) {
                    $validator->errors()->add('chapter_id', __('Kapitola nepatrí do vybraného kurzu.'));
                }

                if ($this->filled('paired_quiz_id')) {
                    $this->validatePair($validator, $course);
                }

                if ($this->input('show_correct_answers') === ResultVisibility::AfterDue->value && ! $this->filled('due_at')) {
                    $validator->errors()->add('show_correct_answers', __('Na zobrazenie odpovedí po termíne musí mať test nastavený termín.'));
                }
            },
        ];
    }

    private function validatePair(Validator $validator, Course $course): void
    {
        $purpose = QuizPurpose::from($this->input('purpose'));
        $expected = match ($purpose) {
            QuizPurpose::PreTest => QuizPurpose::PostTest,
            QuizPurpose::PostTest => QuizPurpose::PreTest,
            default => null,
        };

        $pair = $course->quizzes()
            ->whereKey($this->integer('paired_quiz_id'))
            ->whereKeyNot($this->route('quiz')?->id ?? 0)
            ->first();

        if ($expected === null || $pair === null || $pair->purpose !== $expected) {
            $validator->errors()->add('paired_quiz_id', __('Vstupný test možno spárovať len s výstupným testom toho istého kurzu (a naopak).'));
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'course_id' => __('kurz'),
            'chapter_id' => __('kapitola'),
            'purpose' => __('typ testu'),
            'paired_quiz_id' => __('párový test'),
            'pass_percentage' => __('hranica úspešnosti'),
            'max_attempts' => __('počet pokusov'),
            'time_limit_minutes' => __('časový limit'),
            'available_from' => __('dostupný od'),
            'due_at' => __('termín'),
            'show_result' => __('zobrazenie výsledku'),
            'show_correct_answers' => __('zobrazenie správnych odpovedí'),
        ];
    }
}
