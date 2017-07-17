<?php
namespace In2code\In2template\Migration\Import;

use In2code\In2template\Migration\Import\PropertyHelper\CreateSortingNumberFromPropertyPropertyHelper;

/**
 * Class NewsCategoriesImporter
 */
class NewsCategoriesImporter extends AbstractImporter implements ImporterInterface
{

    /**
     * Table name where to migrate to
     *
     * @var string
     */
    protected $tableName = 'sys_category';

    /**
     * Table name from migrate to
     *
     * @var string
     */
    protected $tableNameOld = 'tt_news_cat';

    /**
     * @var array
     */
    protected $mapping = [
        'pid' => 'pid',
        'title' => 'title',
        'parent_category' => 'parent',
        'fe_group' => 'fe_group',
        'sorting' => 'sorting'
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
        'sorting' => [
            [
                'className' => CreateSortingNumberFromPropertyPropertyHelper::class,
                'configuration' => [
                    'property' => 'title'
                ]
            ]
        ]
    ];
}
