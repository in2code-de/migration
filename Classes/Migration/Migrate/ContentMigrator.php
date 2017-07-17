<?php
namespace In2code\In2template\Migration\Migrate;

use In2code\In2template\Migration\Migrate\PropertyHelper\ClearPropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\CreateFromTvFlexFormPropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\CreateRteListFromCtypeBulletsPropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\CreateRteTableFromCtypeTablePropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper\BuildAccordeonGridForContentElementFceHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper\BuildContentElementFceHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper\BuildContentElementsInTwoColumnGridFceHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper\BuildGridContainerAndSplitContentFceHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper\BuildImageRelationsFceHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper\SetPropertyFceHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper\SetPropertyFromFlexFormPropertyFceHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper\TemplateDmigrationFceHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\FlexFormGeneratorPropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\FlexFormHelper\GetFacilityFromTypoScriptFlexFormHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\NotSupportedPropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\RemoveBrokenLinksPropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\ReplaceOnConditionPropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\ReplacePropertyHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\UpdateColPosFromTemplaVoilaPageSettingsPropertyHelper;

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
        'colPos' => [
            [
                'className' => UpdateColPosFromTemplaVoilaPageSettingsPropertyHelper::class,
                'configuration' => [
                    'colPosMapping' => [
                        'field_content' => 0,
                        'field_sidebarRight' => 1
                    ],
                    'ifNotMatching' => [
                        'hidden' => 1
                    ]
                ]
            ]
        ],
        'pi_flexform' => [
            [
                // Build FlexForm for News plugin
                'className' => FlexFormGeneratorPropertyHelper::class,
                'configuration' => [
                    'condition' => [
                        'CType' => 'list',
                        'list_type' => '9' // Tt_news plugin
                    ],
                    'flexFormTemplate' => 'EXT:in2template/Resources/Private/Migration/FlexForms/News.xml',
                    'additionalMapping' => [
                        [
                            // create new variable {additionalMapping.switchableControllerActions}
                            'variableName' => 'switchableControllerActions',
                            'keyField' => 'flexForm:what_to_display', // "flexForm:path/path" or: "row:uid"
                            'mapping' => [
                                'LIST' => 'News->list',
                                'LIST2' => 'News->list',
                                'LIST3' => 'News->list',
                                'HEADER_LIST' => 'News->list',
                                'LATEST' => 'News->list',
                                'SINGLE' => 'News->detail',
                                'SINGLE2' => 'News->detail',
                                'AMENU' => 'News->list',
                                'SEARCH' => 'News->searchForm',
                                'CATMENU' => 'Category->list',
                                'VERSION_PREVIEW' => 'News->list',
                                'TEMPLATE_M' => 'News->list',
                                'TEMPLATE_N' => 'News->list'
                            ]
                        ],
                        [
                            // create new variable {additionalMapping.categorySetting}
                            'variableName' => 'categorySetting',
                            'keyField' => 'flexForm:categoryMode', // "flexForm:path/path" or: "row:uid"
                            'mapping' => [
                                '0' => '', // show all
                                '1' => 'or', // show from categories (OR)
                                '2' => 'and', // show from categories (AND)
                                '-1' => 'notand', // don't show from categories (AND)
                                '-2' => 'notor', // don't show from categories (OR)
                            ]
                        ],
                        [
                            // create new variable {additionalMapping.archiveSetting}
                            'variableName' => 'categorySetting',
                            'keyField' => 'flexForm:archive', // "flexForm:path/path" or: "row:uid"
                            'mapping' => [
                                '0' => '', // don't care
                                '1' => 'archived', // archived only
                                '-1' => 'active', // not archived only
                            ]
                        ]
                    ]
                ]
            ],
            [
                // Build FlexForm for Contact listing
                'className' => FlexFormGeneratorPropertyHelper::class,
                'configuration' => [
                    'condition' => [
                        'CType' => 'pthskaadmin_list' // Contact plugin
                    ],
                    'flexFormTemplate' => 'EXT:in2template/Resources/Private/Migration/FlexForms/Contact.xml'
                ]
            ],
            [
                // Build FlexForm for Videolisting
                'className' => FlexFormGeneratorPropertyHelper::class,
                'configuration' => [
                    'condition' => [
                        'CType' => 'list',
                        'list_type' => 'hskalisting_pi1'
                    ],
                    'flexFormTemplate' => 'EXT:in2template/Resources/Private/Migration/FlexForms/Videolisting.xml'
                ]
            ],
            [
                // Build FlexForm for Userlisting (from given facility)
                'className' => FlexFormGeneratorPropertyHelper::class,
                'configuration' => [
                    'condition' => [
                        'CType' => 'list',
                        'list_type' => 'ptextlist_pi1',
                        'pi_flexform' => 'flexForm:settings/listIdentifier:registerOfPersonsOfFacility'
                    ],
                    'overwriteValues' => [
                        'list_type' => 'users_pi1'
                    ],
                    'flexFormTemplate' => 'EXT:in2template/Resources/Private/Migration/FlexForms/Userlisting.xml',
                    'helpers' => [
                        [
                            'className' => GetFacilityFromTypoScriptFlexFormHelper::class,
                            'configuration' => [
                                'variableName' => 'facilityUid'
                            ]
                        ]
                    ]
                ]
            ],
            [
                // Build FlexForm for Userlisting (show all)
                'className' => FlexFormGeneratorPropertyHelper::class,
                'configuration' => [
                    'condition' => [
                        'CType' => 'list',
                        'list_type' => 'ptextlist_pi1',
                        'pi_flexform' => 'flexForm:settings/listIdentifier:registerOfPersons'
                    ],
                    'overwriteValues' => [
                        'list_type' => 'users_pi1'
                    ],
                    'flexFormTemplate' => 'EXT:in2template/Resources/Private/Migration/FlexForms/Userlisting.xml',
                    'helpers' => [
                        [
                            'className' => GetFacilityFromTypoScriptFlexFormHelper::class,
                            'configuration' => [
                                'variableName' => 'facilityUid'
                            ]
                        ]
                    ]
                ]
            ],
            [
                // ptextlist_pi1/appointments
                'className' => FlexFormGeneratorPropertyHelper::class,
                'configuration' => [
                    'condition' => [
                        'CType' => 'list',
                        'list_type' => 'ptextlist_pi1',
                        'pi_flexform' => 'flexForm:settings/listIdentifier:appointments'
                    ],
                    'overwriteValues' => [
                        'CType' => 'textmedia',
                        'list_type' => '',
                        'bodytext' => '',
                        'header' => 'This element is not supported any more (ptextlist_pi1/appointments)',
                        'header_layout' => 100
                    ],
                    'flexFormTemplate' => 'EXT:in2template/Resources/Private/Migration/FlexForms/Userlisting.xml'
                ]
            ],
            [
                // ptextlist_pi1/appointmentsOfFacility
                'className' => FlexFormGeneratorPropertyHelper::class,
                'configuration' => [
                    'condition' => [
                        'CType' => 'list',
                        'list_type' => 'ptextlist_pi1',
                        'pi_flexform' => 'flexForm:settings/listIdentifier:appointmentsOfFacility'
                    ],
                    'overwriteValues' => [
                        'CType' => 'textmedia',
                        'list_type' => '',
                        'bodytext' => '',
                        'header' => 'This element is not supported any more (ptextlist_pi1/appointmentsOfFacility)',
                        'header_layout' => 100
                    ],
                    'flexFormTemplate' => 'EXT:in2template/Resources/Private/Migration/FlexForms/Userlisting.xml'
                ]
            ],
            [
                // ptextlist_pi1/downloads
                'className' => FlexFormGeneratorPropertyHelper::class,
                'configuration' => [
                    'condition' => [
                        'CType' => 'list',
                        'list_type' => 'ptextlist_pi1',
                        'pi_flexform' => 'flexForm:settings/listIdentifier:downloads'
                    ],
                    'overwriteValues' => [
                        'list_type' => 'downloads_pi1'
                    ],
                    'flexFormTemplate' => 'EXT:in2template/Resources/Private/Migration/FlexForms/Downloads.xml',
                    'helpers' => [
                        [
                            'className' => GetFacilityFromTypoScriptFlexFormHelper::class,
                            'configuration' => [
                                'variableName' => 'facilityUid'
                            ]
                        ]
                    ]
                ]
            ],
            [
                // ptextlist_pi1/downloadsOfFacility
                'className' => FlexFormGeneratorPropertyHelper::class,
                'configuration' => [
                    'condition' => [
                        'CType' => 'list',
                        'list_type' => 'ptextlist_pi1',
                        'pi_flexform' => 'flexForm:settings/listIdentifier:downloadsOfFacility'
                    ],
                    'overwriteValues' => [
                        'list_type' => 'downloads_pi1'
                    ],
                    'flexFormTemplate' => 'EXT:in2template/Resources/Private/Migration/FlexForms/Downloads.xml',
                    'helpers' => [
                        [
                            'className' => GetFacilityFromTypoScriptFlexFormHelper::class,
                            'configuration' => [
                                'variableName' => 'facilityUid'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'bodytext' => [
            [
                'className' => CreateFromTvFlexFormPropertyHelper::class,
                'configuration' => [
                    'converters' => [
                        [
                            // 2.01 Template A / tt_content.tx_templavoila_to=7
                            'condition' => [
                                'CType' => 'templavoila_pi1',
                                'tx_templavoila_to' => '7'
                            ],
                            'properties' => [
                                'imageorient' => 26,
                                'imagecols' => 1,
                                'header_layout' => 100
                            ],
                            'flexFormField' => 'tx_templavoila_flex',
                            'template' => 'EXT:in2template/Resources/Private/Migration/Fce/FieldText.html',
                            'fceHelpers' => [
                                [
                                    'className' => BuildImageRelationsFceHelper::class,
                                    'configuration' => [
                                        'flexFormKey1' => 'field_image_left_section',
                                        'flexFormKey2' => 'field_image_outer_wrapper',
                                        'tvMappingConfiguration' => [
                                            'field_image',
                                            'field_image_caption',
                                            'field_image_link'
                                        ]
                                    ]
                                ],
                                [
                                    'className' => BuildAccordeonGridForContentElementFceHelper::class
                                ]
                            ]
                        ],
                        [
                            // 2.02 Template B / tt_content.tx_templavoila_to=9
                            'condition' => [
                                'CType' => 'templavoila_pi1',
                                'tx_templavoila_to' => '9'
                            ],
                            'properties' => [
                                'imagecols' => 1,
                                'header_layout' => 100
                            ],
                            'flexFormField' => 'tx_templavoila_flex',
                            'template' => 'EXT:in2template/Resources/Private/Migration/Fce/FieldText.html',
                            'fceHelpers' => [
                                [
                                    'className' => BuildGridContainerAndSplitContentFceHelper::class,
                                    'configuration' => [
                                        'accordeon' => true,
                                        'flexFormKey1' => 'field_image_left_section',
                                        'flexFormKey2' => 'field_image_outer_wrapper',
                                        'tx_gridelements_backend_layout' => 'twoColumnsRight',
                                        'tx_gridelements_columns' => [
                                            'image' => 101,
                                            'text' => 102
                                        ],
                                        'headerLabels' => [
                                            'gridContentElement' => 'Grid Container (25%/75%)',
                                            'imageContentElement' => 'Images'
                                        ],
                                        'tvMappingConfiguration' => [
                                            'field_image',
                                            'field_image_caption',
                                            'field_image_link'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            // 2.03 Template C / tt_content.tx_templavoila_to=10
                            'condition' => [
                                'CType' => 'templavoila_pi1',
                                'tx_templavoila_to' => '10'
                            ],
                            'properties' => [
                                'imageorient' => 25,
                                'imagecols' => 1,
                                'header_layout' => 100
                            ],
                            'flexFormField' => 'tx_templavoila_flex',
                            'template' => 'EXT:in2template/Resources/Private/Migration/Fce/FieldText.html',
                            'fceHelpers' => [
                                [
                                    'className' => BuildImageRelationsFceHelper::class,
                                    'configuration' => [
                                        'flexFormKey1' => 'field_image_right_section',
                                        'flexFormKey2' => 'field_image_outer_wrapper',
                                        'tvMappingConfiguration' => [
                                            'field_image',
                                            'field_image_caption',
                                            'field_image_link'
                                        ]
                                    ]
                                ],
                                [
                                    'className' => BuildAccordeonGridForContentElementFceHelper::class
                                ]
                            ]
                        ],
                        [
                            // 2.04 Template D / tt_content.tx_templavoila_to=15
                            'condition' => [
                                'CType' => 'templavoila_pi1',
                                'tx_templavoila_to' => '15'
                            ],
                            'properties' => [
                                'imageorient' => 0,
                                'header_layout' => 100
                            ],
                            'flexFormField' => 'tx_templavoila_flex',
                            'template' => 'EXT:in2template/Resources/Private/Migration/Fce/FieldTextBottom.html',
                            'fceHelpers' => [
                                [
                                    'className' => TemplateDmigrationFceHelper::class,
                                    'configuration' => [
                                        'flexFormKey1' => 'field_image_section',
                                        'flexFormKey2' => 'field_image_wrapper',
                                        'tx_gridelements_backend_layout' => 'twoColumnsLeft',
                                        'tx_gridelements_columns' => [
                                            'imageLeft' => 101,
                                            'imageRight' => 102
                                        ],
                                        'headerLabels' => [
                                            'gridContentElement' => 'Grid Container (75%/25%)',
                                            'imageContentElement' => 'Images'
                                        ],
                                        'tvMappingConfiguration' => [
                                            'field_image_left',
                                            'field_image_left_alt_text',
                                            'field_image_left_link',
                                            'field_image_left_description',
                                            'field_image_right',
                                            'field_image_right_alt_text',
                                            'field_image_right_link',
                                            'field_image_right_description'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            // 2.06 Template F / tt_content.tx_templavoila_to=12
                            'condition' => [
                                'CType' => 'templavoila_pi1',
                                'tx_templavoila_to' => '12'
                            ],
                            'properties' => [
                                'imageorient' => 8,
                                'imagecols' => 3,
                                'header_layout' => 100
                            ],
                            'flexFormField' => 'tx_templavoila_flex',
                            'template' => 'EXT:in2template/Resources/Private/Migration/Fce/FieldTextTop.html',
                            'fceHelpers' => [
                                [
                                    'className' => BuildImageRelationsFceHelper::class,
                                    'configuration' => [
                                        'flexFormKey1' => 'field_image_section',
                                        'flexFormKey2' => 'field_image_wrapper',
                                        'tvMappingConfiguration' => [
                                            'field_image_left',
                                            'field_image_left_caption',
                                            'field_image_left_link',
                                            'field_image_center',
                                            'field_image_center_caption',
                                            'field_image_center_link',
                                            'field_image_right',
                                            'field_image_right_caption',
                                            'field_image_right_link'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            // 2.07 Template G / tt_content.tx_templavoila_to=17
                            'condition' => [
                                'CType' => 'templavoila_pi1',
                                'tx_templavoila_to' => '17'
                            ],
                            'properties' => [
                                'imageorient' => 8,
                                'imagecols' => 2,
                                'header_layout' => 100
                            ],
                            'flexFormField' => 'tx_templavoila_flex',
                            'template' => 'EXT:in2template/Resources/Private/Migration/Fce/FieldTextTop.html',
                            'fceHelpers' => [
                                [
                                    'className' => BuildImageRelationsFceHelper::class,
                                    'configuration' => [
                                        'flexFormKey1' => 'field_image_section',
                                        'flexFormKey2' => 'field_image_wrapper',
                                        'tvMappingConfiguration' => [
                                            'field_image_left',
                                            'field_image_left_caption',
                                            'field_image_left_link',
                                            'field_image_right',
                                            'field_image_right_caption',
                                            'field_image_right_link'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            // 2.08 Template H / tt_content.tx_templavoila_to=18
                            'condition' => [
                                'CType' => 'templavoila_pi1',
                                'tx_templavoila_to' => '18'
                            ],
                            'properties' => [
                                'imageorient' => 8,
                                'imagecols' => 3,
                                'header_layout' => 100
                            ],
                            'flexFormField' => 'tx_templavoila_flex',
                            'template' => 'EXT:in2template/Resources/Private/Migration/Fce/FieldTextTop.html',
                            'fceHelpers' => [
                                [
                                    'className' => BuildImageRelationsFceHelper::class,
                                    'configuration' => [
                                        'flexFormKey1' => 'field_image_section',
                                        'flexFormKey2' => 'field_image_wrapper',
                                        'tvMappingConfiguration' => [
                                            'field_image_left',
                                            'field_image_left_caption',
                                            'field_image_left_link',
                                            'field_image_center',
                                            'field_image_center_caption',
                                            'field_image_center_link',
                                            'field_image_right',
                                            'field_image_right_caption',
                                            'field_image_right_link'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            // 2.09 Template I / tt_content.tx_templavoila_to=19
                            'condition' => [
                                'CType' => 'templavoila_pi1',
                                'tx_templavoila_to' => '19'
                            ],
                            'properties' => [
                                'imageorient' => 0,
                                'imagecols' => 1,
                                'header_layout' => 100
                            ],
                            'flexFormField' => 'tx_templavoila_flex',
                            'template' => 'EXT:in2template/Resources/Private/Migration/Fce/FieldText.html',
                            'fceHelpers' => [
                                [
                                    'className' => BuildImageRelationsFceHelper::class,
                                    'configuration' => [
                                        'flexFormKey1' => 'field_image_section',
                                        'flexFormKey2' => 'field_image_wrapper',
                                        'tvMappingConfiguration' => [
                                            'field_image_top',
                                            'field_image_alt_text',
                                            'field_image_link',
                                            'field_image_description'
                                        ]
                                    ]
                                ],
                                [
                                    'className' => BuildAccordeonGridForContentElementFceHelper::class
                                ]
                            ]
                        ],
                        [
                            // 2.10 Template J / tt_content.tx_templavoila_to=20
                            'condition' => [
                                'CType' => 'templavoila_pi1',
                                'tx_templavoila_to' => '20'
                            ],
                            'properties' => [
                                'imageorient' => 8,
                                'imagecols' => 1,
                                'header_layout' => 100
                            ],
                            'flexFormField' => 'tx_templavoila_flex',
                            'template' => 'EXT:in2template/Resources/Private/Migration/Fce/FieldTextTop.html',
                            'fceHelpers' => [
                                [
                                    'className' => BuildImageRelationsFceHelper::class,
                                    'configuration' => [
                                        'flexFormKey1' => 'field_image_section',
                                        'flexFormKey2' => 'field_image_wrapper',
                                        'tvMappingConfiguration' => [
                                            'field_image',
                                            'field_image_alt_text',
                                            'field_image_link',
                                            'field_image_description'
                                        ]
                                    ]
                                ],
                                [
                                    'className' => BuildAccordeonGridForContentElementFceHelper::class,
                                    'configuration' => [
                                        'enforceDummyContainer' => true
                                    ]
                                ],
                                [
                                    'className' => BuildContentElementFceHelper::class,
                                    'configuration' => [
                                        'template' =>
                                            'EXT:in2template/Resources/Private/Migration/Fce/FieldTextBottom.html',
                                        'headerLabel' => 'Text bottom'
                                    ]
                                ]
                            ]
                        ],
                        [
                            // 2.11 Template K / tt_content.tx_templavoila_to=21
                            'condition' => [
                                'CType' => 'templavoila_pi1',
                                'tx_templavoila_to' => '21'
                            ],
                            'properties' => [
                                'imageorient' => 8,
                                'imagecols' => 1,
                                'header_layout' => 100
                            ],
                            'flexFormField' => 'tx_templavoila_flex',
                            'template' => 'EXT:in2template/Resources/Private/Migration/Fce/FieldTextTop.html',
                            'fceHelpers' => [
                                [
                                    'className' => BuildImageRelationsFceHelper::class,
                                    'configuration' => [
                                        'flexFormKey1' => 'field_image_section',
                                        'flexFormKey2' => 'field_image_wrapper',
                                        'tvMappingConfiguration' => [
                                            'field_image',
                                            'field_image_alt_text',
                                            'field_image_link',
                                            'field_image_description'
                                        ]
                                    ]
                                ],
                                [
                                    'className' => BuildAccordeonGridForContentElementFceHelper::class,
                                    'configuration' => [
                                        'enforceDummyContainer' => true
                                    ]
                                ],
                                [
                                    'className' => BuildContentElementFceHelper::class,
                                    'configuration' => [
                                        'template' =>
                                            'EXT:in2template/Resources/Private/Migration/Fce/FieldTextBottom.html',
                                        'headerLabel' => 'Text bottom'
                                    ]
                                ]
                            ]
                        ],
                        [
                            // 2.13 Template Z / tt_content.tx_templavoila_to=25
                            'condition' => [
                                'CType' => 'templavoila_pi1',
                                'tx_templavoila_to' => '25'
                            ],
                            'properties' => [
                                'header_layout' => 100
                            ],
                            'flexFormField' => 'tx_templavoila_flex',
                            'template' => 'EXT:in2template/Resources/Private/Migration/Fce/FieldText.html',
                            'fceHelpers' => [
                                [
                                    'className' => BuildAccordeonGridForContentElementFceHelper::class
                                ]
                            ]
                        ],
                        [
                            // 2.14 Template B-2 / tt_content.tx_templavoila_to=30
                            'condition' => [
                                'CType' => 'templavoila_pi1',
                                'tx_templavoila_to' => '30'
                            ],
                            'properties' => [
                                'imagecols' => 1,
                                'header_layout' => 100
                            ],
                            'flexFormField' => 'tx_templavoila_flex',
                            'template' => 'EXT:in2template/Resources/Private/Migration/Fce/FieldText.html',
                            'fceHelpers' => [
                                [
                                    'className' => BuildGridContainerAndSplitContentFceHelper::class,
                                    'configuration' => [
                                        'accordeon' => true,
                                        'flexFormKey1' => 'field_image_right_section',
                                        'flexFormKey2' => 'field_image_outer_wrapper',
                                        'tx_gridelements_backend_layout' => 'twoColumnsLeft',
                                        'tx_gridelements_columns' => [
                                            'text' => 101,
                                            'image' => 102
                                        ],
                                        'headerLabels' => [
                                            'gridContentElement' => 'Grid Container (75%/25%)',
                                            'imageContentElement' => 'Images'
                                        ],
                                        'tvMappingConfiguration' => [
                                            'field_image',
                                            'field_image_caption',
                                            'field_image_link'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            // 3.01 Template M / tt_content.tx_templavoila_to=26
                            'condition' => [
                                'CType' => 'templavoila_pi1',
                                'tx_templavoila_to' => '26'
                            ],
                            'properties' => [
                                'imagecols' => 1,
                                'header_layout' => 100
                            ],
                            'flexFormField' => 'tx_templavoila_flex',
                            'template' => 'EXT:in2template/Resources/Private/Migration/Fce/FieldText.html',
                            'fceHelpers' => [
                                [
                                    'className' => BuildGridContainerAndSplitContentFceHelper::class,
                                    'configuration' => [
                                        'accordeon' => false,
                                        'flexFormKey1' => 'field_image_left_section',
                                        'flexFormKey2' => 'field_image_outer_wrapper',
                                        'tx_gridelements_backend_layout' => 'twoColumnsRight',
                                        'tx_gridelements_columns' => [
                                            'image' => 101,
                                            'text' => 102
                                        ],
                                        'headerLabels' => [
                                            'gridContentElement' => 'Grid Container (25%/75%)',
                                            'imageContentElement' => 'Images'
                                        ],
                                        'tvMappingConfiguration' => [
                                            'field_image',
                                            'field_image_alt_text',
                                            'field_image_link',
                                            'field_image_description',
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            // 3.02 Template N / tt_content.tx_templavoila_to=27
                            'condition' => [
                                'CType' => 'templavoila_pi1',
                                'tx_templavoila_to' => '27'
                            ],
                            'properties' => [
                                'imagecols' => 1,
                                'header_layout' => 3
                            ],
                            'flexFormField' => 'tx_templavoila_flex',
                            'template' => 'EXT:in2template/Resources/Private/Migration/Fce/FieldTextRight.html',
                            'fceHelpers' => [
                                [
                                    'className' => BuildGridContainerAndSplitContentFceHelper::class,
                                    'configuration' => [
                                        'accordeon' => false,
                                        'flexFormKey1' => 'field_image_left_section',
                                        'flexFormKey2' => 'field_image_outer_wrapper',
                                        'tx_gridelements_backend_layout' => 'twoColumnsRight',
                                        'tx_gridelements_columns' => [
                                            'image' => 101,
                                            'text' => 102
                                        ],
                                        'headerLabels' => [
                                            'gridContentElement' => 'Grid Container (25%/75%)',
                                            'imageContentElement' => 'Images'
                                        ],
                                        'tvMappingConfiguration' => [
                                            'field_image',
                                            'field_image_alt_text',
                                            'field_image_link',
                                            'field_image_description',
                                        ]
                                    ]
                                ],
                                [
                                    'className' => SetPropertyFromFlexFormPropertyFceHelper::class,
                                    'configuration' => [
                                        'property' => 'header',
                                        'fromProperty' => 'field_headline_center'
                                    ]
                                ],
                                [
                                    'className' => SetPropertyFceHelper::class,
                                    'configuration' => [
                                        'property' => 'header_layout',
                                        'value' => 3
                                    ]
                                ]
                            ]
                        ],
                        [
                            // 3.04 Template P / tt_content.tx_templavoila_to=23
                            'condition' => [
                                'CType' => 'templavoila_pi1',
                                'tx_templavoila_to' => '23'
                            ],
                            'properties' => [
                                'imageorient' => 0,
                                'imagecols' => 1,
                                'header_layout' => 100
                            ],
                            'flexFormField' => 'tx_templavoila_flex',
                            'template' => 'EXT:in2template/Resources/Private/Migration/Fce/Nothing.html',
                            'fceHelpers' => [
                                [
                                    'className' => BuildImageRelationsFceHelper::class,
                                    'configuration' => [
                                        'flexFormKey1' => 'field_image_section',
                                        'flexFormKey2' => 'field_image_wrapper',
                                        'tvMappingConfiguration' => [
                                            'field_image_top',
                                            'field_image_alt_text',
                                            'field_image_link',
                                            'field_image_description'
                                        ]
                                    ]
                                ],
                                [
                                    'className' => BuildContentElementsInTwoColumnGridFceHelper::class,
                                    'configuration' => [
                                        'templateLeft' =>
                                            'EXT:in2template/Resources/Private/Migration/Fce/FieldTextBottom.html',
                                        'templateRight' =>
                                            'EXT:in2template/Resources/Private/Migration/Fce/FieldTextBottom.html',
                                        'tx_gridelements_backend_layout' => 'twoColumns',
                                        'headerLabels' => [
                                            'gridContentElement' => 'Grid Container (50%/50%)',
                                            'contentLeft' => 'Text bottom left',
                                            'contentRight' => 'Text bottom right'
                                        ],
                                    ]
                                ],
                                [
                                    'className' => BuildAccordeonGridForContentElementFceHelper::class,
                                    'configuration' => [
                                        'enforceDummyContainer' => true
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                // Header CType will be changed to textmedia. Take over sub headline into RTE
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    // Replace only if this condition matches
                    'conditions' => [
                        'CType' => ['header']
                    ],
                    // Set current field "bodytext"
                    'replace' => [
                        'value' => '<p>{subheader}</p>'
                    ]
                ]
            ],
            [
                // Create RTE table from old CType table
                'className' => CreateRteTableFromCtypeTablePropertyHelper::class,
                'configuration' => [
                    'condition' => [
                        'CType' => 'table'
                    ],
                    'classes' => [
                        'table' => 'table--striped'
                    ]
                ]
            ],
            [
                // Create RTE table from old CType table
                'className' => CreateRteListFromCtypeBulletsPropertyHelper::class,
                'configuration' => [
                    'condition' => [
                        'CType' => 'bullets'
                    ]
                ]
            ],
            [
                // Remove not allowed links like "<link Link: https://www..."
                'className' => RemoveBrokenLinksPropertyHelper::class
            ]
        ],
        'subheader' => [
            [
                // Clear subheader in all cases
                'className' => ClearPropertyHelper::class
            ]
        ],
        'header_layout' => [
            [
                // Convert header layouts
                'className' => ReplacePropertyHelper::class,
                'configuration' => [
                    'search' => [
                        0, // Default & Header 2
                        2, // Header 2
                        3, // Header 3
                        4, // Header 4
                        5, // Header 5
                        6, // Header 6
                        //100 // Hidden
                    ],
                    'replace' => [
                        1, // Default / Header 2
                        1, // Header 2
                        2, // Header 3
                        3, // Header 4
                        4, // Header 5
                        4, // Header 5
                        //100 // Hidden
                    ],
                    //'default' => 1 // without default value, existing values will be kept
                ]
            ],
            [
                // Convert contact listing
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'CType' => ['pthskaadmin_list']
                    ],
                    'replace' => [
                        'value' => 100
                    ]
                ]
            ]
        ],
        'imagecols' => [
            [
                // Convert header layouts
                'className' => ReplacePropertyHelper::class,
                'configuration' => [
                    'search' => [
                        0,
                        1,
                        2,
                        3,
                        4,
                        5,
                        6,
                        7,
                        8,
                        9,
                    ],
                    'replace' => [
                        1,
                        1,
                        2,
                        3,
                        3,
                        3,
                        3,
                        3,
                        3,
                        3,
                    ],
                    'default' => 1
                ]
            ]
        ],
        'list_type' => [
            [
                // Convert contact listing
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    // Replace only if this condition matches
                    'conditions' => [
                        'CType' => ['pthskaadmin_list']
                    ],
                    // Replace in current field "list_type"
                    'replace' => [
                        'value' => 'contacts_pi1'
                    ]
                ]
            ],
            [
                // Convert tt_news to tx_news plugins
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    // Replace only if this condition matches
                    'conditions' => [
                        'list_type' => ['9']
                    ],
                    // Replace in current field "list_type"
                    'replace' => [
                        'value' => 'news_pi1'
                    ]
                ]
            ],
            [
                // Convert videolisting
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    // Replace only if this condition matches
                    'conditions' => [
                        'list_type' => ['hskalisting_pi1']
                    ],
                    // Replace in current field "list_type"
                    'replace' => [
                        'value' => 'in2videoaudiolist_pi2'
                    ]
                ]
            ],
            [
                // Convert user edit profile plugin
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'list_type' => ['pthskaadmin_feuser']
                    ],
                    'replace' => [
                        'value' => 'users_pi2'
                    ]
                ]
            ]
        ],
        'date' => [
            [
                'className' => ClearPropertyHelper::class
            ]
        ],
        // CType must be converted after everything else, because other PropertyHelpers "listen" on the CType
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
                // Convert TV FCE
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    // Replace only if this condition matches
                    'conditions' => [
                        'CType' => ['templavoila_pi1']
                    ],
                    // Replace in current field "CType"
                    'replace' => [
                        'value' => 'textmedia'
                    ]
                ]
            ],
            [
                // Convert contact list
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    // Replace only if this condition matches
                    'conditions' => [
                        'CType' => ['pthskaadmin_list']
                    ],
                    // Replace in current field "CType"
                    'replace' => [
                        'value' => 'list'
                    ]
                ]
            ]
        ],
        // manipulate more then only one field
        '*' => [
            [
                'className' => NotSupportedPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        [
                            'CType' => 'mailform'
                        ]
                    ],
                    'properties' => [
                        'CType' => 'textmedia',
                        'list_type' => '',
                        'bodytext' => '',
                        'header' => 'This element is not supported any more'
                    ]
                ]
            ]
        ]
    ];
}
