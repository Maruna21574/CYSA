<div>
    <h1 class="text-xl font-semibold text-slate-900">{{ __('Zabudnuté heslo') }}</h1>
    <p class="mt-1 text-sm text-slate-600">{{ __('Zadaj e-mail svojho účtu a pošleme ti odkaz na nastavenie nového hesla.') }}</p>

    @if (session('status'))
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="sendResetLink" class="mt-6 flex flex-col gap-4" novalidate>
        <x-form.input name="email" type="email" :label="__('E-mail')" wire:model="email" autocomplete="email" required autofocus />

        <x-button class="w-full">{{ __('Poslať odkaz') }}</x-button>
    </form>

    <p class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" class="font-medium text-brand-700 hover:underline">{{ __('Späť na prihlásenie') }}</a>
    </p>
</div>
