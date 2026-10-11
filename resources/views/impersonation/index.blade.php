<x-layouts::app :title="__('Prepnúť účet')">
    <x-page-header
        :title="__('Prepnúť účet')"
        :description="__('Pozrite sa na aplikáciu očami učiteľa, študenta alebo administrátora. Späť sa vrátite tlačidlom „Vrátiť sa na môj účet“ v hornej lište.')"
    />

    <form method="GET" action="{{ route('impersonation.index') }}" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end" role="search">
        <div class="sm:w-80">
            <label for="switch-search" class="sr-only">{{ __('Hľadať používateľa') }}</label>
            <input id="switch-search" name="q" type="search" value="{{ $term }}" maxlength="100" placeholder="{{ __('Meno, priezvisko alebo e-mail…') }}"
                   class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-xs focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30">
        </div>
        <div class="sm:w-56">
            <label for="switch-role" class="sr-only">{{ __('Rola') }}</label>
            <select id="switch-role" name="role" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30">
                <option value="">{{ __('Všetky roly') }}</option>
                @foreach ($roles as $option)
                    <option value="{{ $option->value }}" @selected($role === $option)>{{ $realUser->isSuperAdmin() ? $option->genericLabel() : $option->label($realUser->school) }}</option>
                @endforeach
            </select>
        </div>
        <x-button variant="secondary">{{ __('Filtrovať') }}</x-button>
    </form>

    @if ($users->isEmpty())
        <x-empty-state icon="users" :title="__('Žiadne účty na prepnutie')" :description="__('Nenašli sa žiadne aktívne účty, na ktoré sa môžete prepnúť.')" />
    @else
        <x-table.wrapper>
            <thead class="bg-slate-50">
                <tr>
                    <x-table.th>{{ __('Meno') }}</x-table.th>
                    <x-table.th>{{ __('Rola') }}</x-table.th>
                    <x-table.th>{{ __('Škola / organizácia') }}</x-table.th>
                    <x-table.th><span class="sr-only">{{ __('Akcie') }}</span></x-table.th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="block font-medium text-slate-900">{{ $user->name }}</span>
                            <span class="text-slate-500">{{ $user->email }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <x-badge :color="$user->role->canTeach() ? 'brand' : 'slate'">{{ $user->role->label($user->school) }}</x-badge>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $user->school?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($user->is(auth()->user()))
                                <span class="text-sm text-slate-500">{{ __('Práve prihlásený') }}</span>
                            @else
                                <form method="POST" action="{{ route('impersonation.start', $user) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 font-medium whitespace-nowrap text-brand-700 hover:underline">
                                        <x-icon name="switch" class="size-4" />
                                        {{ __('Prepnúť') }}<span class="sr-only"> {{ __('na účet') }} {{ $user->name }}</span>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table.wrapper>

        <div class="mt-4">{{ $users->links() }}</div>
    @endif
</x-layouts::app>
