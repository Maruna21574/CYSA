<x-layouts::app :title="$quiz->title">
    <x-page-header :title="$quiz->title" :description="$quiz->purpose->label()" :breadcrumbs="[
        ['label' => $quiz->course->title, 'url' => route('courses.show', $quiz->course)],
        ['label' => $quiz->title],
    ]" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="flex flex-col gap-6 lg:col-span-2">
            @if ($quiz->description)
                <x-card :title="__('Pokyny')">
                    <p class="text-sm whitespace-pre-line text-slate-700">{{ $quiz->description }}</p>
                </x-card>
            @endif

            <x-card :title="__('Tvoje pokusy')">
                @forelse ($history as $attempt)
                    <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 py-3 last:border-0">
                        <span class="text-sm font-medium text-slate-900">{{ __('Pokus č. :n', ['n' => $attempt->attempt_number]) }}</span>
                        <span class="text-sm text-slate-500">{{ $attempt->started_at->translatedFormat('j. n. Y H:i') }}</span>
                        <span class="flex-1"></span>
                        @if ($attempt->isInProgress())
                            <x-badge color="amber">{{ __('Prebieha') }}</x-badge>
                        @elseif ($attempt->scoreIsVisible())
                            <x-score :attempt="$attempt" />
                        @else
                            <x-badge>{{ __('Výsledok zatiaľ nie je zverejnený') }}</x-badge>
                        @endif
                        <a href="{{ route('attempts.show', $attempt) }}" class="text-sm font-medium text-indigo-700 hover:underline">{{ __('Detail') }}</a>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('Test si zatiaľ neabsolvoval(a).') }}</p>
                @endforelse
            </x-card>
        </div>

        <x-card class="flex h-fit flex-col gap-4">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <dt class="text-slate-500">{{ __('Otázky') }}</dt><dd class="font-medium text-slate-900">{{ $quiz->questions_count }}</dd>
                <dt class="text-slate-500">{{ __('Časový limit') }}</dt><dd class="font-medium text-slate-900">{{ $quiz->time_limit_minutes ? __(':min min', ['min' => $quiz->time_limit_minutes]) : __('bez limitu') }}</dd>
                <dt class="text-slate-500">{{ __('Na úspech') }}</dt><dd class="font-medium text-slate-900">{{ $quiz->pass_percentage }} %</dd>
                <dt class="text-slate-500">{{ __('Pokusy') }}</dt><dd class="font-medium text-slate-900">{{ $quiz->max_attempts ? __(':used z :max', ['used' => $availability->attemptsUsed, 'max' => $quiz->max_attempts]) : __('neobmedzene') }}</dd>
                @if ($quiz->due_at)
                    <dt class="text-slate-500">{{ __('Termín') }}</dt><dd class="font-medium text-slate-900">{{ $quiz->due_at->translatedFormat('j. n. Y H:i') }}</dd>
                @endif
            </dl>

            @if ($availability->inProgress)
                <form method="POST" action="{{ route('student.quizzes.start', $quiz) }}">
                    @csrf
                    <x-button class="w-full">{{ __('Pokračovať v teste') }}</x-button>
                </form>
            @elseif ($availability->canStart)
                <form method="POST" action="{{ route('student.quizzes.start', $quiz) }}"
                      x-data @submit="if (! confirm(@js($quiz->time_limit_minutes ? __('Po spustení beží čas :min minút. Začať?', ['min' => $quiz->time_limit_minutes]) : __('Začať test?')))) $event.preventDefault()">
                    @csrf
                    <x-button class="w-full">{{ $availability->attemptsUsed ? __('Začať nový pokus') : __('Začať test') }}</x-button>
                </form>
            @else
                <p class="rounded-lg bg-slate-100 p-3 text-sm text-slate-700" role="status">{{ $availability->reason }}</p>
            @endif
        </x-card>
    </div>
</x-layouts::app>
