<x-layouts::app :title="__('Generovať otázky pomocou AI')">
    <x-page-header :title="__('Generovať otázky pomocou AI')" :breadcrumbs="[
        ['label' => __('Kurzy'), 'url' => route('teacher.courses.index')],
        ['label' => $course->title, 'url' => route('teacher.courses.show', $course)],
        ['label' => __('AI návrhy otázok')],
    ]" />

    <div class="grid max-w-5xl gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('teacher.ai.store', array_filter(['material' => $material?->id, 'chapter' => $material ? null : $chapter?->id])) }}" class="flex flex-col gap-4 lg:col-span-2">
            @csrf

            <x-card class="flex flex-col gap-4">
                <p class="text-sm text-slate-700">
                    {{ __('Zdroj:') }} <span class="font-semibold">{{ $material?->title ?? __('text kapitoly „:title“', ['title' => $chapter->title]) }}</span>
                </p>

                <x-form.input name="count" type="number" min="1" :max="config('cysa.ai.max_questions')" :value="5" :label="__('Počet otázok')" required class="max-w-32" />

                <fieldset>
                    <legend class="text-sm font-medium text-slate-700">{{ __('Typy otázok') }}</legend>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach (\App\Enums\QuestionType::cases() as $type)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="types[]" value="{{ $type->value }}" class="size-4 rounded text-indigo-600"
                                       @checked(in_array($type->value, old('types', ['single_choice', 'multiple_choice', 'true_false']), true))>
                                {{ $type->label() }}
                            </label>
                        @endforeach
                    </div>
                    @error('types') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </fieldset>

                <x-form.select name="difficulty" :label="__('Obtiažnosť')" :options="\App\Enums\Difficulty::options()" :placeholder="__('— podľa AI —')" />
            </x-card>

            <div><x-button><x-icon name="sparkles" class="size-4" />{{ __('Vygenerovať návrhy') }}</x-button></div>
        </form>

        <x-card :title="__('Ako to funguje')" class="h-fit text-sm text-slate-700">
            <ol class="list-decimal space-y-2 pl-5">
                <li>{{ __('Text materiálu sa odošle AI službe. Neposielajú sa žiadne údaje o študentoch.') }}</li>
                <li>{{ __('AI navrhne otázky – trvá to zvyčajne do jednej minúty.') }}</li>
                <li>{{ __('Návrhy sa uložia ako koncepty. Študenti ich neuvidia.') }}</li>
                <li>{{ __('Každú otázku skontrolujte, upravte alebo zamietnite a schváľte.') }}</li>
                <li>{{ __('Až schválené otázky môžete pridať do testu.') }}</li>
            </ol>
            <p class="mt-3 text-xs text-slate-500">{{ __('Dnes ste použili :used z :limit generovaní.', ['used' => $usedToday, 'limit' => config('cysa.ai.daily_limit_per_teacher')]) }}</p>
        </x-card>
    </div>
</x-layouts::app>
