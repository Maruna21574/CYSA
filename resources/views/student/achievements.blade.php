<x-layouts::app :title="__('Moje úspechy')">
    <x-page-header :title="__('Moje úspechy')" :description="__('Body, levely a odznaky za učenie. Vidíš ich iba ty.')" />

    <x-gamification-card :stats="$stats" class="mb-6" />

    <h2 class="mb-3 text-lg font-semibold text-slate-900">{{ __('Odznaky') }}</h2>
    <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($badges as $badge)
            @php $awardedAt = $earned[$badge->id] ?? null; @endphp
            <div @class([
                'flex flex-col items-center gap-2 rounded-xl border p-5 text-center',
                'border-amber-200 bg-amber-50' => $awardedAt,
                'border-slate-200 bg-white opacity-60' => ! $awardedAt,
            ])>
                <span @class(['rounded-full p-3', 'bg-amber-100 text-amber-700' => $awardedAt, 'bg-slate-100 text-slate-400' => ! $awardedAt])>
                    <x-icon :name="$awardedAt ? $badge->icon : 'lock'" class="size-7" />
                </span>
                <h3 class="font-semibold text-slate-900">{{ $badge->name }}</h3>
                <p class="text-xs text-slate-600">{{ $badge->description }}</p>
                <p class="text-xs font-medium text-slate-500">
                    {{ $awardedAt ? __('Získaný :date', ['date' => \Illuminate\Support\Carbon::parse($awardedAt)->translatedFormat('j. n. Y')]) : __('+:xp XP', ['xp' => $badge->xp_reward]) }}
                </p>
            </div>
        @endforeach
    </div>

    <x-card :title="__('Posledné body')">
        @php
            $reasons = [
                'chapter_completed' => __('Dokončená kapitola'),
                'quiz_passed' => __('Úspešný test'),
                'quiz_perfect' => __('Test bez chyby'),
                'course_completed' => __('Dokončený kurz'),
                'certificate' => __('Certifikát'),
                'badge' => __('Odznak'),
            ];
        @endphp
        @forelse ($history as $transaction)
            <div class="flex justify-between border-b border-slate-100 py-2 text-sm last:border-0">
                <span>{{ $reasons[$transaction->reason] ?? $transaction->reason }}</span>
                <span class="flex gap-4">
                    <span class="text-slate-500">{{ $transaction->created_at->translatedFormat('j. n. Y') }}</span>
                    <span class="w-16 text-right font-semibold text-emerald-700 tabular-nums">+{{ $transaction->amount }} XP</span>
                </span>
            </div>
        @empty
            <p class="text-sm text-slate-500">{{ __('Zatiaľ žiadne body. Dokonči kapitolu alebo test!') }}</p>
        @endforelse
    </x-card>
</x-layouts::app>
