<div class="grid gap-6 xl:grid-cols-5">
    <section class="xl:col-span-3" aria-labelledby="quiz-questions-heading">
        <div class="mb-3 flex items-center justify-between">
            <h2 id="quiz-questions-heading" class="text-lg font-semibold text-slate-900">
                {{ trans_choice('{0} Žiadne otázky|{1} :count otázka|[2,4] :count otázky|[5,*] :count otázok', $questions->count(), ['count' => $questions->count()]) }}
            </h2>
            <span class="text-sm text-slate-600">{{ __('Spolu :points b.', ['points' => rtrim(rtrim(number_format($totalPoints, 2, ',', ' '), '0'), ',')]) }}</span>
        </div>

        @if ($questions->isEmpty())
            <x-empty-state icon="question" :title="__('Test zatiaľ nemá otázky')" :description="__('Pridajte otázky z banky vpravo alebo vytvorte novú.')" />
        @else
            <ol wire:sort="sortQuestion" class="flex flex-col gap-2">
                @foreach ($questions as $question)
                    <li wire:key="quiz-question-{{ $question->id }}" wire:sort:item="{{ $question->id }}" class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-xs">
                        <button type="button" x-sort:handle class="mt-0.5 cursor-grab text-slate-300 hover:text-slate-500">
                            <x-icon name="drag" class="size-5" /><span class="sr-only">{{ __('Presunúť otázku') }}</span>
                        </button>
                        <span class="mt-0.5 w-6 shrink-0 text-sm font-semibold text-slate-400">{{ $loop->iteration }}.</span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('teacher.questions.edit', $question) }}" class="text-sm font-medium text-slate-900 hover:text-indigo-700 hover:underline">{{ \Illuminate\Support\Str::limit($question->body, 160) }}</a>
                            <div class="mt-1 flex flex-wrap gap-1">
                                <x-badge>{{ $question->type->label() }}</x-badge>
                                @foreach ($question->topics as $topic)
                                    <x-badge color="indigo">{{ $topic->name }}</x-badge>
                                @endforeach
                            </div>
                        </div>
                        <div class="w-20 shrink-0">
                            <label for="points-{{ $question->id }}" class="sr-only">{{ __('Body') }}</label>
                            <input id="points-{{ $question->id }}" type="number" min="0" max="1000" step="0.5"
                                   value="{{ $question->pivot->points !== null ? rtrim(rtrim((string) $question->pivot->points, '0'), '.') : '' }}"
                                   placeholder="{{ rtrim(rtrim((string) $question->default_points, '0'), '.') }}"
                                   wire:change="setPoints({{ $question->id }}, $event.target.value)"
                                   title="{{ __('Body v tomto teste (prázdne = predvolené)') }}"
                                   class="block w-full rounded-lg border border-slate-300 px-2 py-1 text-right text-sm">
                        </div>
                        <button type="button" wire:click="remove({{ $question->id }})" class="mt-0.5 rounded p-1 text-slate-400 hover:text-red-700">
                            <x-icon name="x" class="size-4" /><span class="sr-only">{{ __('Odobrať z testu') }}</span>
                        </button>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    <aside class="xl:col-span-2" aria-labelledby="bank-heading">
        <x-card>
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 id="bank-heading" class="text-base font-semibold text-slate-900">{{ __('Pridať z banky otázok') }}</h2>
                <a href="{{ route('teacher.questions.create', ['quiz' => $quiz->id]) }}" class="inline-flex items-center gap-1 text-sm font-medium text-indigo-700 hover:underline">
                    <x-icon name="plus" class="size-4" />{{ __('Nová otázka') }}
                </a>
            </div>

            <div class="flex flex-col gap-2">
                <x-search-input id="bank-search" wire:model.live.debounce.300ms="search" :label="__('Hľadať otázku')" />
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="bank-topic" class="sr-only">{{ __('Téma') }}</label>
                        <select id="bank-topic" wire:model.live="topic" class="block w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm">
                            <option value="">{{ __('Všetky témy') }}</option>
                            @foreach ($topics as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="bank-type" class="sr-only">{{ __('Typ') }}</label>
                        <select id="bank-type" wire:model.live="type" class="block w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm">
                            <option value="">{{ __('Všetky typy') }}</option>
                            @foreach (\App\Enums\QuestionType::options() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model.live="onlyThisCourse" class="size-4 rounded text-indigo-600">{{ __('Len otázky tohto kurzu') }}
                </label>
            </div>

            <ul class="mt-3 flex flex-col divide-y divide-slate-100" wire:loading.class="opacity-60">
                @forelse ($this->bank as $candidate)
                    <li wire:key="bank-{{ $candidate->id }}" class="flex items-start gap-2 py-2">
                        <div class="min-w-0 flex-1 text-sm">
                            <span class="block text-slate-800">{{ \Illuminate\Support\Str::limit($candidate->body, 120) }}</span>
                            <span class="text-xs text-slate-500">{{ $candidate->type->label() }}</span>
                        </div>
                        <button type="button" wire:click="add({{ $candidate->id }})" class="shrink-0 rounded-md bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                            {{ __('Pridať') }}
                        </button>
                    </li>
                @empty
                    <li class="py-3 text-sm text-slate-500">{{ __('Žiadne ďalšie otázky.') }}</li>
                @endforelse
            </ul>
        </x-card>
    </aside>
</div>
