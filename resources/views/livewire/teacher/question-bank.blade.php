<div>
    <x-page-header :title="__('Banka otázok')" :description="__('Vaše otázky, ktoré môžete použiť vo viacerých testoch.')">
        <x-slot:actions>
            <x-link-button :href="route('teacher.questions.create')"><x-icon name="plus" class="size-4" />{{ __('Nová otázka') }}</x-link-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <x-search-input wire:model.live.debounce.300ms="search" :label="__('Hľadať v znení otázky')" />

        @foreach ([
            ['type', __('Všetky typy'), \App\Enums\QuestionType::options()],
            ['topic', __('Všetky témy'), $topics],
            ['course', __('Všetky kurzy'), $courses],
            ['status', __('Všetky stavy'), ['approved' => __('Schválené'), 'draft' => __('Na kontrolu')]],
        ] as [$property, $placeholder, $choices])
            <div class="sm:w-48">
                <label for="filter-{{ $property }}" class="sr-only">{{ $placeholder }}</label>
                <select id="filter-{{ $property }}" wire:model.live="{{ $property }}" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs">
                    <option value="">{{ $placeholder }}</option>
                    @foreach ($choices as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
    </div>

    @if ($questions->isEmpty())
        <x-empty-state icon="question" :title="__('Žiadne otázky')" :description="__('Vytvorte otázku ručne. Neskôr ich bude možné generovať aj zo študijných materiálov.')">
            <x-link-button :href="route('teacher.questions.create')">{{ __('Vytvoriť otázku') }}</x-link-button>
        </x-empty-state>
    @else
        <x-table.wrapper wire:loading.class="opacity-60">
            <thead class="bg-slate-50">
                <tr>
                    <x-table.th>{{ __('Otázka') }}</x-table.th>
                    <x-table.th>{{ __('Typ') }}</x-table.th>
                    <x-table.th class="text-right">{{ __('Body') }}</x-table.th>
                    <x-table.th class="text-right">{{ __('V testoch') }}</x-table.th>
                    <x-table.th><span class="sr-only">{{ __('Akcie') }}</span></x-table.th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($questions as $question)
                    <tr wire:key="question-{{ $question->id }}">
                        <td class="max-w-xl px-4 py-3">
                            <a href="{{ route('teacher.questions.edit', $question) }}" class="font-medium text-slate-900 hover:text-brand-700 hover:underline">
                                {{ \Illuminate\Support\Str::limit($question->body, 140) }}
                            </a>
                            <div class="mt-1 flex flex-wrap gap-1">
                                @if ($question->status === \App\Enums\QuestionStatus::Draft)
                                    <x-badge color="amber">{{ $question->status->label() }}</x-badge>
                                @endif
                                @foreach ($question->topics as $topic)
                                    <x-badge color="brand">{{ $topic->name }}</x-badge>
                                @endforeach
                                @if ($question->course)
                                    <x-badge>{{ $question->course->title }}</x-badge>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-700">{{ $question->type->label() }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ rtrim(rtrim((string) $question->default_points, '0'), '.') }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $question->quizzes_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3 whitespace-nowrap text-sm">
                                <button type="button" wire:click="duplicate({{ $question->id }})" class="font-medium text-slate-600 hover:underline">{{ __('Duplikovať') }}</button>
                                <button type="button" wire:click="delete({{ $question->id }})" wire:confirm="{{ __('Odstrániť otázku?') }}" class="font-medium text-red-700 hover:underline">{{ __('Odstrániť') }}</button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table.wrapper>

        <div class="mt-4">{{ $questions->links() }}</div>
    @endif
</div>
