<?php
namespace In2code\Migration\Migration;

use In2code\Migration\Migration\Import\CalendarImporter;
use In2code\Migration\Migration\Migrate\CalendarCategoriesMigrator;
use In2code\Migration\Migration\Migrate\ContentMigrator;
use In2code\Migration\Migration\Migrate\NewsMigrator;
use In2code\Migration\Migration\Migrate\PageMigrator;

/**
 * Class Starter
 */
class Starter extends AbstractStarter
{

    /**
     * Define your Migrators and Importers here (Orderings will be respected)
     *
     * Example:
     *  [
     *      'className' => NewsImporter::class,
     *      'configuration' => [
     *          'migrationClassKey' => 'news'
     *      ]
     *  ]
     *
     * If you want to set dryrun or limitToRecord for testing (overwrites values form CommandController):
     *  [
     *      'className' => UserMigrator::class,
     *      'configuration' => [
     *          'migrationClassKey' => 'user',
     *          'limitToRecord' => 1123,
     *          'dryrun' => false
     *      ]
     *  ]
     *
     *
     * @var array
     */
    protected $migrationClasses = [
        [
            'className' => PageMigrator::class,
            'configuration' => [
                'migrationClassKey' => 'page'
            ]
        ],
        [
            'className' => ContentMigrator::class,
            'configuration' => [
                'migrationClassKey' => 'content'
            ]
        ],
        [
            'className' => NewsMigrator::class,
            'configuration' => [
                'migrationClassKey' => 'news'
            ]
        ],
        [
            'className' => CalendarImporter::class,
            'configuration' => [
                'migrationClassKey' => 'calendar'
            ]
        ],
        [
            'className' => CalendarCategoriesMigrator::class,
            'configuration' => [
                'migrationClassKey' => 'calendarcategories'
            ]
        ]
    ];
}
