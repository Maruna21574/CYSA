<x-layouts::app :title="__('Výsledok testu')">
    <x-page-header :title="$attempt->quiz->title" :description="$isOwner ? __('Pokus č. :n', ['n' => $attempt->attempt_number]) : __(':name · pokus č. :n', ['name' => $attempt->user->name, 'n' => $attempt->attempt_number])" :breadcrumbs="$isOwner
        ? [['label' => $attempt->quiz->course->title, 'url' => route('courses.show', $attempt->quiz->course)], ['label' => $attempt->quiz->title, 'url' => route('student.quizzes.show', $attempt->quiz)], ['label' => __('Výsledok')]]
        : [['label' => __('Testy'), 'url' => route('teacher.quizzes.index')], ['label' => $attempt->quiz->title, 'url' => route('teacher.quizzes.show', $attempt->quiz)], ['label' => __('Výsledok')]]" />

    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        @if ($showScore)
            <x-card class="flex flex-col items-center gap-2 py-8 text-center">
                <span @class(['rounded-full p-3', 'bg-emerald-100 text-emerald-700' => $attempt->passed, 'bg-red-100 text-red-700' => ! $attempt->passed])>
                    <x-icon :name="$attempt->passed ? 'trophy' : 'alert'" class="size-8" />
                </span>
                <p class="text-4xl font-bold tabular-nums text-slate-900">{{ rtrim(rtrim(number_format((float) $attempt->percentage, 1, ',', ''), '0'), ',') }} %</p>
                <p class="text-slate-600">
                    {{ __(':score z :max bodov', ['score' => rtrim(rtrim(number_format((float) $attempt->score, 2, ',', ''), '0'), ','), 'max' => rtrim(rtrim(number_format((float) $attempt->max_score, 2, ',', ''), '0'), ',')]) }}
                    · {{ $attempt->passed ? __('Úspešne absolvovaný') : __('Neúspešný (potrebné :p %)', ['p' => $attempt->quiz->pass_percentage]) }}
                </p>
                <p class="text-xs text-slate-500">
                    {{ __('Čas: :time', ['time' => gmdate($attempt->time_spent_seconds >= 3600 ? 'H:i:s' : 'i:s', (int) $attempt->time_spent_seconds)]) }}
                    @if ($attempt->timed_out) · {{ __('odovzdané automaticky po uplynutí času') }} @endif
                </p>
            </x-card>
        @else
            <x-card class="py-8 text-center">
                <p class="font-medium text-slate-900">{{ __('Test bol odovzdaný.') }}</p>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $attempt->quiz->show_result === \App\Enums\ResultVisibility::AfterDue && $attempt->quiz->due_at
                        ? __('Výsledok uvidíš po termíne :date.', ['date' => $attempt->quiz->due_at->translatedFormat('j. n. Y H:i')])
                        : __('Výsledok ti oznámi učiteľ.') }}
                </p>
            </x-card>
        @endif

        @if ($showCorrect)
            <section class="flex flex-col gap-4" aria-labelledby="review-heading">
                <h2 id="review-heading" class="text-lg font-semibold text-slate-900">{{ __('Prehľad odpovedí') }}</h2>

                @foreach ($answers as $answer)
                    @include('attempts._answer', ['answer' => $answer, 'number' => $loop->iteration])
                @endforeach
            </section>
        @elseif ($showScore)
            <p class="text-center text-sm text-slate-500">{{ __('Správne odpovede sa pri tomto teste nezobrazujú.') }}</p>
        @endif

        @if ($isOwner)
            <div class="flex justify-center">
                <x-link-button variant="secondary" :href="route('courses.show', $attempt->quiz->course)">{{ __('Späť na kurz') }}</x-link-button>
            </div>
        @endif
    </div>
</x-layouts::app>
