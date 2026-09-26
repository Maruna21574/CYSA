<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
    <button type="button" @click="open = ! open" :aria-expanded="open" aria-haspopup="true"
            class="relative rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-indigo-600">
        <x-icon name="bell" class="size-6" />
        <span class="sr-only">{{ trans_choice('{0} Notifikácie|{1} Notifikácie, :count neprečítaná|[2,4] Notifikácie, :count neprečítané|[5,*] Notifikácie, :count neprečítaných', $unread, ['count' => $unread]) }}</span>
        @if ($unread > 0)
            <span class="absolute top-1 right-1 flex min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] leading-4 font-bold text-white" aria-hidden="true">{{ $unread > 9 ? '9+' : $unread }}</span>
        @endif
    </button>

    <div x-show="open" x-transition.origin.top.right x-cloak class="absolute right-0 z-30 mt-2 w-80 max-w-[calc(100vw-2rem)] rounded-xl border border-slate-200 bg-white shadow-lg">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
            <span class="text-sm font-semibold text-slate-900">{{ __('Notifikácie') }}</span>
            @if ($unread > 0)
                <button type="button" wire:click="markAllRead" class="text-xs font-medium text-indigo-700 hover:underline">{{ __('Označiť všetko ako prečítané') }}</button>
            @endif
        </div>

        <ul class="max-h-96 overflow-y-auto">
            @forelse ($latest as $notification)
                <li wire:key="notification-{{ $notification->id }}">
                    <button type="button" wire:click="open('{{ $notification->id }}')"
                            @class(['flex w-full gap-3 px-4 py-3 text-left hover:bg-slate-50', 'bg-indigo-50/60' => $notification->read_at === null])>
                        <span class="mt-0.5 text-indigo-600"><x-icon :name="$notification->data['icon'] ?? 'bell'" class="size-5" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-medium text-slate-900">{{ $notification->data['title'] ?? '' }}</span>
                            <span class="block text-xs text-slate-600">{{ \Illuminate\Support\Str::limit($notification->data['body'] ?? '', 120) }}</span>
                            <span class="mt-1 block text-[11px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                    </button>
                </li>
            @empty
                <li class="px-4 py-6 text-center text-sm text-slate-500">{{ __('Žiadne notifikácie.') }}</li>
            @endforelse
        </ul>

        <a href="{{ route('notifications.index') }}" class="block border-t border-slate-100 px-4 py-2 text-center text-sm font-medium text-indigo-700 hover:bg-slate-50">{{ __('Zobraziť všetky') }}</a>
    </div>
</div>
