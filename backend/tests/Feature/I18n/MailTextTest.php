<?php

declare(strict_types=1);

use App\Models\Invite;
use App\Models\User;
use App\Notifications\InviteNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\SetPasswordNotification;
use Illuminate\Notifications\AnonymousNotifiable;

// The mail texts come from lang/en. The English text must stay the same,
// and the number in each text must be filled in (not ":minutes").

beforeEach(function (): void {
    config(['auth.passwords.users.expire' => 47]);
});

it('writes the invite mail in English', function (): void {
    $mail = (new InviteNotification('token'))->toMail(new AnonymousNotifiable);
    $days = Invite::VALID_DAYS;

    expect($mail->subject)->toBe('You are invited to Pond Habits')
        ->and($mail->introLines)->toBe([
            'You are invited to Pond Habits, a personal tracker for focus, tasks, habits and nutrition.',
        ])
        ->and($mail->actionText)->toBe('Create your account')
        ->and($mail->outroLines)->toBe([
            "The link works once and expires in {$days} days.",
            'If you did not expect this mail, you can ignore it.',
        ]);
});

it('writes the set-password mail in English', function (): void {
    $mail = (new SetPasswordNotification('token'))->toMail(User::factory()->make());

    expect($mail->subject)->toBe('Set your Pond Habits password')
        ->and($mail->introLines)->toBe(['An account was made for you on Pond Habits.'])
        ->and($mail->actionText)->toBe('Set your password')
        ->and($mail->outroLines)->toBe([
            'The link works once and expires in 47 minutes.',
            'If you did not expect this mail, you can ignore it.',
        ]);
});

it('writes the password reset mail in English', function (): void {
    $mail = (new ResetPasswordNotification('token'))->toMail(User::factory()->make());

    expect($mail->subject)->toBe('Reset your Pond Habits password')
        ->and($mail->introLines)->toBe(['We got a request to reset the password of your Pond Habits account.'])
        ->and($mail->actionText)->toBe('Set a new password')
        ->and($mail->outroLines)->toBe([
            'The link works once and expires in 47 minutes.',
            'If you did not ask for this, you can ignore this mail. Your password stays the same.',
        ]);
});
