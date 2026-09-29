<x-layouts::app :title="__('Notifikácie')">
    <x-page-header :title="__('Notifikácie')">
        <x-slot:actions>
            <form method="POST" action="{{ route('notifications.read') }}">
                @csrf
                <x-button variant="secondary">{{ __('Označiť všetko ako prečítané') }}</x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    @if ($notifications->isEmpty())
        <x-empty-state icon="bell" :title="__('Žiadne notifikácie')" />
    @else
        <div class="flex flex-col divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white shadow-xs">
            @foreach ($notifications as $notification)
                <a href="{{ \App\Livewire\NotificationBell::safeUrl($notification->data['url'] ?? null) }}"
                   @class(['flex gap-3 px-4 py-3 hover:bg-slate-50', 'bg-brand-50/60' => $notification->read_at === null])>
                    <span class="mt-0.5 text-brand-600"><x-icon :name="$notification->data['icon'] ?? 'bell'" class="size-5" /></span>
                    <span class="flex-1">
                        <span class="block text-sm font-medium text-slate-900">{{ $notification->data['title'] ?? '' }}</span>
                        <span class="block text-sm text-slate-600">{{ $notification->data['body'] ?? '' }}</span>
                    </span>
                    <span class="text-xs whitespace-nowrap text-slate-400">{{ $notification->created_at->translatedFormat('j. n. Y H:i') }}</span>
                </a>
            @endforeach
        </div>
        <div class="mt-4">{{ $notifications->links() }}</div>
    @endif
</x-layouts::app>
