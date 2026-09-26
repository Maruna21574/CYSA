@csrf

@php
    $dateValue = fn ($date) => $date?->format('Y-m-d\TH:i');
@endphp

<div class="flex flex-col gap-4" x-data="{ course: @js((string) old('course_id', $quiz->course_id)), chapters: @js($chapters), purpose: @js(old('purpose', $quiz->purpose?->value ?? 'practice')) }">
    <x-card class="flex flex-col gap-4">
        <x-form.input name="title" :label="__('Názov testu')" :value="$quiz->title" required maxlength="255" />
        <x-form.textarea name="description" :label="__('Pokyny pre študentov')" :value="$quiz->description" rows="3" maxlength="5000" />

        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.select name="course_id" :label="__('Kurz')" :options="$courses" :selected="$quiz->course_id" :placeholder="__('— vyberte kurz —')" required x-model="course" />

            <div>
                <label for="chapter_id" class="block text-sm font-medium text-slate-700">{{ __('Kapitola (nepovinné)') }}</label>
                <select id="chapter_id" name="chapter_id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs">
                    <option value="">{{ __('— test k celému kurzu —') }}</option>
                    <template x-for="(title, id) in (chapters[course] ?? {})" :key="id">
                        <option :value="id" x-text="title" :selected="id == @js((string) old('chapter_id', $quiz->chapter_id))"></option>
                    </template>
                </select>
                @error('chapter_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </x-card>

    <x-card :title="__('Typ testu')" class="flex flex-col gap-4">
        <x-form.select name="purpose" :label="__('Účel')" :options="\App\Enums\QuizPurpose::options()" :selected="$quiz->purpose" x-model="purpose"
            :hint="__('Vstupný a výstupný test slúžia na meranie zlepšenia – výsledky sa porovnávajú vo výskumnom exporte.')" />

        <div x-show="purpose === 'pretest' || purpose === 'posttest'" x-cloak>
            <x-form.select name="paired_quiz_id" :label="__('Párový test')" :options="$pairs" :selected="$quiz->paired_quiz_id" :placeholder="__('— zatiaľ bez páru —')"
                :hint="__('K vstupnému testu vyberte výstupný test toho istého kurzu (a naopak).')" />
        </div>
    </x-card>

    <x-card :title="__('Hodnotenie a pokusy')" class="grid gap-4 sm:grid-cols-3">
        <x-form.input name="pass_percentage" type="number" min="0" max="100" :label="__('Hranica úspešnosti (%)')" :value="$quiz->pass_percentage ?? 60" required />
        <x-form.input name="max_attempts" type="number" min="1" max="100" :label="__('Max. počet pokusov')" :value="$quiz->max_attempts" :hint="__('Prázdne = neobmedzene')" />
        <x-form.input name="time_limit_minutes" type="number" min="1" max="600" :label="__('Časový limit (min)')" :value="$quiz->time_limit_minutes" :hint="__('Prázdne = bez limitu')" />
    </x-card>

    <x-card :title="__('Dostupnosť')" class="grid gap-4 sm:grid-cols-2">
        <x-form.input name="available_from" type="datetime-local" :label="__('Dostupný od')" :value="$dateValue($quiz->available_from)" />
        <x-form.input name="due_at" type="datetime-local" :label="__('Termín (deadline)')" :value="$dateValue($quiz->due_at)" />
    </x-card>

    <x-card :title="__('Zobrazenie a poradie')" class="flex flex-col gap-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.select name="show_result" :label="__('Kedy študent uvidí výsledok')" :options="\App\Enums\ResultVisibility::options()" :selected="$quiz->show_result" />
            <x-form.select name="show_correct_answers" :label="__('Kedy uvidí správne odpovede')" :options="\App\Enums\ResultVisibility::options()" :selected="$quiz->show_correct_answers" />
        </div>
        <x-form.checkbox name="shuffle_questions" :checked="$quiz->shuffle_questions" :label="__('Náhodné poradie otázok')" />
        <x-form.checkbox name="shuffle_options" :checked="$quiz->shuffle_options" :label="__('Náhodné poradie odpovedí')" />
    </x-card>
</div>
