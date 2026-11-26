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
            ->subject('Reset your Pond Habits password')
            ->line('We got a request to reset the password of your Pond Habits account.')
            ->action('Set a new password', $url)
            ->line("The link works once and expires in {$minutes} minutes.")
            ->line('If you did not ask for this, you can ignore this mail. Your password stays the same.');
    }
}
