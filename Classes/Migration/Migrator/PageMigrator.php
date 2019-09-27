<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\Migrator;

use In2code\Migration\Migration\PropertyHelpers\ReplaceOnConditionPropertyHelper;

/**
 * Class PageMigrator
 * as an example class for a page migrator
 */
class PageMigrator extends AbstractMigrator implements MigratorInterface
{
    /**
     * @var string
     */
    protected $tableName = 'pages';

    /**
     * @var array
     */
    protected $values = [
        'hidden' => '1'
    ];

    /**
     * @var array
     */
    protected $propertyHelpers = [
        'seo_title' => [
            [
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'deleted' => [
                            1
                        ]
                    ],
                    'replace' => [
                        'value' => '0'
                    ]
                ]
            ]
        ]
    ];
}
