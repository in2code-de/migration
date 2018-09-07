<?php
namespace In2code\Migration\Migration\Migrate;

use In2code\Migration\Migration\Migrate\PropertyHelper\CreateFormsAndPagesAndFieldsPropertyHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\FlexFormHelper\GetConfirmationSettingsFlexFormHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\FlexFormHelper\GetFormUidFlexFormHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\FlexFormHelper\GetReceiverEmailFlexFormHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\FlexFormHelper\GetReceiverEmailSubjectFlexFormHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\FlexFormHelper\GetRedirectPidFlexFormHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\IncreasePropertyHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\RemoveEmptyLinesPropertyHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\RemoveFileRelationsPropertyHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\SetLayoutAndFrameClassForBorderColumnPropertyHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\UpdateNewsFlexFormPropertyHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\AddAccordeonContentElementPropertyHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\AddCssClassesPropertyHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\ConvertBulletspointsToHtmlPropertyHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\ConvertUploadsToTextMediaPropertyHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\FlexFormGeneratorPropertyHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\ReplaceCssClassesInHtmlStringPropertyHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\ReplaceOnConditionPropertyHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\ReplaceOnNewsPluginPropertyHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\ReplacePropertyHelper;
use In2code\Migration\Utility\DatabaseUtility;
use In2code\Powermail\Domain\Model\Field;
use In2code\Powermail\Domain\Model\Form;
use In2code\Powermail\Domain\Model\Page;

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
     * Hardcode some values in tt_content
     *
     * @var array
     */
    protected $values = [
        'linkToTop' => 0,
        'date' => 0
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
        '_dummy' => [
            [
                // Mailform to powermail converting: Create form, field, page
                'className' => CreateFormsAndPagesAndFieldsPropertyHelper::class
            ],
        ],
        'pi_flexform' => [
            [
                'className' => FlexFormGeneratorPropertyHelper::class,
                'configuration' => [
                    'condition' => [
                        'CType' => 'gridelements_pi1',
                        'tx_gridelements_backend_layout' => '2cols'
                    ],
                    'flexFormTemplate' => 'EXT:migration/Resources/Private/FlexForms/GridElements_50_50.xml'
                ]
            ],
            [
                // News in Main column - set itemsPerPage
                'className' => UpdateNewsFlexFormPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'CType' => ['list'],
                        'list_type' => ['news_pi1'],
                        'colPos' => ['0']
                    ],
                    'mapping' => [
                        'settings.list.paginate.itemsPerPage' => '8',
                        'settings.limit' => '',
                        'settings.hidePagination' => ''
                    ]
                ]
            ],
            [
                // News in Border column - set limit
                'className' => UpdateNewsFlexFormPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'CType' => ['list'],
                        'list_type' => ['news_pi1'],
                        'colPos' => ['2', '3']
                    ],
                    'mapping' => [
                        'settings.list.paginate.itemsPerPage' => '3',
                        'settings.limit' => '',
                        'settings.hidePagination' => ''
                    ]
                ]
            ],
            [
                // News from cal
                'className' => FlexFormGeneratorPropertyHelper::class,
                'configuration' => [
                    'condition' => [
                        'CType' => 'list',
                        'list_type' => 'cal_controller'
                    ],
                    'overwriteValues' => [
                        'list_type' => 'news_pi1',
                        'layout' => 100,
                        'frame_class' => 22
                    ],
                    'flexFormTemplate' => 'EXT:migration/Resources/Private/FlexForms/NewsEvents.xml'
                ]
            ],
            [
                // Mailform to powermail converting: Create plugin
                'className' => FlexFormGeneratorPropertyHelper::class,
                'configuration' => [
                    'condition' => [
                        'CType' => 'mailform'
                    ],
                    'flexFormTemplate' => 'EXT:migration/Resources/Private/FlexForms/Powermail.xml',
                    'helpers' => [
                        [
                            'className' => GetFormUidFlexFormHelper::class,
                            'configuration' => [
                                'variableName' => 'formUid' // get it via {helper.formUid}
                            ]
                        ],
                        [
                            'className' => GetReceiverEmailFlexFormHelper::class,
                            'configuration' => [
                                'variableName' => 'receiverEmail' // get it via {helper.receiverEmail}
                            ]
                        ],
                        [
                            'className' => GetReceiverEmailSubjectFlexFormHelper::class,
                            'configuration' => [
                                'variableName' => 'subject' // get it via {helper.subject}
                            ]
                        ],
                        [
                            'className' => GetRedirectPidFlexFormHelper::class,
                            'configuration' => [
                                'variableName' => 'redirectPid' // get it via {helper.redirectPid}
                            ]
                        ],
                        [
                            'className' => GetConfirmationSettingsFlexFormHelper::class,
                            'configuration' => [
                                'variableName' => 'confirmationPage' // get it via {helper.confirmationPage}
                            ]
                        ],
                    ]
                ]
            ],
        ],
        'header_layout' => [
            [
                // convert not allowed header_layouts to default
                'className' => ReplacePropertyHelper::class,
                'configuration' => [
                    'search' => [
                        '0',
                        '1',
                        '2',
                        '3',
                        '4',
                        '5',
                        '6',
                        '100',
                    ],
                    'replace' => [
                        0,
                        1,
                        2,
                        3,
                        4,
                        5,
                        6,
                        100,
                    ],
                    'default' => 0
                ]
            ]
        ],
        'bodytext' => [
            [
                // Add css classes to UL elements to have some visible arrows
                'className' => AddCssClassesPropertyHelper::class,
                'configuration' => [
                    'tags' => [
                        'ul'
                    ],
                    'addClass' => [
                        'ut-list',
                        'ut-list--link-list'
                    ],
                    'condition' => [
                        'CType' => [
                            'textpic',
                            'text',
                            'textmedia'
                        ]
                    ]
                ]
            ],
            [
                // Add bodytext links from uploads CType
                'className' => ConvertUploadsToTextMediaPropertyHelper::class
            ],
            [
                'className' => ConvertBulletspointsToHtmlPropertyHelper::class,
                'configuration' => [
                    'class' => 'ut-list ut-list--link-list'
                ]
            ],
            [
                // Change table classes
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
                        'table'
                    ],
                    'search' => [
                        'csc-frame-frame3',
                        'csc-frame-frame4',
                        'csc-frame-frame5',
                        'csc-frame-frame6',
                        'csc-frame-frame7',
                        'csc-frame-frame8',
                    ],
                    'replace' => [
                        '', // "Ohne Formatierung"
                        'ut-table--striped', // Transparent/Striped
                        'ut-table--color-primary-3', // Anthrazit normal
                        '',
                        '',
                        'ut-table--color-primary-3',
                    ]
                ]
            ],
            [
                // Clean bodytext if header or image content
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'CType' => [
                            'header',
                            'image',
                            'mailform'
                        ]
                    ],
                    'replace' => [
                        'value' => ''
                    ]
                ]
            ],
            [
                'className' => RemoveFileRelationsPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'CType' => [
                            'header',
                            'text',
                            'div',
                            'uploads',
                            'bullets',
                        ]
                    ]
                ]
            ],
            [
                // try to remove <p>&nbsp;</p> and \n from tt_content.bodytext
                'className' => RemoveEmptyLinesPropertyHelper::class
            ]
        ],
        'list_type' => [
            [
                // Mailform instead of powermail
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'CType' => [
                            'mailform'
                        ]
                    ],
                    'replace' => [
                        'value' => 'powermail_pi1'
                    ]
                ]
            ]
        ],
        'CType' => [
            [
                // Use textpic as main CType
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'CType' => [
                            'uploads',
                            'bullets',
                            'text',
                            'textmedia',
                            'header',
                            'image'
                        ]
                    ],
                    'replace' => [
                        'value' => 'textpic'
                    ]
                ]
            ],
            [
                // Mailform instead of powermail
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'CType' => [
                            'mailform'
                        ]
                    ],
                    'replace' => [
                        'value' => 'list'
                    ]
                ]
            ]
        ],
        'layout' => [
            [
                // Select layout "Dekorierte Boxen" or "Box mit Bild" (if CE contains images) for CE in border column
                'className' => SetLayoutAndFrameClassForBorderColumnPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'colPos' => [
                            '2',
                            //'3'
                        ]
                    ],
                    'values' => [
                        'layout' => 'decorated-box',
                        'frame_class' => 'redbordertop-grey-box'
                    ],
                    'valuesImage' => [
                        'layout' => 'box-picture',
                        'frame_class' => 'grey-box-imagetop'
                    ],
                    'valuesImageBelow' => [
                        'layout' => 'box-picture',
                        'frame_class' => 'grey-box-imagebottom'
                    ]
                ]
            ],
            [
                // Set layout to "Liste - 100%" for News list
                'className' => ReplaceOnNewsPluginPropertyHelper::class,
                'configuration' => [
                    'condition' => 'list', // list or detail
                    'replace' => 100
                ]
            ],
            [
                // Set layout to "Liste - 100%" for News detail
                'className' => ReplaceOnNewsPluginPropertyHelper::class,
                'configuration' => [
                    'condition' => 'detail', // list or detail
                    'replace' => 1000
                ]
            ],
            [
                // Convert grey boxes
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'frame_class' => [
                            'custom-102',
                            'custom-113',
                            'custom-114',
                            'custom-115',
                            'custom-116',
                            'custom-117',
                            'custom-118',
                        ]
                    ],
                    'replace' => [
                        'value' => 'colorvariants'
                    ]
                ]
            ],
        ],
        'frame_class' => [
            [
                // Convert grey box
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'frame_class' => [
                            'custom-102',
                            'custom-113',
                            'custom-114',
                            'custom-115',
                            'custom-116',
                            'custom-117',
                            'custom-118',
                        ]
                    ],
                    'replace' => [
                        'value' => 'components'
                    ]
                ]
            ],
            [
                // Set frame_class to "Box: Grau" for News
                'className' => ReplaceOnNewsPluginPropertyHelper::class,
                'configuration' => [
                    'condition' => 'list', // list or detail
                    'replace' => 5
                ]
            ],
        ],
        'imageorient' => [
            [
                'className' => ReplacePropertyHelper::class,
                'configuration' => [
                    'search' => [
                        '0',
                        '1',
                        '2',
                        '8',
                        '9',
                        '10',
                        '17',
                        '18',
                        '25',
                        '26',
                    ],
                    'replace' => [
                        0,
                        0,
                        0,
                        1,
                        1,
                        1,
                        17,
                        18,
                        26,
                        25,
                    ]
                ]
            ]
        ],
        'sorting' => [
            [
                'className' => IncreasePropertyHelper::class,
                'configuration' => [
                    'valueToAdd' => 1000000000,
                    'condition' => [
                        'colPos' => '3'
                    ]
                ]
            ]
        ],
        'colPos' => [
            [
                // Move all CE in colPos=3 into an accordeon grid container
                'className' => AddAccordeonContentElementPropertyHelper::class,
                'configuration' => [
                    'values' => [
                        'colPos' => 2,
                        'sorting' => 1000000,
                        'CType' => 'gridelements_pi1',
                        'tx_gridelements_backend_layout' => 'accordion'
                    ],
                    'condition' => [
                        'colPos' => '3',
                        'CType' => 'textpic'
                    ]
                ]
            ],
            [
                'className' => ReplacePropertyHelper::class,
                'configuration' => [
                    'search' => [
                        '0',
                        '2',
                        '3'
                    ],
                    'replace' => [
                        0,
                        2,
                        2
                    ]
                ]
            ]
        ]
    ];

    /**
     * @return void
     */
    protected function initialize()
    {
        $this->clearBackendLayout();
        $this->removeSubheaderInOtherContentTypes();
        $this->removePowermailMigratedRecords();
        $this->cleanLocalization();
    }

    /**
     * @return void
     */
    protected function clearBackendLayout()
    {
        $this->getDatabase()->exec_UPDATEquery(
            'pages',
            'uid>1',
            ['backend_layout' => '', 'backend_layout_next_level' => '']
        );
    }

    /**
     * @return void
     */
    protected function removeSubheaderInOtherContentTypes()
    {
        $this->getDatabase()->exec_UPDATEquery(
            $this->tableName,
            'CType != "header" and deleted=0',
            ['subheader' => '']
        );
    }

    /**
     * Delete forms, pages and fields and powermail-ce from the last migration
     *
     * @return void
     */
    protected function removePowermailMigratedRecords()
    {
        $this->getDatabase()->exec_DELETEquery(Form::TABLE_NAME, '_migrated=1');
        $this->getDatabase()->exec_DELETEquery(Page::TABLE_NAME, '_migrated=1');
        $this->getDatabase()->exec_DELETEquery(Field::TABLE_NAME, '_migrated=1');
        $this->getDatabase()->exec_DELETEquery(
            'tt_content',
            'colPos=' . CreateFormsAndPagesAndFieldsPropertyHelper::COLPOSNEWCONTENT
        );
    }

    /**
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function cleanLocalization()
    {
        // 1. set localized content elements to hidden if parent ce are also hidden
        $sql = 'update tt_content c
          left join tt_content c2 on c.uid = c2.l18n_parent
          set c2.hidden = c.hidden
          where c2.sys_language_uid > 0 and c.sys_language_uid = 0 and c.hidden = 1;';
        $connection = DatabaseUtility::getConnectionForTable('tt_content');
        $connection->query($sql)->execute();

        // 2. break relation between localized ce and parents
        $this->getDatabase()->exec_UPDATEquery($this->tableName, 'deleted=0', ['l18n_parent' => 0]);

        // 3. set "all languages" to default language for ce
        $this->getDatabase()->exec_UPDATEquery($this->tableName, 'sys_language_uid=-1', ['sys_language_uid' => 0]);
    }
}
