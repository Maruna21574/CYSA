<div>
    <x-page-header
        :title="$questionId ? __('Úprava otázky') : __('Nová otázka')"
        :breadcrumbs="$quiz
            ? [['label' => __('Testy'), 'url' => route('teacher.quizzes.index')], ['label' => $quiz->title, 'url' => route('teacher.quizzes.show', $quiz)], ['label' => __('Otázka')]]
            : [['label' => __('Banka otázok'), 'url' => route('teacher.questions.index')], ['label' => $questionId ? __('Úprava') : __('Nová otázka')]]"
    />

    @if ($isAiDraft)
        <div class="mb-4 max-w-5xl rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="note">
            <p class="flex items-center gap-2 font-semibold"><x-icon name="sparkles" class="size-4" />{{ __('Návrh umelej inteligencie – čaká na vašu kontrolu') }}</p>
            <p class="mt-1">{{ __('Overte správnosť otázky aj odpovedí. Otázku použijete v teste až po schválení.') }}</p>
        </div>
    @endif

    <form wire:submit="save" class="grid max-w-5xl gap-6 lg:grid-cols-3">
        <div class="flex flex-col gap-6 lg:col-span-2">
            <x-card class="flex flex-col gap-4">
                <x-form.select name="type" id="question-type" :label="__('Typ otázky')" :options="\App\Enums\QuestionType::options()" wire:model.live="type" :hint="$currentType->hint()" />

                <x-form.textarea name="body" id="question-body" :label="__('Znenie otázky')" wire:model.live.debounce.500ms="body" rows="4" maxlength="5000"
                    :hint="$currentType === \App\Enums\QuestionType::FillBlank ? __('Príklad: Silné heslo má aspoň [[1]] znakov a pre každý účet je [[2]].') : null" />
            </x-card>

            <x-card :title="match ($currentType) {
                \App\Enums\QuestionType::ShortAnswer => __('Akceptované odpovede'),
                \App\Enums\QuestionType::FillBlank => __('Odpovede do medzier'),
                \App\Enums\QuestionType::Matching => __('Dvojice na priradenie'),
                \App\Enums\QuestionType::TrueFalse => __('Správna odpoveď'),
                default => __('Možnosti odpovede'),
            }">
                @error('options') <p class="mb-3 rounded-lg bg-red-50 p-2 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror

                @if ($currentType === \App\Enums\QuestionType::TrueFalse)
                    <div class="flex flex-col gap-2 sm:flex-row" role="radiogroup" aria-label="{{ __('Správna odpoveď') }}">
                        @foreach ($options as $index => $option)
                            <label wire:key="tf-{{ $index }}" class="flex flex-1 cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 has-checked:border-indigo-600 has-checked:bg-indigo-50">
                                <input type="radio" name="tf-correct" wire:click="markCorrect({{ $index }})" @checked($option['is_correct']) class="size-4 text-indigo-600">
                                <span class="text-sm font-medium">{{ __('Tvrdenie je :value', ['value' => mb_strtolower($option['body'])]) }}</span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <ul class="flex flex-col gap-3">
                        @foreach ($options as $index => $option)
                            <li wire:key="option-{{ $index }}-{{ $option['id'] ?? 'new' }}" class="flex items-start gap-3">
                                @if ($currentType === \App\Enums\QuestionType::SingleChoice)
                                    <input type="radio" name="single-correct" wire:click="markCorrect({{ $index }})" @checked($option['is_correct'])
                                           class="mt-2.5 size-4 text-indigo-600" aria-label="{{ __('Správna odpoveď') }}">
                                @elseif ($currentType === \App\Enums\QuestionType::MultipleChoice)
                                    <input type="checkbox" wire:model="options.{{ $index }}.is_correct"
                                           class="mt-2.5 size-4 rounded text-indigo-600" aria-label="{{ __('Správna odpoveď') }}">
                                @elseif ($currentType === \App\Enums\QuestionType::FillBlank)
                                    <div class="w-24 shrink-0">
                                        <label for="blank-{{ $index }}" class="sr-only">{{ __('Medzera') }}</label>
                                        <select id="blank-{{ $index }}" wire:model="options.{{ $index }}.blank_index" class="block w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                            @foreach ($this->blankIndexes ?: [1] as $blank)
                                                <option value="{{ $blank }}">[[{{ $blank }}]]</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div class="flex flex-1 flex-col gap-2 sm:flex-row">
                                    <div class="flex-1">
                                        <label for="option-{{ $index }}" class="sr-only">{{ $currentType === \App\Enums\QuestionType::Matching ? __('Položka vľavo') : __('Možnosť :n', ['n' => $index + 1]) }}</label>
                                        <input id="option-{{ $index }}" type="text" wire:model="options.{{ $index }}.body" maxlength="1000"
                                               placeholder="{{ match ($currentType) {
                                                   \App\Enums\QuestionType::ShortAnswer, \App\Enums\QuestionType::FillBlank => __('Akceptovaná odpoveď'),
                                                   \App\Enums\QuestionType::Matching => __('Položka vľavo, napr. Phishing'),
                                                   default => __('Možnosť :n', ['n' => $index + 1]),
                                               } }}"
                                               class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/30">
                                        @error("options.$index.body") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    @if ($currentType === \App\Enums\QuestionType::Matching)
                                        <div class="flex-1">
                                            <label for="match-{{ $index }}" class="sr-only">{{ __('Položka vpravo') }}</label>
                                            <input id="match-{{ $index }}" type="text" wire:model="options.{{ $index }}.match_body" maxlength="1000" placeholder="{{ __('Položka vpravo, napr. Podvodný e-mail') }}"
                                                   class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/30">
                                        </div>
                                    @endif
                                </div>

                                <button type="button" wire:click="removeOption({{ $index }})" class="mt-1.5 rounded p-1 text-slate-400 hover:text-red-700">
                                    <x-icon name="trash" class="size-4" /><span class="sr-only">{{ __('Odstrániť možnosť') }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    @if (count($options) < \App\Services\Questions\QuestionValidator::MAX_OPTIONS)
                        <button type="button" wire:click="addOption" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-indigo-700 hover:underline">
                            <x-icon name="plus" class="size-4" />
                            {{ match ($currentType) {
                                \App\Enums\QuestionType::ShortAnswer, \App\Enums\QuestionType::FillBlank => __('Pridať akceptovanú odpoveď'),
                                \App\Enums\QuestionType::Matching => __('Pridať dvojicu'),
                                default => __('Pridať možnosť'),
                            } }}
                        </button>
                    @endif

                    @if (in_array($currentType, [\App\Enums\QuestionType::ShortAnswer, \App\Enums\QuestionType::FillBlank], true))
                        <div class="mt-4"><x-form.checkbox name="caseSensitive" id="case-sensitive" wire:model="caseSensitive" :label="__('Rozlišovať veľké a malé písmená')" /></div>
                    @endif
                    @if (in_array($currentType, [\App\Enums\QuestionType::MultipleChoice, \App\Enums\QuestionType::FillBlank, \App\Enums\QuestionType::Matching], true))
                        <div class="mt-3"><x-form.checkbox name="partialCredit" id="partial-credit" wire:model="partialCredit" :label="__('Čiastočné bodovanie')" :hint="__('Študent dostane pomernú časť bodov za čiastočne správnu odpoveď.')" /></div>
                    @endif
                @endif
            </x-card>

            <x-card>
                <x-form.textarea name="explanation" id="question-explanation" :label="__('Vysvetlenie (zobrazí sa po vyhodnotení)')" wire:model="explanation" rows="3" maxlength="5000" />
                @if ($aiAvailable)
                    <button type="button" wire:click="suggestExplanation" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-indigo-700 hover:underline">
                        <x-icon name="sparkles" class="size-4" />
                        <span wire:loading.remove wire:target="suggestExplanation">{{ __('Navrhnúť vysvetlenie pomocou AI') }}</span>
                        <span wire:loading wire:target="suggestExplanation">{{ __('AI premýšľa…') }}</span>
                    </button>
                @endif
            </x-card>
        </div>

        <div class="flex flex-col gap-6">
            <x-card class="flex flex-col gap-4">
                <x-form.input name="defaultPoints" id="points" type="number" step="0.5" min="0" max="1000" :label="__('Body')" wire:model="defaultPoints" />
                <x-form.select name="difficulty" id="difficulty" :label="__('Obtiažnosť')" :options="\App\Enums\Difficulty::options()" :placeholder="__('— neurčená —')" wire:model="difficulty" />
                <x-form.select name="courseId" id="course" :label="__('Kurz')" :options="$courses" :placeholder="__('— žiadny —')" wire:model.live="courseId" />
                @if ($chapters)
                    <x-form.select name="chapterId" id="chapter" :label="__('Kapitola')" :options="$chapters" :placeholder="__('— žiadna —')" wire:model="chapterId" />
                @endif
            </x-card>

            <x-card :title="__('Témy')">
                <p class="mb-2 text-xs text-slate-500">{{ __('Témy slúžia na vyhodnotenie, v ktorých oblastiach majú študenti problémy.') }}</p>
                <div class="flex flex-col gap-2">
                    @foreach ($topics as $id => $name)
                        <label wire:key="topic-{{ $id }}" class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="topicIds" value="{{ $id }}" class="size-4 rounded text-indigo-600">{{ $name }}
                        </label>
                    @endforeach
                </div>
                <div class="mt-4">
                    <x-form.input name="tags" id="tags" :label="__('Tagy')" wire:model="tags" :hint="__('Oddeľte čiarkou, napr. 2FA, správca hesiel')" maxlength="500" />
                </div>
            </x-card>

            <x-card :title="__('Obrázok')" class="flex flex-col gap-3">
                @if ($currentImage && ! $removeImage)
                    <img src="{{ route('questions.image', $questionId) }}" alt="" class="max-h-40 rounded-lg">
                    <x-form.checkbox name="removeImage" id="remove-image" wire:model.live="removeImage" :label="__('Odstrániť obrázok')" />
                @endif
                <div>
                    <label for="question-image" class="sr-only">{{ __('Obrázok') }}</label>
                    <input id="question-image" type="file" wire:model="image" accept=".jpg,.jpeg,.png,.webp"
                           class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-700">
                    @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </x-card>

            <div class="flex flex-col gap-2">
                @if ($isAiDraft)
                    <x-button type="button" wire:click="saveAndApprove"><x-icon name="check" class="size-4" />{{ __('Uložiť a schváliť') }}</x-button>
                    <x-button variant="secondary">{{ __('Uložiť bez schválenia') }}</x-button>
                @else
                    <x-button>{{ __('Uložiť otázku') }}</x-button>
                @endif
                <x-link-button variant="secondary" :href="match (true) { $aiGenerationId !== null => route('teacher.ai.show', $aiGenerationId), $quiz !== null => route('teacher.quizzes.show', $quiz), default => route('teacher.questions.index') }">{{ __('Zrušiť') }}</x-link-button>
            </div>
        </div>
    </form>
</div>
