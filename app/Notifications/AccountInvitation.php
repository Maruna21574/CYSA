<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail with a link to set the password of a newly created account.
 */
class AccountInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private string $token) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $url = route('invitation.accept', ['token' => $this->token, 'email' => $notifiable->email]);
        $school = $notifiable->school?->name;

        return (new MailMessage)
            ->subject(__('Pozvánka do platformy :app', ['app' => config('app.name')]))
            ->greeting(__('Dobrý deň, :name,', ['name' => $notifiable->first_name]))
            ->line($school
                ? __('škola :school vám vytvorila účet vo vzdelávacej platforme :app.', ['school' => $school, 'app' => config('app.name')])
                : __('bol vám vytvorený účet vo vzdelávacej platforme :app.', ['app' => config('app.name')]))
            ->line(__('Na aktiváciu účtu si nastavte heslo:'))
            ->action(__('Nastaviť heslo'), $url)
            ->line(__('Odkaz je platný :days dní. Ak ste pozvánku nečakali, e-mail ignorujte.', [
                'days' => (int) (config('auth.passwords.invitations.expire') / 60 / 24),
            ]));
    }
}
