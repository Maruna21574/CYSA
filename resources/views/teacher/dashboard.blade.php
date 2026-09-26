@php use App\Support\Format; @endphp
<x-layouts::app :title="__('Prehľad učiteľa')">
    <x-page-header :title="__('Vitajte, :name', ['name' => auth()->user()->first_name])" :description="__('Prehľad vašich kurzov, tried a výsledkov.')" />

    <nav aria-label="{{ __('Rýchle akcie') }}" class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            [route('teacher.courses.create'), 'book', __('Nový kurz')],
            [route('teacher.quizzes.create'), 'shield', __('Nový test')],
            [route('teacher.questions.create'), 'question', __('Nová otázka')],
            [route('teacher.courses.index'), 'upload', __('Nahrať materiál')],
        ] as [$url, $icon, $label])
            <a href="{{ $url }}" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-800 shadow-xs transition hover:border-indigo-300 hover:text-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-600">
                <span class="rounded-lg bg-indigo-50 p-2 text-indigo-700"><x-icon :name="$icon" /></span>{{ $label }}
            </a>
        @endforeach
    </nav>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat :label="__('Kurzy')" :value="$coursesCount" :hint="__(':n publikovaných', ['n' => $publishedCourses])" icon="book" />
        <x-stat :label="__('Študenti v mojich triedach')" :value="$studentsCount" :hint="trans_choice('{0} žiadna trieda|{1} :count trieda|[2,4] :count triedy|[5,*] :count tried', $classrooms->count(), ['count' => $classrooms->count()])" icon="users" />
        <x-stat :label="__('Priemerná úspešnosť')" :value="Format::percent($summary['average'])" :hint="__(':n odovzdaných testov', ['n' => $summary['attempts']])" icon="chart" />
        <x-stat :label="__('Úspešné pokusy')" :value="Format::percent($summary['pass_rate'])" icon="check-circle" />
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <x-card :title="__('Posledné odovzdané testy')" class="lg:col-span-2">
            @forelse ($recentAttempts as $attempt)
                <a href="{{ route('attempts.show', $attempt) }}" class="flex items-center gap-3 border-b border-slate-100 py-2.5 text-sm last:border-0 hover:text-indigo-700">
                    <span class="flex-1"><span class="font-medium">{{ $attempt->user?->name }}</span> <span class="text-slate-500">· {{ $attempt->quiz?->title }}</span></span>
                    <span class="text-xs text-slate-500">{{ $attempt->finished_at?->diffForHumans() }}</span>
                    <x-score :attempt="$attempt" />
                </a>
            @empty
                <p class="text-sm text-slate-500">{{ __('Zatiaľ žiadne výsledky.') }}</p>
            @endforelse
        </x-card>

        <div class="flex flex-col gap-6">
            <x-card :title="__('Študenti, ktorí potrebujú pomoc')">
                @forelse ($struggling as $student)
                    <div class="border-b border-slate-100 py-2 last:border-0">
                        <p class="text-sm font-medium text-slate-900">{{ $student->name }}</p>
                        <x-meter :value="$student->average" />
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('Nikto nemá priemer pod 50 %.') }}</p>
                @endforelse
                <a href="{{ route('teacher.analytics.index') }}" class="mt-3 inline-block text-sm font-medium text-indigo-700 hover:underline">{{ __('Celá analytika') }}</a>
            </x-card>

            <x-card :title="__('Blížiace sa termíny')">
                @forelse ($deadlines as $quiz)
                    <a href="{{ route('teacher.quizzes.results', $quiz) }}" class="flex items-start gap-2 border-b border-slate-100 py-2 text-sm last:border-0 hover:text-indigo-700">
                        <x-icon name="calendar" class="mt-0.5 size-4 text-slate-400" />
                        <span class="flex-1"><span class="block font-medium">{{ $quiz->title }}</span><span class="text-xs text-slate-500">{{ $quiz->course?->title }}</span></span>
                        <span class="text-xs whitespace-nowrap text-slate-600">{{ $quiz->due_at->translatedFormat('j. n. H:i') }}</span>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">{{ __('Žiadne termíny v najbližších 3 týždňoch.') }}</p>
                @endforelse
            </x-card>

            <x-card :title="__('Moje triedy')">
                @forelse ($classrooms as $classroom)
                    <div class="flex justify-between border-b border-slate-100 py-2 text-sm last:border-0">
                        <span class="font-medium">{{ $classroom->name }}</span>
                        <span class="text-slate-500">{{ trans_choice('{1} :count študent|[2,4] :count študenti|[0,*] :count študentov', $classroom->students_count, ['count' => $classroom->students_count]) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('Zatiaľ vás školský administrátor nepriradil do žiadnej triedy.') }}</p>
                @endforelse
            </x-card>
        </div>
    </div>
</x-layouts::app>
