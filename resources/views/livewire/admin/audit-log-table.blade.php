<div class="flex flex-col gap-4">
    <div class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xs sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <label for="audit-action" class="block text-xs font-medium text-slate-600">{{ __('Udalosť') }}</label>
            <select id="audit-action" wire:model.live="action" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm">
                <option value="">{{ __('Všetky') }}</option>
                @foreach ($actions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="audit-search" class="block text-xs font-medium text-slate-600">{{ __('Používateľ alebo IP') }}</label>
            <input id="audit-search" type="search" wire:model.live.debounce.400ms="search" class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
        </div>
        <div>
            <label for="audit-school" class="block text-xs font-medium text-slate-600">{{ __('Škola') }}</label>
            <select id="audit-school" wire:model.live="schoolId" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm">
                <option value="">{{ __('Všetky') }}</option>
                @foreach ($schools as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="audit-from" class="block text-xs font-medium text-slate-600">{{ __('Od') }}</label>
            <input id="audit-from" type="date" wire:model.live="from" class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
        </div>
        <div>
            <label for="audit-to" class="block text-xs font-medium text-slate-600">{{ __('Do') }}</label>
            <input id="audit-to" type="date" wire:model.live="to" class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
        </div>
    </div>

    @if ($logs->isEmpty())
        <x-empty-state icon="document" :title="__('Žiadne záznamy')" />
    @else
        <x-table.wrapper wire:loading.class="opacity-60">
            <thead class="bg-slate-50">
                <tr>
                    <x-table.th>{{ __('Čas') }}</x-table.th>
                    <x-table.th>{{ __('Udalosť') }}</x-table.th>
                    <x-table.th>{{ __('Používateľ') }}</x-table.th>
                    <x-table.th>{{ __('Objekt') }}</x-table.th>
                    <x-table.th>{{ __('IP adresa') }}</x-table.th>
                    <x-table.th><span class="sr-only">{{ __('Detail') }}</span></x-table.th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($logs as $log)
                    <tr wire:key="log-{{ $log->id }}">
                        <td class="px-4 py-2 whitespace-nowrap text-slate-600 tabular-nums">{{ $log->created_at->format('j. n. Y H:i:s') }}</td>
                        <td class="px-4 py-2"><x-badge :color="str_starts_with($log->action, 'auth.') ? 'amber' : 'slate'">{{ \App\Enums\AuditAction::tryFrom($log->action)?->label() ?? $log->action }}</x-badge></td>
                        <td class="px-4 py-2">{{ $log->user?->name ?? ($log->metadata['email'] ?? '—') }}</td>
                        <td class="px-4 py-2 text-xs text-slate-500">{{ $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : '—' }}</td>
                        <td class="px-4 py-2 font-mono text-xs text-slate-500">{{ $log->ip_address }}</td>
                        <td class="px-4 py-2 text-right">
                            <button type="button" wire:click="toggle({{ $log->id }})" class="text-sm font-medium text-brand-700 hover:underline" aria-expanded="{{ $expanded === $log->id ? 'true' : 'false' }}">
                                {{ $expanded === $log->id ? __('Skryť') : __('Detail') }}
                            </button>
                        </td>
                    </tr>
                    @if ($expanded === $log->id)
                        <tr wire:key="log-detail-{{ $log->id }}">
                            <td colspan="6" class="bg-slate-50 px-4 py-3">
                                <div class="grid gap-3 text-xs md:grid-cols-3">
                                    @foreach (['old_values' => __('Pôvodné hodnoty'), 'new_values' => __('Nové hodnoty'), 'metadata' => __('Kontext')] as $field => $label)
                                        <div>
                                            <p class="mb-1 font-semibold text-slate-700">{{ $label }}</p>
                                            <pre class="overflow-x-auto rounded bg-white p-2 text-slate-700">{{ $log->{$field} ? json_encode($log->{$field}, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '—' }}</pre>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="mt-2 text-xs break-all text-slate-500">{{ $log->user_agent }}</p>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </x-table.wrapper>

        {{ $logs->links() }}
    @endif
</div>
