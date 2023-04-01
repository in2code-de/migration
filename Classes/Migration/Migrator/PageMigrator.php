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
    protected string $tableName = 'pages';

    protected array $values = [
        'hidden' => '1',
    ];

    protected array $propertyHelpers = [
        'seo_title' => [
            [
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'deleted' => [
                            1,
                        ],
                    ],
                    'replace' => [
                        'value' => '0',
                    ],
                ],
            ],
        ],
    ];
}
