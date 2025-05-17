<?php

return [
    // Default values if not given from CLI
    'configuration' => [
        'key' => '',
        'dryrun' => true,
        'limitToRecord' => null,
        'limitToPage' => null,
        'recursive' => false,
        'messageTypes' => [
            'message',
            'note',
            'warning',
            'error',
        ],
    ],

    // Define your migrations
    'migrations' => [
        [
            'className' => \In2code\Migration\Migration\Importer\NewsImporter::class,
            'keys' => [
                'news',
            ],
        ],
        [
            'className' => \In2code\Migration\Migration\Migrator\PageMigrator::class,
            'keys' => [
                'page',
            ],
        ],
        [
            'className' => \In2code\Migration\Migration\Migrator\ContentMigrator::class,
            'keys' => [
                'content',
            ],
        ],
    ],
];
