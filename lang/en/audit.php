<?php

return [
    'auth' => [
        'login' => 'Login',
        'logout' => 'Logout',
        'failed' => 'Failed login',
        'password_reset' => 'Password reset',
    ],
    'loan' => [
        'created' => 'Lent',
        'converted' => 'Reservation handed out',
        'returned' => 'Returned',
        'partially_returned' => 'Partially returned',
        'extended' => 'Extended',
        'issues_resolved' => 'Missing accessories resolved',
    ],
    'device_reservation' => [
        'created' => 'Reserved',
        'cancelled' => 'Reservation withdrawn',
    ],
    'device' => [
        'created' => 'Device created',
        'updated' => 'Device changed',
        'deleted' => 'Device deleted',
    ],
    'category' => [
        'created' => 'Category created',
        'updated' => 'Category changed',
        'deleted' => 'Category deleted',
    ],
    'room' => [
        'created' => 'Room created',
        'updated' => 'Room changed',
        'deleted' => 'Room deleted',
    ],
    'room_booking' => [
        'created' => 'Room booked',
        'updated' => 'Room booking changed',
        'deleted' => 'Room booking cancelled',
    ],
    'user' => [
        'created' => 'User created',
        'restored' => 'Deleted account restored',
        'updated' => 'User changed',
        'deleted' => 'User deleted',
        'self_deleted' => 'Account deleted by user',
        'profile_updated' => 'Profile changed',
        'email_changed' => 'Email changed',
        'password_changed' => 'Password changed',
        'admin_created_cli' => 'Admin created via console',
    ],
    'session' => [
        'revoked' => 'Session ended',
        'revoked_others' => 'Other sessions ended',
        'revoked_by_admin' => 'Sessions ended by admin',
    ],
    'settings' => [
        'updated' => 'Settings changed',
    ],
];
