<?php

return [
    /*
    |--------------------------------------------------------------------------
    | UAT Demo Password
    |--------------------------------------------------------------------------
    |
    | UAT accounts are intentionally unavailable unless a dedicated password
    | is supplied. The UAT seeder also refuses to run in production.
    |
    */
    'demo_password' => env('UAT_DEMO_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Controlled UAT Accounts
    |--------------------------------------------------------------------------
    |
    | These identities exist only when UatScenarioSeeder is explicitly run.
    | Passwords are never stored here and are supplied at runtime.
    |
    */
    'accounts' => [
        'admin' => [
            'name' => 'UAT Admin',
            'email' => 'uat.admin@example.test',
        ],
        'karyawan' => [
            'name' => 'UAT Employee',
            'email' => 'uat.employee@example.test',
        ],
        'supervisor' => [
            'name' => 'UAT Supervisor',
            'email' => 'uat.supervisor@example.test',
        ],
        'hrd' => [
            'name' => 'UAT HRD',
            'email' => 'uat.hrd@example.test',
        ],
        'pimpinan' => [
            'name' => 'UAT Pimpinan',
            'email' => 'uat.pimpinan@example.test',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Controlled UAT Scenario Contract
    |--------------------------------------------------------------------------
    |
    | Seeder and readiness validation share this contract to prevent ticket
    | names or expected states from drifting independently.
    |
    */
    'scenarios' => [
        'submitted' => [
            'ticket' => 'UAT-SUBMITTED-001',
            'status' => 'submitted',
        ],
        'assigned' => [
            'ticket' => 'UAT-ASSIGNED-001',
            'status' => 'assigned',
        ],
        'waiting_input' => [
            'ticket' => 'UAT-WAITING-INPUT-001',
            'status' => 'waiting_input',
        ],
        'resolved_feedback' => [
            'ticket' => 'UAT-RESOLVED-FEEDBACK-001',
            'status' => 'resolved',
            'feedback_outcome' => 'needs_follow_up',
        ],
        'escalated' => [
            'ticket' => 'UAT-ESCALATED-001',
            'status' => 'in_progress',
            'active_escalation' => true,
        ],
    ],
];
