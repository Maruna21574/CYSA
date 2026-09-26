@props(['stats', 'badges' => collect()])

@php
    $level = $stats->level;
    $from = \App\Gamification\GamificationService::xpForLevel($level);
    $to = \App\Gamification\GamificationService::xpForLevel($level + 1);
    $progress = $to > $from ? ($stats->xp_total - $from) / ($to - $from) * 100 : 100;
@endphp

<section {{ $attributes->class('rounded-xl border border-indigo-200 bg-gradient-to-br from-indigo-600 to-sky-600 p-5 text-white shadow-xs') }} aria-label="{{ __('Moje úspechy') }}">
    <div class="flex flex-wrap items-center gap-6">
        <div>
            <p class="text-xs font-medium tracking-wide text-indigo-100 uppercase">{{ __('Level') }}</p>
            <p class="text-4xl font-bold tabular-nums">{{ $level }}</p>
        </div>
        <div class="min-w-48 flex-1">
            <div class="mb-1 flex justify-between text-sm">
                <span class="font-semibold tabular-nums">{{ $stats->xp_total }} XP</span>
                <span class="text-indigo-100 tabular-nums">{{ __('ďalší level pri :xp XP', ['xp' => $to]) }}</span>
            </div>
            <div class="h-2.5 overflow-hidden rounded-full bg-white/25" role="progressbar" aria-valuenow="{{ round($progress) }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('Postup k ďalšiemu levelu') }}">
                <div class="h-full rounded-full bg-white" style="width: {{ $progress }}%"></div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <x-icon name="fire" class="size-7 text-amber-300" />
            <div>
                <p class="text-xl font-bold tabular-nums">{{ $stats->current_streak }}</p>
                <p class="text-xs text-indigo-100">{{ trans_choice('{0} dní v rade|{1} deň v rade|[2,4] dni v rade|[5,*] dní v rade', $stats->current_streak) }}</p>
            </div>
        </div>
    </div>

    @if ($badges->isNotEmpty())
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($badges as $badge)
                <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-xs font-medium" title="{{ $badge->description }}">
                    <x-icon :name="$badge->icon" class="size-4" />{{ $badge->name }}
                </span>
            @endforeach
        </div>
    @endif

    <a href="{{ route('student.achievements') }}" class="mt-3 inline-block text-sm font-medium text-white underline">{{ __('Všetky odznaky') }}</a>
</section>
