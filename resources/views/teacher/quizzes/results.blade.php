@php use App\Support\Format; @endphp
<x-layouts::app :title="__('Výsledky testu')">
    @include('teacher.quizzes._header', ['active' => 'results'])

    <x-analytics-filter :filter="$filter" :action="route('teacher.quizzes.results', $quiz)" :classrooms="$classrooms" />

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <x-stat :label="__('Študenti')" :value="$summary['students']" icon="users" />
        <x-stat :label="__('Odovzdané pokusy')" :value="$summary['attempts']" icon="shield" />
        <x-stat :label="__('Priemerná úspešnosť')" :value="Format::percent($summary['average'])" icon="chart" />
        <x-stat :label="__('Úspešné pokusy')" :value="Format::percent($summary['pass_rate'])" icon="check-circle" />
        <x-stat :label="__('Priemerný čas')" :value="Format::duration($summary['average_seconds'])" icon="clock" />
    </div>

    <div class="mb-6 flex justify-end">
        <x-link-button variant="secondary" :href="route('teacher.quizzes.results.export', [$quiz, ...$filter->toQuery()])">
            <x-icon name="download" class="size-4" />{{ __('Exportovať CSV') }}
        </x-link-button>
    </div>

    <div class="grid gap-6 xl:grid-cols-5">
        <section class="xl:col-span-3" aria-labelledby="attempts-heading">
            <h2 id="attempts-heading" class="mb-3 text-lg font-semibold text-slate-900">{{ __('Pokusy študentov') }}</h2>
            @if ($attempts->isEmpty())
                <x-empty-state icon="chart" :title="__('Zatiaľ žiadne odovzdané pokusy')" />
            @else
                <x-table.wrapper>
                    <thead class="bg-slate-50">
                        <tr>
                            <x-table.th>{{ __('Študent') }}</x-table.th>
                            <x-table.th>{{ __('Odovzdané') }}</x-table.th>
                            <x-table.th>{{ __('Čas') }}</x-table.th>
                            <x-table.th>{{ __('Výsledok') }}</x-table.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($attempts as $attempt)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('attempts.show', $attempt) }}" class="font-medium text-slate-900 hover:text-indigo-700 hover:underline">{{ $attempt->user->name }}</a>
                                    <span class="block text-xs text-slate-500">{{ __('pokus č. :n', ['n' => $attempt->attempt_number]) }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $attempt->finished_at?->translatedFormat('j. n. Y H:i') }}</td>
                                <td class="px-4 py-3 tabular-nums text-slate-600">{{ Format::duration($attempt->time_spent_seconds) }}</td>
                                <td class="px-4 py-3"><x-score :attempt="$attempt" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table.wrapper>
                <div class="mt-4">{{ $attempts->links() }}</div>
            @endif
        </section>

        <div class="flex flex-col gap-6 xl:col-span-2">
            <x-card :title="__('Úspešnosť otázok')">
                @forelse ($questions as $question)
                    <div class="border-b border-slate-100 py-2 last:border-0">
                        <p class="mb-1 text-sm text-slate-800">{{ \Illuminate\Support\Str::limit($question->body, 110) }}</p>
                        <x-meter :value="$question->success" :label="__('Úspešnosť otázky')" />
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('Bez údajov.') }}</p>
                @endforelse
            </x-card>

            <x-card :title="__('Najčastejšie chybné odpovede')">
                @forelse ($wrongAnswers as $wrong)
                    <div class="border-b border-slate-100 py-2 text-sm last:border-0">
                        <p class="text-slate-500">{{ \Illuminate\Support\Str::limit($wrong->question, 90) }}</p>
                        <p class="font-medium text-red-700">„{{ $wrong->option }}“ <span class="font-normal text-slate-500">· {{ trans_choice('[1,*] :count-krát', $wrong->picks, ['count' => $wrong->picks]) }}</span></p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('Bez údajov.') }}</p>
                @endforelse
            </x-card>
        </div>
    </div>
</x-layouts::app>
