<?php
namespace In2code\In2template\Migration\Migrate;

use In2code\In2template\Migration\Migrate\PropertyHelper\FlexFormGeneratorPropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\FlexFormHelper\GetFaqCategoriesFlexFormHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\HideOnPropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\NotSupportedPropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\ReplaceCssClassesInHtmlStringPropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\ReplaceOnConditionPropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\ReplacePropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\VideoPluginPropertyHelper;

/**
 * Class ContentMigrator
 */
class ContentMigrator extends AbstractMigrator implements MigratorInterface
{

    /**
     * Table to migrate
     *
     * @var string
     */
    protected $tableName = 'tt_content';

    /**
     * Simply copy values from one to another column
     *
     * @var array
     */
    protected $mapping = [
        'tx_slider_content_slider_items' => 'tx_in2template_slider_content_items',
        'tx_slider_content_slider_uid' => 'tx_in2template_slider_content_uid'
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
        'deleted' => [
            [
                // Remove content with unsupported colPos
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'colPos' => [
                            '10', // Megadropdown
                            '30' // Toolbar
                        ]
                    ],
                    'replace' => [
                        'value' => 1
                    ]
                ]
            ]
        ],
        'colPos' => [
            [
                'className' => ReplacePropertyHelper::class,
                'configuration' => [
                    'search' => [
                        '20', // Slider
                        '0', // Seiteninhalt
                    ],
                    'replace' => [
                        1, // Header
                        0, // Content
                    ],
                ]
            ],
        ],
        'CType' => [
            [
                // CSC => FSC (convert to textmedia)
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    // Replace only if this condition matches
                    'conditions' => [
                        'CType' => [
                            'text',
                            'textpic',
                            'image',
                            'media',
                            'header',
                            'table',
                            'bullets'
                        ]
                    ],
                    // Replace in current field "CType"
                    'replace' => [
                        'value' => 'textmedia'
                    ]
                ]
            ],
            [
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'CType' => [
                            'in2template_isotope'
                        ]
                    ],
                    'replace' => [
                        'value' => 'mv_animation'
                    ]
                ]
            ],
            [
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'CType' => [
                            'mv_quicklinks'
                        ]
                    ],
                    'replace' => [
                        'value' => 'quicklinks'
                    ]
                ]
            ]
        ],
        'list_type' => [
            //[
            //    'className' => ReplacePropertyHelper::class,
            //    'configuration' => [
            //        'search' => [
            //            'udgmvexpertsearch_expertsearch',
            //        ],
            //        'replace' => [
            //            'in2mvadpsearch_pi2',
            //        ]
            //    ]
            //],
            [
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'CType' => [
                            'list'
                        ],
                        'list_type' => [
                            'udgmvfaq_faq'
                        ]
                    ],
                    'replace' => [
                        'value' => 'in2faq_pi1'
                    ]
                ]
            ],
            [
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'CType' => [
                            'list'
                        ],
                        'list_type' => [
                            'udgmvdocuments_documents'
                        ]
                    ],
                    'replace' => [
                        'value' => 'in2mvdocumentsearch_pi1'
                    ]
                ]
            ]
        ],
        'bodytext' => [
            [
                'className' => ReplaceCssClassesInHtmlStringPropertyHelper::class,
                'configuration' => [
                    'search' => [
                        'btn',
                        'btn-neg',
                        'btn-green',
                        'btn-blue',
                        'contenttable-2',
                        'contenttable-3',
                        'ul-check'
                    ],
                    'replace' => [
                        'c-button',
                        'c-button--white',
                        'c-button--green',
                        '',
                        'u-table-transparent',
                        'u-table-blue',
                        'u-list-check'
                    ]
                ]
            ]
        ],
        'accordion_foldable' => [
            [
                'className' => ReplacePropertyHelper::class,
                'configuration' => [
                    'search' => [
                        '0',
                        '1',
                        '2',
                    ],
                    'replace' => [
                        '',
                        'js-accordion',
                        'js-accordion-mobile'
                    ],
                    'startField' => 'foldable'
                ]
            ],
            [
                'className' => ReplacePropertyHelper::class,
                'configuration' => [
                    'search' => [
                        'accordion_panel',
                        'accordion_panel_group',
                        'unfold_element'
                    ],
                    'replace' => [
                        'js-accordion',
                        'js-accordion',
                        'js-show-more'
                    ],
                    'startField' => 'tx_gridelements_backend_layout'
                ]
            ],
        ],
        'text_column_count' => [
            [
                'className' => ReplacePropertyHelper::class,
                'configuration' => [
                    'search' => [
                        '30',
                        '31',
                        '0',
                    ],
                    'replace' => [
                        'u-two-text-columns',
                        'u-three-text-columns',
                        '',
                    ],
                    'startField' => 'section_frame'
                ]
            ],
        ],
        'pi_flexform' => [
            [
                'className' => VideoPluginPropertyHelper::class,
            ],
            //[
            //    'className' => NotSupportedPropertyHelper::class,
            //    'configuration' => [
            //        'conditions' => [
            //            [
            //                'CType' => 'list',
            //                'list_type' => 'in2mvadpsearch_pi2'
            //            ]
            //        ],
            //        'properties' => [
            //            'pi_flexform' => ''
            //        ]
            //    ]
            //],
            [
                // Build FlexForm for FAQ plugin
                'className' => FlexFormGeneratorPropertyHelper::class,
                'configuration' => [
                    'condition' => [
                        'CType' => 'list',
                        'list_type' => 'in2faq_pi1'
                    ],
                    'flexFormTemplate' => 'EXT:in2template/Resources/Private/Migration/FlexForms/Faq.xml',
                    'helpers' => [
                        [
                            'className' => GetFaqCategoriesFlexFormHelper::class,
                            'configuration' => [
                                'variableName' => 'categories'
                            ]
                        ]
                    ]
                ]
            ],
            [
                // Clear FlexForm for documentsearch plugin
                'className' => FlexFormGeneratorPropertyHelper::class,
                'configuration' => [
                    'condition' => [
                        'CType' => 'list',
                        'list_type' => 'in2mvdocumentsearch_pi1'
                    ],
                    'flexFormTemplate' => 'EXT:in2template/Resources/Private/Migration/FlexForms/Empty.xml'
                ]
            ],
        ],
        'hide_on' => [
            [
                'className' => HideOnPropertyHelper::class,
                'configuration' => [
                    'fieldName' => 'tx_udgtemplate_hide_breakpoint'
                ]
            ]
        ],
        'tx_gridelements_backend_layout' => [
            [
                'className' => ReplacePropertyHelper::class,
                'configuration' => [
                    'search' => [
                        'accordion_panel',
                        'accordion_panel_group',
                        'calculator',
                        'container',
                        'grid_12',
                        'grid_2_8_2',
                        'grid_3_3_3_3',
                        'grid_4_4_4',
                        'grid_4_8',
                        'grid_6_6',
                        'grid_8_4',
                        'grid_9_3',
                        'grid_footer',
                        'grid_megadropdown',
                        'slider_panel',
                        'slider_panel_group',
                        'teaser_blue',
                        'unfold_element',
                        'in2grids_100',
                        'in2grids_50_50',
                        'in2grids_33_33_33',
                        'in2grids_66_33',
                        'in2grids_33_66',
                        'in2grids_25_25_25_25',
                        'in2grids_75_25',
                        'in2grids_25_75',
                        'in2grids_16_66_16'

                    ],
                    'replace' => [
                        'in2grids_100',
                        'in2grids_100',
                        'calculator',
                        'in2grids_100',
                        'in2grids_100',
                        'in2grids_16_66_16',
                        'in2grids_25_25_25_25',
                        'in2grids_33_33_33',
                        'in2grids_33_66',
                        'in2grids_50_50',
                        'in2grids_75_25',
                        'in2grids_75_25',
                        'grid_footer',
                        'grid_megadropdown',
                        'slider_panel',
                        'slider_panel_group',
                        'teaser_blue',
                        'in2grids_100',
                        'in2grids_100',
                        'in2grids_50_50',
                        'in2grids_33_33_33',
                        'in2grids_66_33',
                        'in2grids_33_66',
                        'in2grids_25_25_25_25',
                        'in2grids_75_25',
                        'in2grids_25_75',
                        'in2grids_16_66_16'
                    ]
                ]
            ]
        ],
        'image_hover' => [
            [
                'className' => ReplacePropertyHelper::class,
                'configuration' => [
                    'search' => [
                        '0',
                        '1',
                        '2'
                    ],
                    'replace' => [
                        '',
                        'container',
                        'gradient'
                    ]
                ]
            ]
        ],
        'uid' => [
            [
                'className' => NotSupportedPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        [
                            'tx_gridelements_backend_layout' => 'calculator'
                        ]
                    ],
                    'properties' => [
                        'CType' => 'textmedia',
                        'list_type' => '',
                        'bodytext' => '###INSERT_CALCULATOR###',
                        'header' => 'Calculator marker for extended application',
                        'header_layout' => 100
                    ]
                ]
            ],
            [
                'className' => NotSupportedPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        [
                            'tx_gridelements_backend_layout' => 'grid_footer'
                        ],
                        [
                            'tx_gridelements_backend_layout' => 'grid_megadropdown'
                        ],
                        [
                            'tx_gridelements_backend_layout' => 'slider_panel'
                        ],
                        [
                            'tx_gridelements_backend_layout' => 'slider_panel_group'
                        ],
                        [
                            'tx_gridelements_backend_layout' => 'teaser_blue'
                        ]
                    ],
                    'properties' => [
                        'CType' => 'textmedia',
                        'list_type' => '',
                        'bodytext' =>
                            '<p style="background: red; color: white;">This element is not supported any more!</p>',
                        'header' => 'This element is not supported any more',
                        'header_layout' => 100
                    ]
                ]
            ],
            [
                'className' => NotSupportedPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        [
                            'CType' => 'list',
                            'list_type' => 'udgmvproducts_product'
                        ]
                    ],
                    'properties' => [
                        'CType' => 'textmedia',
                        'list_type' => '',
                        'bodytext' =>
                            '<p style="background: red; color: white;">This element is not supported any more!</p>',
                        'header' => 'This element is not supported any more',
                        'header_layout' => 100
                    ]
                ]
            ],
            [
                'className' => NotSupportedPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        [
                            'CType' => 'menu'
                        ],
                        [
                            'CType' => 'menu_18'
                        ],
                        [
                            'CType' => 'menu_pages'
                        ],
                        [
                            'CType' => 'menu_sitemap'
                        ],
                        [
                            'CType' => 'menu_subpages'
                        ]
                    ],
                    'properties' => [
                        'CType' => 'textmedia',
                        'list_type' => '',
                        'bodytext' =>
                            '<p style="background: red; color: white;">This element is not supported any more!</p>',
                        'header' => 'This element is not supported any more',
                        'header_layout' => 100
                    ]
                ]
            ],
            [
                'className' => NotSupportedPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        [
                            'CType' => 'list',
                            'list_type' => 'udgmvexpertsearch_expertsearch'
                        ]
                    ],
                    'properties' => [
                        'CType' => 'textmedia',
                        'list_type' => '',
                        'bodytext' =>
                            '<p style="background: red; color: white;">This element is not supported any more!</p>',
                        'header' => 'This element is not supported any more',
                        'header_layout' => 100
                    ]
                ]
            ]
        ]
    ];
}
