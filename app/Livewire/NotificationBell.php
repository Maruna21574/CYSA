<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Bell in the top bar with the latest in-app notifications of the signed-in user.
 */
class NotificationBell extends Component
{
    public function open(string $id): void
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $this->redirect(self::safeUrl($notification->data['url'] ?? null));
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    /**
     * Notification links always point into the application - never follow anything else
     * (defence against an open redirect through stored data).
     */
    public static function safeUrl(?string $url): string
    {
        $base = rtrim((string) config('app.url'), '/');

        if (is_string($url) && ($url === $base || str_starts_with($url, $base.'/') || (str_starts_with($url, '/') && ! str_starts_with($url, '//')))) {
            return $url;
        }

        return route('dashboard');
    }

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.notification-bell', [
            'unread' => $user->unreadNotifications()->count(),
            'latest' => $user->notifications()->latest()->limit(8)->get(),
        ]);
    }
}
