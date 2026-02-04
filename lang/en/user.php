<?php

declare(strict_types=1);

return [
    'resource' => [
        'email_verified_at' => 'Email verified at',
        'model_label' => 'User|Users',
    ],
    'account_cannot_be_deleted' => 'Account cannot be deleted while active resources exist.',
    'account_deletion_blocked_message' => 'Your account cannot be deleted yet because it is linked to active data:',
        'delete_resources_first_message' => 'Please delete these resources first before attempting to close your account.',
    'two_factor' => [
        'notifications' => [
            'security_error_title' => 'Security Error',
            'security_error_body' => 'Unable to retrieve recovery codes. Please contact support.',
            'configuration_error_title' => 'Configuration Error',
            'configuration_error_body' => 'Your 2FA configuration is inaccessible. You may need to reset your 2FA.',
        ],
    ],
];
