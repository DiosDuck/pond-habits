<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The mail with the password reset link (docs/product/password-reset.md).
 * The link opens the set-password page. It has the token only, so the address is not in URLs or logs.
 */
final class ResetPasswordNotification extends Notification
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
            ->subject(__('mail.reset_password.subject'))
            ->line(__('mail.reset_password.intro'))
            ->action(__('mail.reset_password.action'), $url)
            ->line(__('mail.reset_password.expires', ['minutes' => $minutes]))
            ->line(__('mail.reset_password.ignore'));
    }
}
