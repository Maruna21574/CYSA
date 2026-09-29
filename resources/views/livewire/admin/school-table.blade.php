<div class="flex flex-col gap-4">
    <x-search-input wire:model.live.debounce.300ms="search" :label="__('Hľadať školu')" :placeholder="__('Názov alebo mesto…')" />

    @if ($schools->isEmpty())
        <x-empty-state icon="building" :title="__('Žiadne školy')" :description="$search ? __('Skúste upraviť hľadaný výraz.') : __('Zatiaľ nebola vytvorená žiadna škola.')" />
    @else
        <x-table.wrapper wire:loading.class="opacity-60">
            <thead class="bg-slate-50">
                <tr>
                    <x-table.th>{{ __('Škola') }}</x-table.th>
                    <x-table.th>{{ __('Typ') }}</x-table.th>
                    <x-table.th class="text-right">{{ __('Používatelia') }}</x-table.th>
                    <x-table.th class="text-right">{{ __('Triedy') }}</x-table.th>
                    <x-table.th>{{ __('Stav') }}</x-table.th>
                    <x-table.th><span class="sr-only">{{ __('Akcie') }}</span></x-table.th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($schools as $school)
                    <tr wire:key="school-{{ $school->id }}">
                        <td class="px-4 py-3">
                            <span class="block font-medium text-slate-900">{{ $school->name }}</span>
                            <span class="text-slate-500">{{ $school->city }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $school->type->label() }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $school->users_count }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $school->classrooms_count }}</td>
                        <td class="px-4 py-3">
                            @if ($school->is_active)
                                <x-badge color="green">{{ __('Aktívna') }}</x-badge>
                            @else
                                <x-badge color="red">{{ __('Neaktívna') }}</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.schools.edit', $school) }}" class="font-medium text-brand-700 hover:underline">
                                {{ __('Upraviť') }}<span class="sr-only"> {{ $school->name }}</span>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table.wrapper>

        {{ $schools->links() }}
    @endif
</div>
