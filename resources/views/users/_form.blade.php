@csrf

<x-card class="flex flex-col gap-4">
    <div class="grid gap-4 sm:grid-cols-2">
        <x-form.input name="first_name" :label="__('Meno')" :value="$user->first_name" required maxlength="100" autocomplete="off" />
        <x-form.input name="last_name" :label="__('Priezvisko')" :value="$user->last_name" required maxlength="100" autocomplete="off" />
    </div>

    <x-form.input name="email" type="email" :label="__('E-mail')" :value="$user->email" required maxlength="255" autocomplete="off" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-form.select name="role" :label="__('Rola')" :options="$roles" :selected="$user->role" required />

        @if ($schools)
            <x-form.select name="school_id" :label="__('Škola')" :options="$schools" :selected="$user->school_id" :placeholder="__('— bez školy (iba super admin) —')" />
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
