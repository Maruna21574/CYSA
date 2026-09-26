<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base of all in-app notifications. The in-app (database) copy is stored immediately;
 * the optional e-mail goes through the queue and only to users who enabled e-mails.
 */
abstract class CysaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    abstract public function title(): string;

    abstract public function body(): string;

    abstract public function url(): string;

    public function icon(): string
    {
        return 'bell';
    }

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return $notifiable->email_notifications ? ['database', 'mail'] : ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    /**
     * @return array{title: string, body: string, url: string, icon: string}
     */
    public function toArray(User $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => $this->url(),
            'icon' => $this->icon(),
        ];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title().' · '.config('app.name'))
            ->greeting(__('Dobrý deň, :name,', ['name' => $notifiable->first_name]))
            ->line($this->body())
            ->action(__('Otvoriť'), $this->url())
            ->line(__('E-mailové upozornenia môžete vypnúť vo svojom profile.'));
    }
}
