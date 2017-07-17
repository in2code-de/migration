<?php
namespace In2code\In2template\Migration\Import;

use In2code\In2template\Migration\Import\PropertyHelper\GetCategoryParentIdentifierPropertyHelper;

/**
 * Class CalendarCategoriesImporter
 */
class CalendarCategoriesImporter extends AbstractImporter implements ImporterInterface
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
    protected $tableNameOld = 'tx_pthskaadmin_domain_model_appointmentcategory';

    /**
     * @var bool
     */
    protected $truncate = false;

    /**
     * @var bool
     */
    protected $keepIdentifiers = false;

    /**
     * @var array
     */
    protected $mapping = [
        'pid' => 'pid',
        'name_de' => 'title'
    ];

    /**
     * Fill properties with hard values
     *
     * @var array
     */
    protected $values = [
        'type' => 'In2code\HskaCalendar\Domain\Model\Category'
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
        'parent' => [
            [
                'className' => GetCategoryParentIdentifierPropertyHelper::class
            ]
        ]
    ];
}
