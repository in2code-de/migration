<?php
namespace In2code\In2template\Migration\Migrate;

use In2code\In2template\Migration\Migrate\PropertyHelper\ChangeFileRelationPropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\CheckifProductPagePropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\ProductDescriptionPropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\ProductNamePropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\ReplaceOnConditionPropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\ReplacePropertyHelper;

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
        'tx_udgtemplate_landingpage_footer_page' => 'template_footer_page',
        'tx_udgtemplate_landingpage_system_page' => 'template_impressum_navigation_page',
        'tx_udgtemplate_landingpage_global_phone' => 'template_footer_slogan'
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
        'backend_layout' => [
            [
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'backend_layout' => [
                            'udg_template__landingpage'
                        ]
                    ],
                    'replace' => [
                        'value' => 'in2template__Landingpage'
                    ]
                ]
            ],
            [
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'backend_layout' => [
                            'udg_template__default'
                        ]
                    ],
                    'replace' => [
                        'value' => ''
                    ]
                ]
            ],
            [
                'className' => CheckifProductPagePropertyHelper::class,
                'configuration' => [
                    'replace' => 'in2template__Productpage'
                ]
            ],
            [
                // Page HOME
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'uid' => [
                            '8091'
                        ]
                    ],
                    'replace' => [
                        'value' => 'in2template__Homepage'
                    ]
                ]
            ],
        ],
        'backend_layout_next_level' => [
            [
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'backend_layout_next_level' => [
                            'udg_template__landingpage'
                        ]
                    ],
                    'replace' => [
                        'value' => 'in2template__Landingpage'
                    ]
                ]
            ],
            [
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'backend_layout_next_level' => [
                            'udg_template__default'
                        ]
                    ],
                    'replace' => [
                        'value' => ''
                    ]
                ]
            ],
            [
                // Page HOME
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'uid' => [
                            '8091'
                        ]
                    ],
                    'replace' => [
                        'value' => 'in2template__Subpage'
                    ]
                ]
            ],
        ],
        'product_name' => [
            [
                'className' => ProductNamePropertyHelper::class
            ]
        ],
        'product_description' => [
            [
                'className' => ProductDescriptionPropertyHelper::class
            ]
        ],
        'template_theme' => [
            [
                'className' => ReplacePropertyHelper::class,
                'configuration' => [
                    'search' => [
                        '',
                        'black',
                        'templateNetbank',
                        'templateSantander',
                        'templateBankenvertrieb',
                        'templateVtb',
                        'templatePortal'
                    ],
                    'replace' => [
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        't-portal'
                    ],
                    'startField' => 'tx_udgtemplate_landingpage_theme'
                ]
            ]
        ],
        'template_logo' => [
            [
                'className' => ChangeFileRelationPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'backend_layout' => 'in2template__Landingpage'
                    ],
                    'from' => [
                        'tablenames' => 'pages',
                        'fieldname' => 'image',
                        'uid_foreign' => '{uid}'
                    ],
                    'to' => [
                        'tablenames' => 'pages',
                        'fieldname' => 'template_logo',
                        'uid_foreign' => '{uid}'
                    ]
                ]
            ]
        ]
    ];
}
