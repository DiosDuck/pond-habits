<?php

declare(strict_types=1);

// The texts of the mails (ADR 0001: i18n keys from day 1).
return [
    'invite' => [
        'subject' => 'You are invited to Pond Habits',
        'intro' => 'You are invited to Pond Habits, a personal tracker for focus, tasks, habits and nutrition.',
        'action' => 'Create your account',
        'expires' => 'The link works once and expires in :days days.',
        'ignore' => 'If you did not expect this mail, you can ignore it.',
    ],
    'set_password' => [
        'subject' => 'Set your Pond Habits password',
        'intro' => 'An account was made for you on Pond Habits.',
        'action' => 'Set your password',
        'expires' => 'The link works once and expires in :minutes minutes.',
        'ignore' => 'If you did not expect this mail, you can ignore it.',
    ],
    'reset_password' => [
        'subject' => 'Reset your Pond Habits password',
        'intro' => 'We got a request to reset the password of your Pond Habits account.',
        'action' => 'Set a new password',
        'expires' => 'The link works once and expires in :minutes minutes.',
        'ignore' => 'If you did not ask for this, you can ignore this mail. Your password stays the same.',
    ],
];
