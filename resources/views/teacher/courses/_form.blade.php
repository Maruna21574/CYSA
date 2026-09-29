@csrf

<x-card class="flex flex-col gap-4">
    <x-form.input name="title" :label="__('Názov kurzu')" :value="$course->title" required maxlength="255" />

    <x-form.textarea name="description" :label="__('Popis')" :value="$course->description" rows="4" maxlength="5000"
        :hint="__('Krátko opíšte, čo sa študenti v kurze naučia.')" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-form.select name="category_id" :label="__('Kategória')" :options="$categories" :selected="$course->category_id" :placeholder="__('— bez kategórie —')" />
        <x-form.select name="difficulty" :label="__('Obtiažnosť')" :options="\App\Enums\Difficulty::options()" :selected="$course->difficulty ?? \App\Enums\Difficulty::Beginner" required />
    </div>

    <x-form.checkbox name="sequential_chapters" :checked="$course->sequential_chapters"
        :label="__('Kapitoly sa musia absolvovať postupne')"
        :hint="__('Študent otvorí ďalšiu kapitolu až po dokončení predchádzajúcej.')" />
</x-card>

<x-card :title="__('Obrázok kurzu')" class="mt-4 flex flex-col gap-4">
    @if ($course->cover_path)
        <x-course-cover :course="$course" class="h-32 w-56 rounded-lg" />
        <x-form.checkbox name="remove_cover" :label="__('Odstrániť obrázok')" />
    @endif

    <x-form.input name="cover" type="file" :label="$course->cover_path ? __('Nahradiť obrázok') : __('Nahrať obrázok')" accept=".jpg,.jpeg,.png,.webp"
        :hint="__('JPG, PNG alebo WebP, najviac 5 MB.')"
        class="file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-700" />
</x-card>
