<?php
namespace In2code\Migration\Migration\Migrate;

use In2code\Migration\Migration\Migrate\PropertyHelper\ConvertTimestampToReadableDatePropertyHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\RemoveEmptyLinesPropertyHelper;

/**
 * Class NewsMigrator
 */
class NewsMigrator extends AbstractMigrator implements MigratorInterface
{

    /**
     * Table to migrate
     *
     * @var string
     */
    protected $tableName = 'tx_news_domain_model_news';

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
        'bodytext' => [
            [
                'className' => RemoveEmptyLinesPropertyHelper::class
            ]
        ],
        'application_deadline' => [
            [
                'className' => ConvertTimestampToReadableDatePropertyHelper::class,
                'configuration' => [
                    'format' => '%d.%m.%Y',
                    'append' => '',
                    'prepend' => ''
                ]
            ]
        ]
    ];
}
