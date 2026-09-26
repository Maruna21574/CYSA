@php
    $types = collect(\App\Enums\SchoolType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all();
@endphp

@csrf

<x-card class="flex flex-col gap-4">
    <x-form.input name="name" :label="__('Názov školy')" :value="$school->name" required maxlength="255" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-form.select name="type" :label="__('Typ školy')" :options="$types" :selected="$school->type" required />
        <x-form.input name="city" :label="__('Mesto')" :value="$school->city" maxlength="255" />
    </div>

    <x-form.input name="slug" :label="__('Skratka v URL')" :value="$school->slug" :hint="__('Nepovinné – vytvorí sa automaticky z názvu.')" maxlength="255" />

    <x-form.checkbox name="is_active" :label="__('Škola je aktívna')" :hint="__('Používatelia neaktívnej školy sa nemôžu prihlásiť.')" :checked="$school->is_active" />
</x-card>
