<?php
namespace In2code\Migration\Migration\Migrate;

use In2code\Migration\Migration\Migrate\PropertyHelper\AddCategoryRelationsForEventPropertyHelper;

/**
 * Class CalendarCategoriesMigrator
 */
class CalendarCategoriesMigrator extends AbstractMigrator implements MigratorInterface
{

    /**
     * @var string
     */
    protected $tableName = 'tx_cal_event';

    /**
     * Iterate through tx_cal_event records for creating new mm relations but it's not needed to update the old cal
     * table again at this point
     *
     * @var bool
     */
    protected $doNotUpdate = true;

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
        'category_id' => [
            [
                'className' => AddCategoryRelationsForEventPropertyHelper::class
            ]
        ]
    ];
}
