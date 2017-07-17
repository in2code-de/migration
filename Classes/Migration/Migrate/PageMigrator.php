<?php
namespace In2code\In2template\Migration\Migrate;

use In2code\In2template\Migration\Migrate\PropertyHelper\CreateReferenceContentElementsFromTvReferencesPropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\CrossReplacePropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\ReplaceOnConditionPropertyHelper;

/**
 * Class PageMigrator
 */
class PageMigrator extends AbstractMigrator implements MigratorInterface
{

    /**
     * Table to migrate
     *
     * @var string
     */
    protected $tableName = 'pages';

    /**
     * Simply copy values from one to another column
     *
     * @var array
     */
    protected $mapping = [
    ];

    /**
     * PropertyHelpers are called after initial build via mapping
     *
     *      "newProperty" => [
     *          [
     *              "className" => class1::class,
     *              "configuration => ["red"]
     *          ],
     *          [
     *              "className" => class2::class
     *          ]
     *      ]
     *
     * @var array
     */
    protected $propertyHelpers = [
        '*' => [
            [
                'className' => CreateReferenceContentElementsFromTvReferencesPropertyHelper::class
            ]
        ],
        'backend_layout' => [
            [
                // Set pages.backend_layout from pages.tx_templavoila_to
                'className' => CrossReplacePropertyHelper::class,
                'configuration' => [
                    'search' => [
                        'field' => 'tx_templavoila_to',
                        'values' => [
                            '31', // 2 Columns
                            '1', // 3 Columns
                            '8' // Homepage
                        ]
                    ],
                    'replace' => [
                        'field' => 'backend_layout',
                        'values' => [
                            'in2template__default',
                            'in2template__default',
                            'in2template__homepage'
                        ]
                    ],
                    'defaultValue' => ''
                ]
            ],
            [
                // Facility layouts
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'uid' => [
                            '105',
                            '104',
                            '3874',
                            '102',
                            '101',
                            '100',
                            '119',
                            '118',
                            '48',
                            '49',
                            '8421',
                            '917',
                            '916',
                            '915',
                            '914',
                            '913',
                            '912',
                            '944',
                            '938'
                        ]
                    ],
                    'replace' => [
                        'value' => 'in2template__facilities'
                    ]
                ]
            ]
        ],
        'backend_layout_next_level' => [
            [
                // Set pages.backend_layout_next_level from pages.tx_templavoila_next_to
                'className' => CrossReplacePropertyHelper::class,
                'configuration' => [
                    'search' => [
                        'field' => 'tx_templavoila_next_to',
                        'values' => [
                            '31', // 2 Columns
                            '1', // 3 Columns
                            '8' // Homepage
                        ]
                    ],
                    'replace' => [
                        'field' => 'backend_layout_next_level',
                        'values' => [
                            'in2template__default',
                            'in2template__default',
                            'in2template__homepage'
                        ]
                    ],
                    'defaultValue' => ''
                ]
            ]
        ]
    ];
}
