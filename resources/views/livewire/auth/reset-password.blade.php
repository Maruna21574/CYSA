<div>
    <h1 class="text-xl font-semibold text-slate-900">{{ $isInvitation ? __('Aktivácia účtu') : __('Nastavenie nového hesla') }}</h1>

    @if ($isInvitation)
        <p class="mt-1 text-sm text-slate-600">{{ __('Nastav si heslo, ktorým sa budeš prihlasovať.') }}</p>
    @endif

    <form wire:submit="resetPassword" class="mt-6 flex flex-col gap-4" novalidate>
        <x-form.input name="email" type="email" :label="__('E-mail')" wire:model="email" autocomplete="username" required />

        <x-form.input
            name="password"
            type="password"
            :label="__('Nové heslo')"
            :hint="__('Aspoň 8 znakov, písmená aj číslice.')"
            wire:model="password"
            autocomplete="new-password"
            required
        />

        <x-form.input name="password_confirmation" type="password" :label="__('Zopakuj heslo')" wire:model="password_confirmation" autocomplete="new-password" required />

        <x-button class="w-full">{{ __('Uložiť heslo') }}</x-button>
    </form>
</div>
