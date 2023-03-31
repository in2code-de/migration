<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\Migrator;

use In2code\Migration\Migration\PropertyHelpers\ReplaceCssClassesInHtmlStringPropertyHelper;

/**
 * Class ContentMigrator
 * as an example class for migration of tt_content elements
 */
class ContentMigrator extends AbstractMigrator implements MigratorInterface
{
    protected string $tableName = 'tt_content';

    protected array $values = [
        'editlock' => '0'
    ];

    protected array $propertyHelpers = [
        'bodytext' => [
            [
                'className' => ReplaceCssClassesInHtmlStringPropertyHelper::class,
                'configuration' => [
                    'condition' => [
                        'CType' => [
                            'textpic',
                            'text',
                            'textmedia'
                        ]
                    ],
                    'tags' => [
                        'a'
                    ],
                    'search' => [
                        'c-button--button',
                        'c-button'
                    ],
                    'replace' => [
                        'btn-primary',
                        'btn'
                    ]
                ]
            ]
        ]
    ];
}
