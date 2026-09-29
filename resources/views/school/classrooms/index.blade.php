<x-layouts::app :title="__('Triedy')">
    <x-page-header :title="__('Triedy')" :description="__('Triedy školy a ich učitelia a študenti.')">
        <x-slot:actions>
            <x-link-button :href="route('school.classrooms.create')">{{ __('Nová trieda') }}</x-link-button>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('school.classrooms.index') }}" class="mb-4 flex gap-2" role="search">
        <x-search-input name="q" :value="$search" :label="__('Hľadať triedu')" />
        <x-button variant="secondary">{{ __('Hľadať') }}</x-button>
    </form>

    @if ($classrooms->isEmpty())
        <x-empty-state icon="squares" :title="__('Žiadne triedy')" :description="$search ? __('Skúste upraviť hľadaný výraz.') : __('Vytvorte prvú triedu, napríklad 4.A.')" />
    @else
        <x-table.wrapper>
            <thead class="bg-slate-50">
                <tr>
                    <x-table.th>{{ __('Trieda') }}</x-table.th>
                    <x-table.th>{{ __('Školský rok') }}</x-table.th>
                    <x-table.th class="text-right">{{ __('Učitelia') }}</x-table.th>
                    <x-table.th class="text-right">{{ __('Študenti') }}</x-table.th>
                    <x-table.th><span class="sr-only">{{ __('Akcie') }}</span></x-table.th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($classrooms as $classroom)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('school.classrooms.show', $classroom) }}" class="font-medium text-slate-900 hover:text-brand-700 hover:underline">{{ $classroom->name }}</a>
                            @if ($classroom->grade_level)
                                <span class="block text-xs text-slate-500">{{ __(':grade. ročník', ['grade' => $classroom->grade_level]) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $classroom->school_year }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $classroom->teachers_count }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $classroom->students_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('school.classrooms.show', $classroom) }}" class="font-medium text-brand-700 hover:underline">
                                {{ __('Otvoriť') }}<span class="sr-only"> {{ $classroom->name }}</span>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table.wrapper>

        <div class="mt-4">{{ $classrooms->links() }}</div>
    @endif
</x-layouts::app>
