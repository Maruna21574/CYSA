{{-- Review of one answer, rendered only from the frozen snapshot. --}}
@php
    $snapshot = $answer->question_snapshot;
    $type = \App\Enums\QuestionType::from($snapshot['type']);
    $response = $answer->response ?? [];
    $options = collect($snapshot['options'])->keyBy('id');
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',');
    $state = $answer->response === null ? 'empty' : ($answer->is_correct ? 'correct' : ((float) $answer->points_awarded > 0 ? 'partial' : 'wrong'));
@endphp

<article @class([
    'rounded-xl border bg-white p-5 shadow-xs',
    'border-emerald-200' => $state === 'correct',
    'border-amber-200' => $state === 'partial',
    'border-red-200' => in_array($state, ['wrong', 'empty'], true),
])>
    <header class="mb-3 flex items-start justify-between gap-3">
        <h3 class="font-medium whitespace-pre-line text-slate-900">{{ $number }}. {{ $snapshot['body'] }}</h3>
        <span @class([
            'shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums',
            'bg-emerald-50 text-emerald-700' => $state === 'correct',
            'bg-amber-50 text-amber-800' => $state === 'partial',
            'bg-red-50 text-red-700' => in_array($state, ['wrong', 'empty'], true),
        ])>{{ $fmt($answer->points_awarded) }} / {{ $fmt($answer->max_points) }} b.</span>
    </header>

    @switch($type)
        @case(\App\Enums\QuestionType::SingleChoice)
        @case(\App\Enums\QuestionType::TrueFalse)
        @case(\App\Enums\QuestionType::MultipleChoice)
            <ul class="flex flex-col gap-1.5 text-sm">
                @foreach ($snapshot['options'] as $option)
                    @php $picked = in_array($option['id'], $response['selected'] ?? []); @endphp
                    <li @class([
                        'flex items-center gap-2 rounded-lg px-3 py-2',
                        'bg-emerald-50 text-emerald-900' => $option['is_correct'],
                        'bg-red-50 text-red-900' => $picked && ! $option['is_correct'],
                        'text-slate-700' => ! $picked && ! $option['is_correct'],
                    ])>
                        <span class="w-5 shrink-0">
                            @if ($option['is_correct']) <x-icon name="check" class="size-4" /><span class="sr-only">{{ __('správna odpoveď') }}</span>
                            @elseif ($picked) <x-icon name="x" class="size-4" />
                            @endif
                        </span>
                        <span class="flex-1">{{ $option['body'] }}</span>
                        @if ($picked) <span class="text-xs font-medium">{{ __('tvoja odpoveď') }}</span> @endif
                    </li>
                @endforeach
            </ul>
            @break

        @case(\App\Enums\QuestionType::ShortAnswer)
            <dl class="grid gap-1 text-sm sm:grid-cols-[auto_1fr] sm:gap-x-4">
                <dt class="text-slate-500">{{ __('Tvoja odpoveď:') }}</dt><dd class="font-medium">{{ $response['text'] ?? '—' }}</dd>
                <dt class="text-slate-500">{{ __('Správne:') }}</dt><dd>{{ collect($snapshot['options'])->pluck('body')->implode(', ') }}</dd>
            </dl>
            @break

        @case(\App\Enums\QuestionType::FillBlank)
            <dl class="grid gap-1 text-sm sm:grid-cols-[auto_1fr_1fr] sm:gap-x-4">
                @foreach (collect($snapshot['options'])->groupBy('blank_index')->sortKeys() as $blank => $accepted)
                    <dt class="text-slate-500">[[{{ $blank }}]]</dt>
                    <dd class="font-medium">{{ $response['blanks'][$blank] ?? '—' }}</dd>
                    <dd class="text-slate-600">{{ __('správne: :a', ['a' => $accepted->pluck('body')->implode(' / ')]) }}</dd>
                @endforeach
            </dl>
            @break

        @case(\App\Enums\QuestionType::Matching)
            <ul class="flex flex-col gap-1.5 text-sm">
                @foreach ($snapshot['options'] as $option)
                    @php $chosen = $response['pairs'][$option['id']] ?? null; $ok = (int) $chosen === (int) $option['id']; @endphp
                    <li @class(['rounded-lg px-3 py-2', 'bg-emerald-50' => $ok, 'bg-red-50' => ! $ok])>
                        <span class="font-medium">{{ $option['body'] }}</span> →
                        {{ $chosen ? ($options[$chosen]['match_body'] ?? '—') : '—' }}
                        @unless ($ok)
                            <span class="block text-xs text-slate-600">{{ __('správne: :a', ['a' => $option['match_body']]) }}</span>
                        @endunless
                    </li>
                @endforeach
            </ul>
            @break
    @endswitch

    @if (! empty($snapshot['explanation']))
        <p class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700"><span class="font-semibold">{{ __('Vysvetlenie:') }}</span> {{ $snapshot['explanation'] }}</p>
    @endif

    @if (! $isOwner && auth()->user()->can('viewResults', $attempt->quiz) && ! $attempt->isInProgress())
        <form method="POST" action="{{ route('teacher.attempts.answers.score', [$attempt, $answer]) }}" class="mt-3 flex flex-wrap items-end gap-2 border-t border-slate-100 pt-3">
            @csrf
            @method('PATCH')
            <div>
                <label for="score-{{ $answer->id }}" class="block text-xs font-medium text-slate-600">{{ __('Upraviť body (max. :max)', ['max' => $fmt($answer->max_points)]) }}</label>
                <input id="score-{{ $answer->id }}" type="number" name="points" min="0" max="{{ (float) $answer->max_points }}" step="0.25" value="{{ (float) $answer->points_awarded }}"
                       class="mt-1 block w-28 rounded-lg border border-slate-300 px-2 py-1 text-sm">
            </div>
            <x-button variant="secondary" class="py-1">{{ __('Uložiť body') }}</x-button>
        </form>
    @endif
</article>
