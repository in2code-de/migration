<?php
namespace In2code\In2template\Migration\Import;

use In2code\In2template\Migration\Import\PropertyHelper\CreateCategoryRelationPropertyHelper;
use In2code\In2template\Migration\Import\PropertyHelper\CreateFileRelationsPropertyHelper;
use In2code\In2template\Migration\Import\PropertyHelper\CreateImageRelationAndMoveImagePropertyHelper;
use In2code\In2template\Migration\Import\PropertyHelper\CreateRelatedRelationsPropertyHelper;

/**
 * Class NewsImporter
 */
class NewsImporter extends AbstractImporter implements ImporterInterface
{

    /**
     * Table name where to migrate to
     *
     * @var string
     */
    protected $tableName = 'tx_news_domain_model_news';

    /**
     * Table name from migrate to
     *
     * @var string
     */
    protected $tableNameOld = 'tt_news';

    /**
     * @var array
     */
    protected $mapping = [
        'type' => 'type',
        'title' => 'title',
        'short' => 'teaser',
        'bodytext' => 'bodytext',
        'datetime' => 'datetime',
        'author' => 'author',
        'author_email' => 'author_email',
        'keyword' => 'keywords',
        'archivedate' => 'archive',
        'editlock' => 'editlock',
        'keywords' => 'keywords',
        'tx_ptttnews_tag' => 'description',
        'page' => 'internalurl',
        'ext_url' => 'externalurl',

        'fe_group' => 'fe_group',
        'sys_language_uid' => 'sys_language_uid',
        'l18n_parent' => 'l10n_parent',
        'l18n_diffsource' => 'l10n_diffsource',

        'category' => 'categories',
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
        'categories' => [
            [
                'className' => CreateCategoryRelationPropertyHelper::class
            ]
        ],
        'fal_media' => [
            [
                'className' => CreateImageRelationAndMoveImagePropertyHelper::class
            ]
        ],
        'fal_related_files' => [
            [
                'className' => CreateFileRelationsPropertyHelper::class
            ]
        ],
        'related' => [
            [
                'className' => CreateRelatedRelationsPropertyHelper::class
            ]
        ]
    ];
}
