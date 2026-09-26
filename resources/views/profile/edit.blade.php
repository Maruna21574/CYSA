<x-layouts::app :title="__('Môj profil')">
    <x-page-header :title="__('Môj profil')" />

    <div class="flex max-w-2xl flex-col gap-6">
        <x-card :title="__('Údaje o účte')">
            <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
                <dt class="text-slate-500">{{ __('Meno') }}</dt><dd class="font-medium">{{ $user->name }}</dd>
                <dt class="text-slate-500">{{ __('E-mail') }}</dt><dd class="font-medium">{{ $user->email }}</dd>
                <dt class="text-slate-500">{{ __('Rola') }}</dt><dd class="font-medium">{{ $user->role->label() }}</dd>
                @if ($user->school)
                    <dt class="text-slate-500">{{ __('Škola') }}</dt><dd class="font-medium">{{ $user->school->name }}</dd>
                @endif
            </dl>
            <p class="mt-3 text-xs text-slate-500">{{ __('Meno a e-mail spravuje administrátor školy.') }}</p>
        </x-card>

        <form method="POST" action="{{ route('profile.preferences') }}">
            @csrf
            @method('PUT')
            <x-card :title="__('Upozornenia')" class="flex flex-col gap-4">
                <x-form.checkbox name="email_notifications" :checked="$user->email_notifications"
                    :label="__('Posielať upozornenia aj e-mailom')"
                    :hint="__('Nový kurz, nový test, blížiaci sa termín, certifikát a oznámenia učiteľa. V aplikácii ich uvidíte vždy.')" />
                <div><x-button>{{ __('Uložiť') }}</x-button></div>
            </x-card>
        </form>

        <form method="POST" action="{{ route('profile.password') }}">
            @csrf
            @method('PUT')
            <x-card :title="__('Zmena hesla')" class="flex flex-col gap-4">
                <x-form.input name="current_password" type="password" :label="__('Súčasné heslo')" autocomplete="current-password" required />
                <x-form.input name="password" type="password" :label="__('Nové heslo')" :hint="__('Aspoň 8 znakov, písmená aj číslice.')" autocomplete="new-password" required />
                <x-form.input name="password_confirmation" type="password" :label="__('Zopakujte nové heslo')" autocomplete="new-password" required />
                <div><x-button>{{ __('Zmeniť heslo') }}</x-button></div>
            </x-card>
        </form>
    </div>
</x-layouts::app>
