@php use App\Support\Format; @endphp
<x-layouts::app :title="__('Analytika')">
    <x-page-header :title="__('Analytika')" :description="__('Výsledky študentov vo vašich testoch.')">
        <x-slot:actions>
            <x-link-button variant="secondary" :href="route('teacher.research.index')"><x-icon name="chart" class="size-4" />{{ __('Výskum: pred / po') }}</x-link-button>
        </x-slot:actions>
    </x-page-header>

    <x-analytics-filter :filter="$filter" :action="route('teacher.analytics.index')" :classrooms="$classrooms" :courses="$courses" :quizzes="$quizzes" />

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <x-stat :label="__('Študenti')" :value="$summary['students']" icon="users" />
        <x-stat :label="__('Absolvované testy')" :value="$summary['attempts']" icon="shield" />
        <x-stat :label="__('Priemerná úspešnosť')" :value="Format::percent($summary['average'])" icon="chart" />
        <x-stat :label="__('Úspešní (pokusy)')" :value="Format::percent($summary['pass_rate'])" icon="check-circle" />
        <x-stat :label="__('Priemerný čas')" :value="Format::duration($summary['average_seconds'])" icon="clock" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card :title="__('Úspešnosť podľa tém')">
            <p class="mb-3 text-xs text-slate-500">{{ __('Témy zoradené od najproblematickejšej.') }}</p>
            @forelse ($topics as $topic)
                <div class="mb-2">
                    <p class="text-sm text-slate-800">{{ $topic->topic }} <span class="text-xs text-slate-500">({{ $topic->answers }})</span></p>
                    <x-meter :value="$topic->success" :label="$topic->topic" />
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('Otázky zatiaľ nemajú priradené témy alebo chýbajú výsledky.') }}</p>
            @endforelse
        </x-card>

        <x-card :title="__('Najčastejšie chybné odpovede')">
            @forelse ($wrongAnswers as $wrong)
                <div class="border-b border-slate-100 py-2 text-sm last:border-0">
                    <p class="text-slate-500">{{ \Illuminate\Support\Str::limit($wrong->question, 100) }}</p>
                    <p class="font-medium text-red-700">„{{ $wrong->option }}“ <span class="font-normal text-slate-500">· {{ $wrong->picks }}×</span></p>
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('Bez údajov.') }}</p>
            @endforelse
        </x-card>

        @foreach ([[__('Najťažšie otázky'), $hardest], [__('Najľahšie otázky'), $easiest]] as [$title, $list])
            <x-card :title="$title">
                @forelse ($list as $question)
                    <div class="border-b border-slate-100 py-2 last:border-0">
                        <p class="mb-1 text-sm text-slate-800">{{ \Illuminate\Support\Str::limit($question->body, 110) }}</p>
                        <x-meter :value="$question->success" />
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('Bez údajov.') }}</p>
                @endforelse
            </x-card>
        @endforeach
    </div>

    <section class="mt-6" aria-labelledby="students-heading">
        <h2 id="students-heading" class="mb-3 text-lg font-semibold text-slate-900">{{ __('Študenti') }}</h2>
        @if ($students->isEmpty())
            <x-empty-state icon="users" :title="__('Bez výsledkov')" />
        @else
            <x-table.wrapper>
                <thead class="bg-slate-50">
                    <tr>
                        <x-table.th>{{ __('Študent') }}</x-table.th>
                        <x-table.th class="text-right">{{ __('Pokusy') }}</x-table.th>
                        <x-table.th class="text-right">{{ __('Úspešné') }}</x-table.th>
                        <x-table.th class="w-64">{{ __('Priemer') }}</x-table.th>
                        <x-table.th>{{ __('Posledná aktivita') }}</x-table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($students as $student)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ $student->name }}
                                @if ($student->struggling)
                                    <x-badge color="red" class="ml-1">{{ __('potrebuje pomoc') }}</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $student->attempts }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $student->passed }}</td>
                            <td class="px-4 py-3"><x-meter :value="$student->average" /></td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $student->last_at ? \Illuminate\Support\Carbon::parse($student->last_at)->translatedFormat('j. n. Y') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table.wrapper>
        @endif
    </section>

    <section class="mt-6" aria-labelledby="progress-heading">
        <h2 id="progress-heading" class="mb-3 text-lg font-semibold text-slate-900">{{ __('Progres v kurzoch') }}</h2>
        @if ($progress->isEmpty())
            <x-empty-state icon="book" :title="__('Študenti zatiaľ nezačali žiadny kurz')" />
        @else
            <x-table.wrapper>
                <thead class="bg-slate-50">
                    <tr>
                        <x-table.th>{{ __('Študent') }}</x-table.th>
                        <x-table.th>{{ __('Kurz') }}</x-table.th>
                        <x-table.th class="text-right">{{ __('Kapitoly') }}</x-table.th>
                        <x-table.th class="w-64">{{ __('Progres') }}</x-table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($progress as $row)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $row->user?->name }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $row->course?->title }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $row->completed_chapters }} / {{ $row->total_chapters }}</td>
                            <td class="px-4 py-3"><x-meter :value="$row->percentage" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table.wrapper>
        @endif
    </section>
</x-layouts::app>
