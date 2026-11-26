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
            ->subject(__('mail.invite.subject'))
            ->line(__('mail.invite.intro'))
            ->action(__('mail.invite.action'), $url)
            ->line(__('mail.invite.expires', ['days' => $days]))
            ->line(__('mail.invite.ignore'));
    }
}
