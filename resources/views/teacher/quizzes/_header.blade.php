@php
    $tabs = [
        'questions' => [__('Otázky'), route('teacher.quizzes.show', $quiz)],
        'edit' => [__('Nastavenia'), route('teacher.quizzes.edit', $quiz)],
    ];

    if (Route::has('teacher.quizzes.results')) {
        $tabs['results'] = [__('Výsledky'), route('teacher.quizzes.results', $quiz)];
    }
@endphp

<x-page-header :title="$quiz->title" :description="$quiz->purpose->label().' · '.$quiz->course->title" :breadcrumbs="[
    ['label' => __('Testy'), 'url' => route('teacher.quizzes.index')],
    ['label' => $quiz->title],
]">
    <x-slot:actions>
        <x-badge :color="$quiz->status->color()" class="self-center">{{ $quiz->status->label() }}</x-badge>

        @foreach (\App\Enums\QuizStatus::cases() as $target)
            @continue($target === $quiz->status)
            <form method="POST" action="{{ route('teacher.quizzes.status', $quiz) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $target->value }}">
                <x-button :variant="$target === \App\Enums\QuizStatus::Published ? 'primary' : 'secondary'">
                    {{ match ($target) {
                        \App\Enums\QuizStatus::Published => __('Publikovať'),
                        \App\Enums\QuizStatus::Draft => __('Vrátiť do konceptu'),
                        \App\Enums\QuizStatus::Archived => __('Archivovať'),
                    } }}
                </x-button>
            </form>
        @endforeach
    </x-slot:actions>
</x-page-header>

<nav class="mb-6 flex gap-1 overflow-x-auto border-b border-slate-200" aria-label="{{ __('Sekcie testu') }}">
    @foreach ($tabs as $key => [$label, $url])
        <a href="{{ $url }}" @if ($active === $key) aria-current="page" @endif @class([
            '-mb-px whitespace-nowrap border-b-2 px-4 py-2 text-sm font-medium',
            'border-indigo-600 text-indigo-700' => $active === $key,
            'border-transparent text-slate-600 hover:border-slate-300 hover:text-slate-900' => $active !== $key,
        ])>{{ $label }}</a>
    @endforeach
</nav>
