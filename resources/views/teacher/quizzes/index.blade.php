<x-layouts::app :title="__('Testy')">
    <x-page-header :title="__('Testy a kvízy')" :description="__('Testy vašich kurzov vrátane vstupných a výstupných testov.')">
        <x-slot:actions>
            @can('create', \App\Models\Quiz::class)
                <x-link-button :href="route('teacher.quizzes.create')"><x-icon name="plus" class="size-4" />{{ __('Nový test') }}</x-link-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('teacher.quizzes.index') }}" class="mb-4 flex gap-2" role="search">
        <x-search-input name="q" :value="$search" :label="__('Hľadať test')" />
        <x-button variant="secondary">{{ __('Hľadať') }}</x-button>
    </form>

    @if ($quizzes->isEmpty())
        <x-empty-state icon="shield" :title="__('Žiadne testy')" :description="__('Test vždy patrí ku kurzu. Vytvorte prvý test a pridajte doň otázky z banky.')" />
    @else
        <x-table.wrapper>
            <thead class="bg-slate-50">
                <tr>
                    <x-table.th>{{ __('Test') }}</x-table.th>
                    <x-table.th>{{ __('Typ') }}</x-table.th>
                    <x-table.th class="text-right">{{ __('Otázky') }}</x-table.th>
                    <x-table.th>{{ __('Termín') }}</x-table.th>
                    <x-table.th>{{ __('Stav') }}</x-table.th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($quizzes as $quiz)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('teacher.quizzes.show', $quiz) }}" class="font-medium text-slate-900 hover:text-brand-700 hover:underline">{{ $quiz->title }}</a>
                            <span class="block text-xs text-slate-500">{{ $quiz->course->title }}@if ($quiz->chapter) · {{ $quiz->chapter->title }}@endif</span>
                        </td>
                        <td class="px-4 py-3 text-slate-700">
                            <x-badge :color="$quiz->purpose->isResearch() ? 'brand' : 'slate'">{{ $quiz->purpose->label() }}</x-badge>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $quiz->questions_count }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $quiz->due_at?->translatedFormat('j. n. Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3"><x-badge :color="$quiz->status->color()">{{ $quiz->status->label() }}</x-badge></td>
                    </tr>
                @endforeach
            </tbody>
        </x-table.wrapper>

        <div class="mt-4">{{ $quizzes->links() }}</div>
    @endif
</x-layouts::app>
