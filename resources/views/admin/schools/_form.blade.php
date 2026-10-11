@php
    $types = collect(\App\Enums\SchoolType::schoolTypes())->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all();
@endphp

@csrf

<x-card class="flex flex-col gap-4">
    <x-form.input name="name" :label="$companies ? __('Názov organizácie') : __('Názov školy')" :value="$school->name" required maxlength="255" />

    @if ($companies)
        <input type="hidden" name="type" value="{{ \App\Enums\SchoolType::Company->value }}">
        <x-form.input name="city" :label="__('Mesto')" :value="$school->city" maxlength="255" />
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.select name="type" :label="__('Typ školy')" :options="$types" :selected="$school->type" required />
            <x-form.input name="city" :label="__('Mesto')" :value="$school->city" maxlength="255" />
        </div>
    @endif

    <x-form.input name="slug" :label="__('Skratka v URL')" :value="$school->slug" :hint="__('Nepovinné – vytvorí sa automaticky z názvu.')" maxlength="255" />

    <x-form.checkbox name="is_active" :label="$companies ? __('Organizácia je aktívna') : __('Škola je aktívna')" :hint="$companies ? __('Používatelia neaktívnej organizácie sa nemôžu prihlásiť.') : __('Používatelia neaktívnej školy sa nemôžu prihlásiť.')" :checked="$school->is_active" />
</x-card>
