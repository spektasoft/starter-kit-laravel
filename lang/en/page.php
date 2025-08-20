<?php

declare(strict_types=1);

return [
    'resource' => [
        'content' => 'Content',
        'model_label' => 'Page|Pages',
        'status' => [
            'draft' => 'Draft',
            'publish' => 'Publish',
        ],
        'title' => 'Title',
    ],
    'action' => [
        'edit' => 'Edit',
        'view' => 'View',
    ],
    'export_completed' => 'Your page export has completed and :successful_rows row(s) exported.',
    'export_failed' => ':failed_rows row(s) failed to export.',
    'import_completed' => ':successful_rows row(s) imported.',
    'import_failed' => ':failed_rows row(s) failed to import.',
];
