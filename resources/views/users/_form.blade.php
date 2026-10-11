@csrf

<x-card class="flex flex-col gap-4">
    <div class="grid gap-4 sm:grid-cols-2">
        <x-form.input name="first_name" :label="__('Meno')" :value="$user->first_name" required maxlength="100" autocomplete="off" />
        <x-form.input name="last_name" :label="__('Priezvisko')" :value="$user->last_name" required maxlength="100" autocomplete="off" />
    </div>

    <x-form.input name="email" type="email" :label="__('E-mail')" :value="$user->email" required maxlength="255" autocomplete="off" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-form.select name="role" :label="__('Rola')" :options="$roles" :selected="$user->role" required />

        @if ($organization)
            {{-- Only the super admin chooses the organization; everyone else is bound to their own on the server. --}}
            @if (auth()->user()->isSuperAdmin())
                <input type="hidden" name="school_id" value="{{ $organization->id }}">
            @endif
            <div>
                <span class="mb-1 block text-sm font-medium text-slate-700">{{ __('Organizácia') }}</span>
                <p class="rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700">{{ $organization->name }}</p>
            </div>
        @elseif ($schools)
            <x-form.select name="school_id" :label="__('Škola / organizácia')" :options="$schools" :selected="$user->school_id" :placeholder="__('— bez školy (iba super admin) —')" />
        @endif
    </div>
</x-card>

<x-card :title="__('Prihlasovanie')" class="mt-4 flex flex-col gap-4">
    <x-form.input
        name="password"
        type="password"
        :label="$user->exists ? __('Nové heslo') : __('Heslo')"
        :hint="$user->exists
            ? __('Vyplňte iba ak chcete heslo zmeniť. Aspoň 8 znakov, písmená aj číslice.')
            : __('Nepovinné. Ak heslo nezadáte, používateľovi pošleme e-mailom pozvánku, v ktorej si ho nastaví sám.')"
        autocomplete="new-password"
    />

    @unless ($user->exists)
        <x-form.checkbox name="send_invitation" :label="__('Poslať pozvánku e-mailom aj pri zadanom hesle')" />
    @endunless
</x-card>
