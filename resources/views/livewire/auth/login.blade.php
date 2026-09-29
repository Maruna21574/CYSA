<div>
    <h1 class="text-xl font-semibold text-slate-900">{{ __('Prihlásenie') }}</h1>
    <p class="mt-1 text-sm text-slate-600">{{ __('Prihlás sa účtom, ktorý ti vytvorila škola.') }}</p>

    <form wire:submit="login" class="mt-6 flex flex-col gap-4" novalidate>
        <x-form.input name="email" type="email" :label="__('E-mail')" wire:model="email" autocomplete="username" required autofocus />

        <x-form.input name="password" type="password" :label="__('Heslo')" wire:model="password" autocomplete="current-password" required />

        <div class="flex items-center justify-between gap-4">
            <label for="remember" class="flex items-center gap-2 text-sm text-slate-700">
                <input id="remember" type="checkbox" wire:model="remember" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                {{ __('Zapamätať si ma') }}
            </label>

            <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-700 hover:underline">{{ __('Zabudnuté heslo?') }}</a>
        </div>

        <x-button class="w-full">
            <span wire:loading.remove wire:target="login">{{ __('Prihlásiť sa') }}</span>
            <span wire:loading wire:target="login">{{ __('Prihlasujem…') }}</span>
        </x-button>
    </form>
</div>
