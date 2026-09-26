@php use App\Support\Format; @endphp
<x-layouts::app :title="__('Výskum: pred a po vzdelávaní')">
    <x-page-header :title="__('Výskum: pred a po vzdelávaní')"
        :description="__('Porovnanie prvého pokusu vo vstupnom a výstupnom teste. Študenti sú uvedení iba pod pseudonymným kódom.')"
        :breadcrumbs="[['label' => __('Analytika'), 'url' => route('teacher.analytics.index')], ['label' => __('Výskum')]]" />

    @if ($pairs->isEmpty())
        <x-empty-state icon="chart" :title="__('Žiadna dvojica testov')"
            :description="__('Vytvorte vstupný a výstupný test toho istého kurzu a v nastaveniach ich spárujte.')">
            <x-link-button :href="route('teacher.quizzes.create')">{{ __('Vytvoriť test') }}</x-link-button>
        </x-empty-state>
    @else
        <form method="GET" action="{{ route('teacher.research.index') }}" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xs sm:grid-cols-3 sm:items-end">
            <div class="sm:col-span-1">
                <label for="pair" class="block text-xs font-medium text-slate-600">{{ __('Dvojica testov') }}</label>
                <select id="pair" name="pair" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm">
                    @foreach ($pairs as $pair)
                        <option value="{{ $pair->id }}" @selected($pre?->id === $pair->id)>{{ $pair->course->title }}: {{ $pair->title }} → {{ $pair->pairedQuiz->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="classroom" class="block text-xs font-medium text-slate-600">{{ __('Trieda') }}</label>
                <select id="classroom" name="classroom" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm">
                    <option value="">{{ __('Všetky') }}</option>
                    @foreach ($classrooms as $id => $name)
                        <option value="{{ $id }}" @selected($classroomId === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <x-button class="py-1.5">{{ __('Zobraziť') }}</x-button>
                <x-link-button variant="secondary" class="py-1.5" :href="route('teacher.research.export', array_filter(['quiz' => $pre->id, 'classroom' => $classroomId]))">
                    <x-icon name="download" class="size-4" />{{ __('Export CSV') }}
                </x-link-button>
            </div>
        </form>

        @php $stats = $result['stats']; @endphp

        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat :label="__('Spárovaní študenti')" :value="$stats['paired']" :hint="__('vstupný: :pre, výstupný: :post', ['pre' => $stats['pre_participants'], 'post' => $stats['post_participants']])" icon="users" />
            <x-stat :label="__('Priemer pred')" :value="Format::percent($stats['mean_pre'])" icon="chart" />
            <x-stat :label="__('Priemer po')" :value="Format::percent($stats['mean_post'])" icon="chart" />
            <x-stat :label="__('Priemerné zlepšenie')" :value="$stats['mean_delta'] === null ? '—' : ($stats['mean_delta'] > 0 ? '+' : '').Format::number($stats['mean_delta'], 1).' p. b.'"
                :hint="__('zlepšilo sa :n z :total', ['n' => $stats['improved'], 'total' => $stats['paired']])" icon="trophy" />
        </div>

        <x-card :title="__('Štatistika')" class="mb-6">
            <dl class="grid gap-3 text-sm sm:grid-cols-4">
                <div><dt class="text-slate-500">{{ __('Smerodajná odchýlka zlepšenia') }}</dt><dd class="font-semibold tabular-nums">{{ Format::number($stats['sd_delta']) }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Párový t-test (t)') }}</dt><dd class="font-semibold tabular-nums">{{ Format::number($stats['t'], 3) }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Stupne voľnosti (df)') }}</dt><dd class="font-semibold tabular-nums">{{ $stats['df'] ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Veľkosť účinku (Cohenovo d)') }}</dt><dd class="font-semibold tabular-nums">{{ Format::number($stats['cohens_d'], 3) }}</dd></div>
            </dl>
            <p class="mt-3 text-xs text-slate-500">{{ __('Hodnoty slúžia na orientáciu; p-hodnotu a ďalšie testy vypočítajte zo surových dát v exporte (R, SPSS, Python).') }}</p>
        </x-card>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-card :title="__('Témy pred a po')">
                @forelse ($result['topics'] as $topic)
                    <div class="mb-3">
                        <p class="text-sm font-medium text-slate-800">{{ $topic->topic }}
                            @if ($topic->delta !== null)
                                <span @class(['text-xs', 'text-emerald-700' => $topic->delta > 0, 'text-red-700' => $topic->delta < 0])>({{ $topic->delta > 0 ? '+' : '' }}{{ Format::number($topic->delta, 1) }} p. b.)</span>
                            @endif
                        </p>
                        <div class="grid grid-cols-[3rem_1fr] items-center gap-x-2 text-xs text-slate-500">
                            <span>{{ __('pred') }}</span><x-meter :value="$topic->pre" />
                            <span>{{ __('po') }}</span><x-meter :value="$topic->post" />
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('Bez údajov.') }}</p>
                @endforelse
            </x-card>

            <x-card :title="__('Skupiny (triedy)')">
                @if ($result['groups']->isEmpty())
                    <p class="text-sm text-slate-500">{{ __('Bez údajov.') }}</p>
                @else
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-xs text-slate-500"><th class="py-1">{{ __('Skupina') }}</th><th class="py-1 text-right">n</th><th class="py-1 text-right">{{ __('Pred') }}</th><th class="py-1 text-right">{{ __('Po') }}</th><th class="py-1 text-right">Δ</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($result['groups'] as $group)
                                <tr><td class="py-2">{{ $group->group }}</td><td class="py-2 text-right tabular-nums">{{ $group->n }}</td><td class="py-2 text-right tabular-nums">{{ Format::percent($group->pre) }}</td><td class="py-2 text-right tabular-nums">{{ Format::percent($group->post) }}</td><td class="py-2 text-right font-semibold tabular-nums">{{ Format::number($group->delta, 1) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-card>
        </div>

        <section class="mt-6" aria-labelledby="paired-heading">
            <h2 id="paired-heading" class="mb-3 text-lg font-semibold text-slate-900">{{ __('Jednotliví študenti (pseudonymizovane)') }}</h2>
            @if ($result['students']->isEmpty())
                <x-empty-state icon="users" :title="__('Zatiaľ nikto neabsolvoval oba testy')" />
            @else
                <x-table.wrapper>
                    <thead class="bg-slate-50"><tr><x-table.th>{{ __('Kód') }}</x-table.th><x-table.th>{{ __('Skupina') }}</x-table.th><x-table.th class="text-right">{{ __('Pred') }}</x-table.th><x-table.th class="text-right">{{ __('Po') }}</x-table.th><x-table.th class="text-right">{{ __('Zlepšenie') }}</x-table.th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($result['students'] as $row)
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">{{ $row->code }}</td>
                                <td class="px-4 py-2 text-slate-600">{{ $row->group }}</td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ Format::percent($row->pre) }}</td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ Format::percent($row->post) }}</td>
                                <td @class(['px-4 py-2 text-right font-semibold tabular-nums', 'text-emerald-700' => $row->delta > 0, 'text-red-700' => $row->delta < 0])>{{ $row->delta > 0 ? '+' : '' }}{{ Format::number($row->delta, 1) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table.wrapper>
            @endif
        </section>
    @endif
</x-layouts::app>
