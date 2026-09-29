<x-layouts::app :title="__('AI návrhy otázok')">
    <x-page-header :title="__('AI návrhy otázok')" :description="__('Generovanie spustíte pri študijnom materiáli alebo kapitole v editore kurzu.')" />

    @if ($generations->isEmpty())
        <x-empty-state icon="sparkles" :title="__('Zatiaľ ste negenerovali žiadne otázky')" :description="__('Otvorte kapitolu kurzu a pri materiáli kliknite na „Otázky pomocou AI“.')" />
    @else
        <x-table.wrapper>
            <thead class="bg-slate-50">
                <tr>
                    <x-table.th>{{ __('Zdroj') }}</x-table.th>
                    <x-table.th>{{ __('Dátum') }}</x-table.th>
                    <x-table.th class="text-right">{{ __('Otázky') }}</x-table.th>
                    <x-table.th>{{ __('Stav') }}</x-table.th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($generations as $generation)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('teacher.ai.show', $generation) }}" class="font-medium text-slate-900 hover:text-brand-700 hover:underline">{{ $generation->sourceLabel() }}</a>
                            <span class="block text-xs text-slate-500">{{ $generation->course?->title }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $generation->created_at->translatedFormat('j. n. Y H:i') }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $generation->created_questions }} / {{ $generation->requested_count }}</td>
                        <td class="px-4 py-3">
                            <x-badge :color="match ($generation->status) { \App\Enums\AiGenerationStatus::Completed => 'green', \App\Enums\AiGenerationStatus::Failed => 'red', default => 'amber' }">
                                {{ $generation->status->label() }}
                            </x-badge>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table.wrapper>
        <div class="mt-4">{{ $generations->links() }}</div>
    @endif
</x-layouts::app>
