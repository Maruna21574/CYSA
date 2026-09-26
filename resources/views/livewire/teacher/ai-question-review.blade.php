<div @if (! $generation->status->isFinished()) wire:poll.4s @endif class="flex flex-col gap-6">
    @if (! $generation->status->isFinished())
        <x-card class="flex items-center gap-4" role="status">
            <span class="size-8 animate-spin rounded-full border-4 border-indigo-200 border-t-indigo-600" aria-hidden="true"></span>
            <div>
                <p class="font-medium text-slate-900">{{ $generation->status->label() }}…</p>
                <p class="text-sm text-slate-600">{{ __('AI pripravuje návrhy otázok. Stránka sa aktualizuje sama; môžete ju aj zavrieť – po dokončení dostanete notifikáciu.') }}</p>
            </div>
        </x-card>
    @elseif ($generation->status === \App\Enums\AiGenerationStatus::Failed)
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            <p class="font-semibold">{{ __('Generovanie sa nepodarilo.') }}</p>
            <p class="mt-1">{{ $generation->error }}</p>
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="note">
            <p class="font-semibold">{{ __('Toto sú iba návrhy umelej inteligencie.') }}</p>
            <p class="mt-1">{{ __('Skontrolujte správnosť, formulácie aj správne odpovede. Študenti uvidia iba otázky, ktoré schválite a pridáte do testu.') }}</p>
        </div>

        @if ($generation->warnings)
            <details class="rounded-lg border border-slate-200 bg-white p-3 text-sm text-slate-600">
                <summary class="cursor-pointer font-medium">{{ trans_choice('{1} :count návrh bol vynechaný|[2,4] :count návrhy boli vynechané|[5,*] :count návrhov bolo vynechaných', count($generation->warnings), ['count' => count($generation->warnings)]) }}</summary>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($generation->warnings as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </details>
        @endif

        <div class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <x-button type="button" variant="secondary" wire:click="approveAll" wire:confirm="{{ __('Naozaj schváliť všetky zostávajúce návrhy bez úprav?') }}">
                <x-icon name="check" class="size-4" />{{ __('Schváliť všetky') }}
            </x-button>
            <span class="flex-1"></span>
            @if ($quizzes)
                <div>
                    <label for="ai-quiz" class="block text-xs font-medium text-slate-600">{{ __('Pridať schválené otázky do testu') }}</label>
                    <select id="ai-quiz" wire:model="quizId" class="mt-1 block rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm">
                        <option value="">{{ __('— vyberte test —') }}</option>
                        @foreach ($quizzes as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </select>
                </div>
                <x-button type="button" wire:click="addToQuiz">{{ __('Pridať do testu') }}</x-button>
            @else
                <x-link-button variant="secondary" :href="route('teacher.quizzes.create', ['course' => $generation->course_id])">{{ __('Vytvoriť test pre tento kurz') }}</x-link-button>
            @endif
        </div>

        <div class="flex flex-col gap-4">
            @forelse ($questions as $question)
                <article wire:key="ai-question-{{ $question->id }}" @class([
                    'rounded-xl border bg-white p-5 shadow-xs',
                    'border-amber-200' => $question->status === \App\Enums\QuestionStatus::Draft,
                    'border-emerald-200' => $question->status === \App\Enums\QuestionStatus::Approved,
                ])>
                    <header class="mb-3 flex flex-wrap items-start justify-between gap-2">
                        <div class="flex flex-wrap gap-1">
                            <x-badge :color="$question->status === \App\Enums\QuestionStatus::Approved ? 'green' : 'amber'">{{ $question->status->label() }}</x-badge>
                            <x-badge>{{ $question->type->label() }}</x-badge>
                            @foreach ($question->topics as $topic)
                                <x-badge color="indigo">{{ $topic->name }}</x-badge>
                            @endforeach
                        </div>
                        <div class="flex gap-3 text-sm">
                            <a href="{{ route('teacher.questions.edit', $question) }}" class="font-medium text-indigo-700 hover:underline">{{ __('Upraviť') }}</a>
                            @if ($question->status === \App\Enums\QuestionStatus::Draft)
                                <button type="button" wire:click="approve({{ $question->id }})" class="font-medium text-emerald-700 hover:underline">{{ __('Schváliť') }}</button>
                            @endif
                            <button type="button" wire:click="reject({{ $question->id }})" wire:confirm="{{ __('Zamietnuť a odstrániť tento návrh?') }}" class="font-medium text-red-700 hover:underline">{{ __('Zamietnuť') }}</button>
                        </div>
                    </header>

                    <p class="font-medium whitespace-pre-line text-slate-900">{{ $question->body }}</p>

                    <ul class="mt-3 flex flex-col gap-1 text-sm">
                        @foreach ($question->options as $option)
                            <li @class(['flex items-center gap-2 rounded-lg px-3 py-1.5', 'bg-emerald-50 text-emerald-900' => $option->is_correct && $question->type->isChoice(), 'text-slate-700' => ! ($option->is_correct && $question->type->isChoice())])>
                                @if ($question->type->isChoice())
                                    <span class="w-4">@if ($option->is_correct)<x-icon name="check" class="size-4" />@endif</span>
                                @endif
                                @if ($question->type === \App\Enums\QuestionType::FillBlank)
                                    <span class="text-xs text-slate-500">[[{{ $option->blank_index }}]]</span>
                                @endif
                                <span>{{ $option->body }}</span>
                                @if ($option->match_body)
                                    <span class="text-slate-400">→</span><span>{{ $option->match_body }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    @if ($question->explanation)
                        <p class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700"><span class="font-semibold">{{ __('Vysvetlenie:') }}</span> {{ $question->explanation }}</p>
                    @endif
                </article>
            @empty
                <x-empty-state icon="question" :title="__('Žiadne návrhy')" :description="__('Všetky návrhy boli zamietnuté alebo AI nevytvorila platnú otázku.')" />
            @endforelse
        </div>

        @if ($generation->material)
            <x-card :title="__('Materiál v skratke')">
                @if ($materialText?->summary)
                    <p class="text-sm text-slate-700">{{ $materialText->summary }}</p>
                    @if ($materialText->keywords)
                        <div class="mt-3 flex flex-wrap gap-1">
                            @foreach ($materialText->keywords as $keyword)
                                <x-badge color="indigo">{{ $keyword }}</x-badge>
                            @endforeach
                        </div>
                    @endif
                @else
                    <x-button type="button" variant="secondary" wire:click="summarize">
                        <x-icon name="sparkles" class="size-4" />{{ __('Zhrnúť materiál a nájsť kľúčové pojmy') }}
                    </x-button>
                @endif
            </x-card>
        @endif
    @endif
</div>
