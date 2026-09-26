<x-layouts::app :title="__('Administrácia systému')">
    <x-page-header :title="__('Administrácia systému')" :description="__('Využitie platformy a bezpečnostné udalosti.')" />

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat :label="__('Školy')" :value="$schools" :hint="__(':n aktívnych', ['n' => $activeSchools])" icon="building" />
        <x-stat :label="__('Používatelia')" :value="$usersByRole->sum()" :hint="__(':n aktívnych za 30 dní', ['n' => $activeUsers])" icon="users" />
        <x-stat :label="__('Kurzy / testy')" :value="$courses.' / '.$quizzes" icon="book" />
        <x-stat :label="__('Odovzdané testy')" :value="$attempts" :hint="__(':n certifikátov', ['n' => $certificates])" icon="shield" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card :title="__('Aktivita – odovzdané testy za 14 dní')" class="lg:col-span-2">
            @php $max = max(1, $activity->max()); @endphp
            <div class="flex h-40 items-end gap-1" role="img" aria-label="{{ __('Graf počtu odovzdaných testov po dňoch') }}">
                @foreach ($activity as $day => $count)
                    <div class="flex flex-1 flex-col items-center gap-1" title="{{ \Illuminate\Support\Carbon::parse($day)->translatedFormat('j. n.') }}: {{ $count }}">
                        <span class="text-[10px] tabular-nums text-slate-500">{{ $count ?: '' }}</span>
                        <div class="w-full rounded-t bg-indigo-500" style="height: {{ max(2, $count / $max * 120) }}px"></div>
                        <span class="text-[10px] text-slate-400">{{ \Illuminate\Support\Carbon::parse($day)->format('j.') }}</span>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card :title="__('Používatelia podľa rolí')">
            @foreach ($usersByRole as $role => $count)
                <div class="flex justify-between border-b border-slate-100 py-2 text-sm last:border-0"><span>{{ $role }}</span><span class="font-semibold tabular-nums">{{ $count }}</span></div>
            @endforeach
        </x-card>
    </div>

    <x-card :title="__('Posledné bezpečnostné udalosti')" class="mt-6">
        @forelse ($securityEvents as $event)
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 py-2 text-sm last:border-0">
                <x-badge :color="in_array($event->action, ['auth.lockout', 'auth.account_blocked']) ? 'red' : 'amber'">{{ \App\Enums\AuditAction::tryFrom($event->action)?->label() ?? $event->action }}</x-badge>
                <span class="flex-1 text-slate-700">{{ $event->user?->name ?? ($event->metadata['email'] ?? '—') }}</span>
                <span class="font-mono text-xs text-slate-500">{{ $event->ip_address }}</span>
                <span class="text-xs text-slate-500">{{ $event->created_at->translatedFormat('j. n. Y H:i') }}</span>
            </div>
        @empty
            <p class="text-sm text-slate-500">{{ __('Žiadne udalosti.') }}</p>
        @endforelse
        @if (Route::has('admin.audit-logs.index'))
            <a href="{{ route('admin.audit-logs.index') }}" class="mt-3 inline-block text-sm font-medium text-indigo-700 hover:underline">{{ __('Celý audit log') }}</a>
        @endif
    </x-card>
</x-layouts::app>
