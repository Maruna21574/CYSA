@csrf

<x-card class="flex flex-col gap-4">
    <x-form.input name="title" :label="__('Názov kapitoly')" :value="$chapter->title" required maxlength="255" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-form.select name="module_id" :label="__('Modul')" :options="$modules" :selected="$selectedModule" required />
        <x-form.input name="estimated_minutes" type="number" min="1" max="600" :label="__('Odhadovaný čas (minúty)')" :value="$chapter->estimated_minutes" />
    </div>

    <x-form.rich-editor name="content" :label="__('Text kapitoly')" :value="$chapter->content"
        :hint="__('Obrázky, dokumenty a videá pridáte nižšie ako materiály.')" />

    <x-form.checkbox name="requires_previous" :checked="$chapter->requires_previous"
        :label="__('Vyžaduje dokončenie predchádzajúcej kapitoly')" />
    <x-form.checkbox name="is_published" :checked="$chapter->is_published"
        :label="__('Kapitola je viditeľná pre študentov')" />
</x-card>
