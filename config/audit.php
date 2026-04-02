<?php

use App\Models\CompanyProfileChangeRequest;
use App\Models\Dispatch;
use App\Models\DispatchChangeRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\AuditLog;

return [
    'discover_models' => true,

    'models' => [
        Dispatch::class,
        DispatchChangeRequest::class,
        CompanyProfileChangeRequest::class,
        User::class,
        Role::class,
    ],

    'excluded_models' => [
        AuditLog::class,
    ],

    'ignored_fields' => [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
    ],

    'sensitive_fields' => [
        'password',
        'api_token',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ],

    'request_logging' => [
        'enabled' => true,
        'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
        'excluded_route_names' => [
            'audit-logs.index',
        ],
        'excluded_paths' => [
            'audit-logs',
        ],
    ],
];
