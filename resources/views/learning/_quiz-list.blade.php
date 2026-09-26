{{-- Quizzes with the student's best visible result. --}}
<ul class="flex flex-col divide-y divide-slate-100">
    @foreach ($quizzes as $quiz)
        @php $best = $bestResults[$quiz->id] ?? null; @endphp
        <li class="flex flex-wrap items-center gap-3 py-3">
            <span class="text-indigo-600"><x-icon name="shield" class="size-5" /></span>
            <div class="min-w-0 flex-1">
                @if ($isPreview)
                    <a href="{{ route('teacher.quizzes.show', $quiz) }}" class="font-medium text-slate-900 hover:text-indigo-700 hover:underline">{{ $quiz->title }}</a>
                @else
                    <a href="{{ route('student.quizzes.show', $quiz) }}" class="font-medium text-slate-900 hover:text-indigo-700 hover:underline">{{ $quiz->title }}</a>
                @endif
                <span class="block text-xs text-slate-500">
                    {{ $quiz->purpose->label() }} · {{ trans_choice('{1} :count otázka|[2,4] :count otázky|[0,*] :count otázok', $quiz->questions_count, ['count' => $quiz->questions_count]) }}
                    @if ($quiz->due_at) · {{ __('termín :date', ['date' => $quiz->due_at->translatedFormat('j. n. Y H:i')]) }} @endif
                </span>
            </div>
            @if ($isPreview)
                <x-badge :color="$quiz->status->color()">{{ $quiz->status->label() }}</x-badge>
            @elseif ($best && $best->scoreIsVisible())
                <x-score :attempt="$best" />
            @elseif ($best)
                <x-badge>{{ __('Odovzdané') }}</x-badge>
            @endif
        </li>
    @endforeach
</ul>
