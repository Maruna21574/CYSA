<x-layouts::app :title="__('Môj prehľad')">
    <x-page-header :title="__('Ahoj, :name!', ['name' => auth()->user()->first_name])" :description="__('Tu nájdeš svoje kurzy, testy a výsledky.')" />

    @if ($gamification)
        <x-gamification-card :stats="$gamification['stats']" :badges="$gamification['badges']" class="mb-6" />
    @endif

    @if ($courses->isEmpty())
        <x-empty-state icon="book" :title="__('Zatiaľ nemáš priradené žiadne kurzy')" :description="__('Keď ti učiteľ priradí kurz, nájdeš ho tu.')" />
    @else
        @if ($continue->isNotEmpty())
            <section aria-labelledby="continue-heading" class="mb-6">
                <h2 id="continue-heading" class="mb-3 text-lg font-semibold text-slate-900">{{ __('Pokračuj v učení') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($continue as $item)
                        <a href="{{ route('chapters.show', [$item['course'], $item['chapter']]) }}" class="flex items-center gap-4 rounded-xl border border-indigo-200 bg-indigo-50 p-4 transition hover:bg-indigo-100 focus-visible:outline-2 focus-visible:outline-indigo-600">
                            <span class="rounded-full bg-white p-2 text-indigo-700"><x-icon name="play" class="size-6" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-xs text-indigo-700">{{ $item['course']->title }}</span>
                                <span class="block truncate font-semibold text-slate-900">{{ $item['chapter']->title }}</span>
                            </span>
                            <x-icon name="chevron-right" class="size-5 text-indigo-700" />
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="grid items-start gap-6 lg:grid-cols-3">
            <x-card :title="__('Moje kurzy')" class="lg:col-span-2">
                @foreach ($courses as $course)
                    @php $progress = $courseProgress[$course->id] ?? null; @endphp
                    <a href="{{ route('courses.show', $course) }}" class="block border-b border-slate-100 py-3 last:border-0 hover:text-indigo-700">
                        <div class="mb-1 flex items-center justify-between gap-2">
                            <span class="font-medium">{{ $course->title }}</span>
                            @if ($progress?->completed_at)
                                <x-badge color="green">{{ __('Dokončený') }}</x-badge>
                            @endif
                        </div>
                        <x-meter tone="progress" :value="$progress?->percentage ?? 0" :label="__('Progres kurzu :title', ['title' => $course->title])" />
                    </a>
                @endforeach
            </x-card>

            <div class="flex flex-col gap-6">
                <x-card :title="__('Nadchádzajúce testy')">
                    @forelse ($upcoming as $quiz)
                        <a href="{{ route('student.quizzes.show', $quiz) }}" class="flex items-start gap-2 border-b border-slate-100 py-2 text-sm last:border-0 hover:text-indigo-700">
                            <x-icon name="shield" class="mt-0.5 size-4 text-indigo-600" />
                            <span class="flex-1"><span class="block font-medium">{{ $quiz->title }}</span><span class="text-xs text-slate-500">{{ $quiz->course?->title }}</span></span>
                            @if ($quiz->due_at)
                                <span @class(['text-xs whitespace-nowrap', 'font-semibold text-red-700' => $quiz->due_at->lt(now()->addDays(2)), 'text-slate-600' => ! $quiz->due_at->lt(now()->addDays(2))])>{{ __('do :date', ['date' => $quiz->due_at->translatedFormat('j. n.')]) }}</span>
                            @endif
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('Všetky testy máš hotové.') }}</p>
                    @endforelse
                </x-card>

                <x-card :title="__('Posledné výsledky')">
                    @forelse ($recent as $attempt)
                        <a href="{{ route('attempts.show', $attempt) }}" class="flex items-center justify-between gap-2 border-b border-slate-100 py-2 text-sm last:border-0 hover:text-indigo-700">
                            <span class="truncate">{{ $attempt->quiz->title }}</span>
                            @if ($attempt->scoreIsVisible())
                                <x-score :attempt="$attempt" />
                            @else
                                <x-badge>{{ __('čaká') }}</x-badge>
                            @endif
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('Zatiaľ žiadne výsledky.') }}</p>
                    @endforelse
                    <a href="{{ route('student.results.index') }}" class="mt-3 inline-block text-sm font-medium text-indigo-700 hover:underline">{{ __('Všetky výsledky') }}</a>
                </x-card>
            </div>
        </div>
    @endif
</x-layouts::app>
