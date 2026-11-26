<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invite;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The invite mail with the registration link (docs/product/invites.md).
 * The link has the token only, so the address is not in URLs or logs.
 */
final class InviteNotification extends Notification
{
    public function __construct(private readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(AnonymousNotifiable $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(AnonymousNotifiable $notifiable): MailMessage
    {
        $url = config()->string('app.url').'/register?token='.urlencode($this->token);
        $days = Invite::VALID_DAYS;

        return (new MailMessage)
            ->subject('You are invited to Pond Habits')
            ->line('You are invited to Pond Habits, a personal tracker for focus, tasks, habits and nutrition.')
            ->action('Create your account', $url)
            ->line("The link works once and expires in {$days} days.")
            ->line('If you did not expect this mail, you can ignore it.');
    }
}
