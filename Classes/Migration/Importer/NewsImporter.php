<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\Importer;

/**
 * Class NewsImporter
 * as an example class for a tt_news to tx_news importer functionality
 */
class NewsImporter extends AbstractImporter implements ImporterInterface
{
    protected string $tableName = 'tx_news_domain_model_news';
    protected string $tableNameOld = 'tt_news';

    protected array $mapping = [
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
        'page' => 'internalurl',
        'ext_url' => 'externalurl',
        'hidden' => 'hidden',

        'fe_group' => 'fe_group',
        'sys_language_uid' => 'sys_language_uid',
        'l18n_parent' => 'l10n_parent',
        'l18n_diffsource' => 'l10n_diffsource',

        'category' => 'categories',
    ];

    protected array $values = [
        'hidden' => '0',
    ];

    protected array $propertyHelpers = [
//        'categories' => [
//            [
//                'className' => CreateNewsCategoryRelationPropertyHelper::class,
//            ],
//        ],
//        'fal_media' => [
//            [
//                'className' => CreateNewsImageRelationAndMoveImagePropertyHelper::class,
//            ],
//        ],
//        'fal_related_files' => [
//            [
//                'className' => CreateNewsFileRelationsPropertyHelper::class,
//            ],
//        ],
//        'related' => [
//            [
//                'className' => CreateNewsRelatedRelationsPropertyHelper::class,
//            ],
//        ],
    ];
}
