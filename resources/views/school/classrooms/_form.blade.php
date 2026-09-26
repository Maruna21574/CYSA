@csrf

<x-card class="flex flex-col gap-4">
    <x-form.input name="name" :label="__('Názov triedy')" :value="$classroom->name" :hint="__('Napríklad 4.A alebo I.B')" required maxlength="50" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-form.input name="grade_level" type="number" :label="__('Ročník')" :value="$classroom->grade_level" min="1" max="13" />
        <x-form.input name="school_year" :label="__('Školský rok')" :value="$classroom->school_year" :hint="__('Formát 2026/2027')" required maxlength="9" />
    </div>
</x-card>
