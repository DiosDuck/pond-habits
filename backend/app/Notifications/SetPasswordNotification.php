<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The mail with the set-password link (docs/product/login.md).
 * The link has the token only. The form asks for the email, so the address is not in URLs or logs.
 */
final class SetPasswordNotification extends Notification
{
    public function __construct(private readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $url = config()->string('app.url').'/set-password?token='.urlencode($this->token);
        $minutes = config()->integer('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject(__('mail.set_password.subject'))
            ->line(__('mail.set_password.intro'))
            ->action(__('mail.set_password.action'), $url)
            ->line(__('mail.set_password.expires', ['minutes' => $minutes]))
            ->line(__('mail.set_password.ignore'));
    }
}
