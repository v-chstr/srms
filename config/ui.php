<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paper Status Display
    |--------------------------------------------------------------------------
    |
    | Centralized status labels and colors used across badge components,
    | table rows, paper detail panels, and research cards.
    |
    | 'label'      — short form for tables and filters
    | 'label_long' — descriptive form for detail panels and cards
    | 'bg'         — background class for the badge
    | 'text'       — text color class for the badge
    |
    */

    'statuses' => [
        'pending'  => [
            'label'      => 'Pending',
            'label_long' => 'Pending Review',
            'bg'         => 'bg-status-pending',
            'text'       => 'text-status-pending-fg',
        ],
        'revision' => [
            'label'      => 'Revision',
            'label_long' => 'Needs Revision',
            'bg'         => 'bg-status-revision',
            'text'       => 'text-status-revision-fg',
        ],
        'approved' => [
            'label'      => 'Approved',
            'label_long' => 'Approved',
            'bg'         => 'bg-status-approved',
            'text'       => 'text-status-approved-fg',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Display
    |--------------------------------------------------------------------------
    */

    'roles' => [
        'admin'   => ['label' => 'Admin',   'bg' => 'bg-primary-100', 'text' => 'text-primary-800'],
        'adviser' => ['label' => 'Adviser', 'bg' => 'bg-accent-100',  'text' => 'text-accent-700'],
        'student' => ['label' => 'Student', 'bg' => 'bg-sky-100',     'text' => 'text-sky-800'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Review Decisions
    |--------------------------------------------------------------------------
    */

    'review_decisions' => [
        'approved'          => 'Approved',
        'revision_required' => 'Revision Required',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filter Placeholders & Fallback Copy
    |--------------------------------------------------------------------------
    |
    | Shared strings used in multiple views. Centralised here so wording
    | stays consistent across admin, adviser, student, and archive surfaces.
    |
    */

    'placeholders' => [
        'all_courses'   => 'All courses',
        'all_statuses'  => 'All statuses',
        'all_roles'     => 'All roles',
        'all_queues'    => 'All queues',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Statuses
    |--------------------------------------------------------------------------
    */

    'queue_statuses' => [
        'pending'   => ['label' => 'Pending',   'bg' => 'bg-yellow-100', 'text' => 'text-yellow-800'],
        'active'    => ['label' => 'Active',     'bg' => 'bg-blue-100',   'text' => 'text-blue-800'],
        'completed' => ['label' => 'Completed',  'bg' => 'bg-green-100',  'text' => 'text-green-800'],
    ],

    'fallbacks' => [
        'not_assigned'  => 'Not assigned',
        'no_abstract'   => 'No abstract provided.',
        'no_course'     => 'No course',
    ],

];
