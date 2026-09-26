<div class="flex flex-col gap-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
        <x-search-input wire:model.live.debounce.300ms="search" :label="__('Hľadať používateľa')" :placeholder="__('Meno, priezvisko alebo e-mail…')" />

        <div class="sm:w-44">
            <label for="filter-role" class="sr-only">{{ __('Rola') }}</label>
            <select id="filter-role" wire:model.live="role" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/30">
                <option value="">{{ __('Všetky roly') }}</option>
                @foreach ($this->roleOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        @if ($this->schoolOptions)
            <div class="sm:w-56">
                <label for="filter-school" class="sr-only">{{ __('Škola') }}</label>
                <select id="filter-school" wire:model.live="schoolId" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/30">
                    <option value="">{{ __('Všetky školy') }}</option>
                    @foreach ($this->schoolOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="sm:w-40">
            <label for="filter-status" class="sr-only">{{ __('Stav') }}</label>
            <select id="filter-status" wire:model.live="status" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/30">
                <option value="">{{ __('Všetky stavy') }}</option>
                <option value="active">{{ __('Aktívne') }}</option>
                <option value="inactive">{{ __('Deaktivované') }}</option>
            </select>
        </div>
    </div>

    @if ($users->isEmpty())
        <x-empty-state icon="users" :title="__('Žiadni používatelia')" :description="__('Skúste upraviť filtre alebo pridajte nového používateľa.')" />
    @else
        <x-table.wrapper wire:loading.class="opacity-60">
            <thead class="bg-slate-50">
                <tr>
                    <x-table.th>{{ __('Meno') }}</x-table.th>
                    <x-table.th>{{ __('Rola') }}</x-table.th>
                    @if ($this->schoolOptions)
                        <x-table.th>{{ __('Škola') }}</x-table.th>
                    @endif
                    <x-table.th>{{ __('Posledné prihlásenie') }}</x-table.th>
                    <x-table.th>{{ __('Stav') }}</x-table.th>
                    <x-table.th><span class="sr-only">{{ __('Akcie') }}</span></x-table.th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-3">
                            <span class="block font-medium text-slate-900">{{ $user->name }}</span>
                            <span class="text-slate-500">{{ $user->email }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <x-badge :color="$user->role->canTeach() ? 'indigo' : 'slate'">{{ $user->role->label() }}</x-badge>
                        </td>
                        @if ($this->schoolOptions)
                            <td class="px-4 py-3 text-slate-700">{{ $user->school?->name ?? '—' }}</td>
                        @endif
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                            {{ $user->last_login_at?->translatedFormat('j. n. Y H:i') ?? __('nikdy') }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($user->is_active)
                                <x-badge color="green">{{ __('Aktívny') }}</x-badge>
                            @else
                                <x-badge color="red">{{ __('Deaktivovaný') }}</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3 whitespace-nowrap">
                                @unless ($user->is(auth()->user()))
                                    <button type="button" wire:click="toggleActive({{ $user->id }})" class="font-medium text-slate-600 hover:text-slate-900 hover:underline">
                                        {{ $user->is_active ? __('Deaktivovať') : __('Aktivovať') }}<span class="sr-only"> {{ $user->name }}</span>
                                    </button>
                                @endunless
                                <a href="{{ route('users.edit', $user) }}" class="font-medium text-indigo-700 hover:underline">
                                    {{ __('Upraviť') }}<span class="sr-only"> {{ $user->name }}</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table.wrapper>

        {{ $users->links() }}
    @endif
</div>
