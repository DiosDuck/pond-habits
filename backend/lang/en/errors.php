<?php

declare(strict_types=1);

// The error texts of the API. The frontend shows them as they are (ADR 0001: i18n keys from day 1).
return [
    'login_failed' => 'The email or password is not correct.',
    'invite_invalid' => 'This invite is not valid or has expired.',
    'link_invalid' => 'This link is not valid or has expired.',
    'email_taken' => 'This email address has an account already.',
    'disable_self' => 'You cannot disable your own account.',
    'password_wrong' => 'The password is not correct.',
    'current_password_wrong' => 'The current password is not correct.',
    'only_admin' => 'You are the only admin. Another admin must exist first.',
];
