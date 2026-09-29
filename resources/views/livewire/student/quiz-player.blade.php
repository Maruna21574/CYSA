<div class="mx-auto flex max-w-3xl flex-col gap-5">
    <header class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xs sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ $attempt->quiz->title }}</h1>
            <p class="text-sm text-slate-600">
                {{ __('Pokus č. :n', ['n' => $attempt->attempt_number]) }} ·
                {{ __('Zodpovedané :done z :total', ['done' => $answeredCount, 'total' => $questions->count()]) }}
            </p>
        </div>

        @if ($secondsRemaining !== null)
            <div
                x-data="{
                    left: {{ $secondsRemaining }},
                    submitted: false,
                    init() {
                        const timer = setInterval(() => {
                            this.left = Math.max(0, this.left - 1);
                            if (this.left === 0 && ! this.submitted) { this.submitted = true; clearInterval(timer); $wire.submit(); }
                        }, 1000);
                    },
                    get label() {
                        const m = Math.floor(this.left / 60), s = this.left % 60;
                        return `${m}:${String(s).padStart(2, '0')}`;
                    },
                }"
                class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold tabular-nums"
                :class="left <= 60 ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-800'"
                role="timer" aria-live="off"
            >
                <x-icon name="clock" class="size-5" />
                <span x-text="label">{{ intdiv($secondsRemaining, 60) }}:{{ str_pad((string) ($secondsRemaining % 60), 2, '0', STR_PAD_LEFT) }}</span>
                <span class="sr-only">{{ __('zostávajúci čas') }}</span>
            </div>
        @endif
    </header>

    {{-- Question navigator --}}
    <nav aria-label="{{ __('Prehľad otázok') }}" class="flex flex-wrap gap-2">
        @foreach ($questions as $index => $item)
            @php $answered = $this->isAnswered($responses[$item['question_id']] ?? []); @endphp
            <button type="button" wire:click="goTo({{ $index }})" wire:key="nav-{{ $item['question_id'] }}"
                @if ($index === $current) aria-current="step" @endif
                @class([
                    'size-9 rounded-lg text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-brand-600',
                    'bg-brand-600 text-white' => $index === $current,
                    'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' => $index !== $current && $answered,
                    'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50' => $index !== $current && ! $answered,
                ])>
                {{ $index + 1 }}<span class="sr-only">{{ $answered ? __('(zodpovedaná)') : __('(nezodpovedaná)') }}</span>
            </button>
        @endforeach
    </nav>

    @if ($question)
        @php $qid = $question['question_id']; $type = $question['type']; @endphp

        <section wire:key="question-{{ $qid }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs sm:p-6" aria-labelledby="question-title-{{ $qid }}">
            <div class="mb-4 flex items-start justify-between gap-4">
                <p class="text-sm font-medium text-slate-500">{{ __('Otázka :n z :total', ['n' => $current + 1, 'total' => $questions->count()]) }} · {{ $type->label() }}</p>
                <x-badge>{{ trans_choice('{1} :count bod|[2,4] :count body|[0,*] :count bodov', (int) ceil($question['points']), ['count' => rtrim(rtrim(number_format($question['points'], 2, ',', ''), '0'), ',')]) }}</x-badge>
            </div>

            @if ($type !== \App\Enums\QuestionType::FillBlank)
                <h2 id="question-title-{{ $qid }}" class="text-lg font-semibold whitespace-pre-line text-slate-900">{{ $question['body'] }}</h2>
            @else
                <h2 id="question-title-{{ $qid }}" class="sr-only">{{ __('Doplň chýbajúce slová') }}</h2>
            @endif

            @if ($question['has_image'])
                <img src="{{ route('questions.image', $qid) }}" alt="{{ __('Obrázok k otázke') }}" class="mt-4 max-h-80 rounded-lg">
            @endif

            <div class="mt-5">
                @switch($type)
                    @case(\App\Enums\QuestionType::SingleChoice)
                    @case(\App\Enums\QuestionType::TrueFalse)
                        <fieldset>
                            <legend class="sr-only">{{ __('Vyber jednu odpoveď') }}</legend>
                            <div class="flex flex-col gap-2">
                                @foreach ($question['options'] as $option)
                                    <label wire:key="opt-{{ $option['id'] }}" class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 px-4 py-3 hover:bg-slate-50 has-checked:border-brand-600 has-checked:bg-brand-50">
                                        <input type="radio" name="q-{{ $qid }}" value="{{ $option['id'] }}" wire:model.live="responses.{{ $qid }}.selected.0" class="mt-0.5 size-4 text-brand-600">
                                        <span class="text-sm text-slate-800">{{ $option['body'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        @break

                    @case(\App\Enums\QuestionType::MultipleChoice)
                        <fieldset>
                            <legend class="mb-2 text-sm text-slate-600">{{ __('Označ všetky správne odpovede.') }}</legend>
                            <div class="flex flex-col gap-2">
                                @foreach ($question['options'] as $option)
                                    <label wire:key="opt-{{ $option['id'] }}" class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 px-4 py-3 hover:bg-slate-50 has-checked:border-brand-600 has-checked:bg-brand-50">
                                        <input type="checkbox" value="{{ $option['id'] }}" wire:model.live="responses.{{ $qid }}.selected" class="mt-0.5 size-4 rounded text-brand-600">
                                        <span class="text-sm text-slate-800">{{ $option['body'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        @break

                    @case(\App\Enums\QuestionType::ShortAnswer)
                        <label for="answer-{{ $qid }}" class="block text-sm font-medium text-slate-700">{{ __('Tvoja odpoveď') }}</label>
                        <input id="answer-{{ $qid }}" type="text" maxlength="500" autocomplete="off" wire:model.live.debounce.700ms="responses.{{ $qid }}.text"
                               class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30">
                        @break

                    @case(\App\Enums\QuestionType::FillBlank)
                        <p class="text-lg leading-10 text-slate-900">
                            @foreach ($question['segments'] as $segment)
                                @if (preg_match('/^\[\[(\d{1,2})\]\]$/', $segment, $m))
                                    <label for="blank-{{ $qid }}-{{ $m[1] }}" class="sr-only">{{ __('Medzera :n', ['n' => $m[1]]) }}</label>
                                    <input id="blank-{{ $qid }}-{{ $m[1] }}" type="text" maxlength="500" autocomplete="off"
                                           wire:model.live.debounce.700ms="responses.{{ $qid }}.blanks.{{ $m[1] }}"
                                           class="mx-1 inline-block w-36 rounded-md border border-slate-300 px-2 py-1 text-base focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30">
                                @else
                                    <span class="whitespace-pre-line">{{ $segment }}</span>
                                @endif
                            @endforeach
                        </p>
                        @break

                    @case(\App\Enums\QuestionType::Matching)
                        <div class="flex flex-col gap-3">
                            @foreach ($question['left'] as $left)
                                <div wire:key="pair-{{ $left['id'] }}" class="grid gap-2 rounded-lg border border-slate-200 p-3 sm:grid-cols-2 sm:items-center">
                                    <label for="pair-{{ $qid }}-{{ $left['id'] }}" class="text-sm font-medium text-slate-800">{{ $left['body'] }}</label>
                                    <select id="pair-{{ $qid }}-{{ $left['id'] }}" wire:model.live="responses.{{ $qid }}.pairs.{{ $left['id'] }}"
                                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                                        <option value="">{{ __('— vyber —') }}</option>
                                        @foreach ($question['right'] as $right)
                                            <option value="{{ $right['id'] }}">{{ $right['body'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                        @break
                @endswitch
            </div>
        </section>

        <div class="flex items-center justify-between gap-3">
            <x-button type="button" variant="secondary" wire:click="goTo({{ $current - 1 }})" :disabled="$current === 0">
                <x-icon name="chevron-left" class="size-4" />{{ __('Predchádzajúca') }}
            </x-button>

            @if ($current < $questions->count() - 1)
                <x-button type="button" wire:click="goTo({{ $current + 1 }})">
                    {{ __('Ďalšia') }}<x-icon name="chevron-right" class="size-4" />
                </x-button>
            @else
                <x-button type="button" wire:click="submit"
                    wire:confirm="{{ $answeredCount < $questions->count()
                        ? __('Nezodpovedal(a) si :n otázok. Naozaj chceš test odovzdať?', ['n' => $questions->count() - $answeredCount])
                        : __('Naozaj chceš test odovzdať? Odpovede potom už nebude možné zmeniť.') }}">
                    <x-icon name="check" class="size-4" />{{ __('Odovzdať test') }}
                </x-button>
            @endif
        </div>

        <p class="text-center text-xs text-slate-500" wire:loading.remove>{{ __('Odpovede sa ukladajú automaticky.') }}</p>
        <p class="text-center text-xs text-slate-500" wire:loading>{{ __('Ukladám…') }}</p>
    @endif
</div>
