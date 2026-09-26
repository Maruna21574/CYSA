<x-layouts::app :title="__('Vyhľadávanie')">
    <x-page-header :title="__('Vyhľadávanie')" />

    <form method="GET" action="{{ route('search') }}" class="mb-6 flex max-w-xl gap-2" role="search">
        <label for="search-page" class="sr-only">{{ __('Hľadaný výraz') }}</label>
        <input id="search-page" name="q" type="search" value="{{ $term }}" minlength="2" maxlength="100" autofocus
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/30">
        <x-button>{{ __('Hľadať') }}</x-button>
    </form>

    @if (mb_strlen($term) < 2)
        <p class="text-sm text-slate-500">{{ __('Zadajte aspoň 2 znaky.') }}</p>
    @elseif ($sections === [])
        <x-empty-state icon="question" :title="__('Nič sa nenašlo')" :description="__('Skúste iný výraz.')" />
    @else
        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($sections as $label => $results)
                <x-card :title="$label">
                    <ul class="flex flex-col divide-y divide-slate-100">
                        @foreach ($results as $result)
                            <li>
                                <a href="{{ $result['url'] }}" class="block py-2 hover:text-indigo-700">
                                    <span class="block text-sm font-medium">{{ $result['title'] }}</span>
                                    @if ($result['subtitle'])
                                        <span class="text-xs text-slate-500">{{ $result['subtitle'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endforeach
        </div>
    @endif
</x-layouts::app>
