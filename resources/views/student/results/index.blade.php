<x-layouts::app :title="__('Moje výsledky')">
    <x-page-header :title="__('Moje výsledky')" :description="__('História všetkých tvojich pokusov.')" />

    @if ($attempts->isEmpty())
        <x-empty-state icon="chart" :title="__('Zatiaľ žiadne výsledky')" :description="__('Po absolvovaní prvého testu tu uvidíš svoje výsledky.')" />
    @else
        <x-table.wrapper>
            <thead class="bg-slate-50">
                <tr>
                    <x-table.th>{{ __('Test') }}</x-table.th>
                    <x-table.th>{{ __('Dátum') }}</x-table.th>
                    <x-table.th>{{ __('Výsledok') }}</x-table.th>
                    <x-table.th><span class="sr-only">{{ __('Detail') }}</span></x-table.th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($attempts as $attempt)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="block font-medium text-slate-900">{{ $attempt->quiz->title }}</span>
                            <span class="text-xs text-slate-500">{{ $attempt->quiz->course?->title }} · {{ __('pokus č. :n', ['n' => $attempt->attempt_number]) }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $attempt->started_at->translatedFormat('j. n. Y H:i') }}</td>
                        <td class="px-4 py-3">
                            @if ($attempt->isInProgress())
                                <x-badge color="amber">{{ __('Prebieha') }}</x-badge>
                            @elseif ($attempt->scoreIsVisible())
                                <x-score :attempt="$attempt" />
                            @else
                                <x-badge>{{ __('Čaká na zverejnenie') }}</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('attempts.show', $attempt) }}" class="font-medium text-brand-700 hover:underline">{{ __('Detail') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table.wrapper>

        <div class="mt-4">{{ $attempts->links() }}</div>
    @endif
</x-layouts::app>
