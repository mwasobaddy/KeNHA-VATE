<?php

return [
    'otp' => [
        'length' => env('OTP_LENGTH', 6),
        'expiry_minutes' => env('OTP_EXPIRY_MINUTES', 10),
        'max_attempts' => env('OTP_MAX_ATTEMPTS', 5),
    ],

    'points' => [
        'first_login' => env('POINTS_FIRST_LOGIN', 50),
        'daily_login' => env('POINTS_DAILY_LOGIN', 10),
    ],

    'rate_limiting' => [
        'login_attempts' => env('LOGIN_MAX_ATTEMPTS', 5),
        'lockout_minutes' => env('LOGIN_LOCKOUT_MINUTES', 15),
    ],

    'email_domains' => [
        'kenha' => ['kenha.co.ke'],
    ],

    'account_status' => [
        'active' => 'active',
        'banned' => 'banned',
        'disabled' => 'disabled',
    ],

    'employment_types' => [
        'permanent' => 'Permanent',
        'contract' => 'Contract',
        'intern' => 'Intern',
        'consultant' => 'Consultant',
    ],

    'gender_options' => [
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Other',
        'prefer_not_to_say' => 'Prefer not to say',
    ],

    'collaboration_points' => [
        'invite_collaborator' => env('POINTS_INVITE_COLLABORATOR', 5),
        'accept_invitation' => env('POINTS_ACCEPT_INVITATION', 10),
        'suggest_revision' => env('POINTS_SUGGEST_REVISION', 15),
        'revision_accepted' => env('POINTS_REVISION_ACCEPTED', 25),
        'collaboration_request' => env('POINTS_COLLABORATION_REQUEST', 3),
    ],
];