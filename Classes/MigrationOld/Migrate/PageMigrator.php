<?php
namespace In2code\Migration\MigrationOld\Migrate;

use In2code\Migration\MigrationOld\Migrate\PropertyHelper\AddValueByPidPropertyHelper;

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
     * @var array
     */
    protected $mapping = [
        'title' => 'seo_title'
    ];

    /**
     * @var array
     */
    protected $values = [
        'no_index' => 1
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
//        'og_description' => [
//            [
//                'className' => AddValueByPidPropertyHelper::class,
//                'configuration' => [
//                    'mapping' => [
//                        3439 => 1
//                    ]
//                ]
//            ]
//        ]
    ];
}
