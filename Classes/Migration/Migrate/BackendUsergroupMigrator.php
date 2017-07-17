<?php
namespace In2code\In2template\Migration\Migrate;

use In2code\In2template\Migration\Migrate\PropertyHelper\BuildRelationFacilityPropertyHelper;

/**
 * Class BackendUsergroupMigrator
 */
class BackendUsergroupMigrator extends AbstractMigrator implements MigratorInterface
{

    /**
     * Table to migrate
     *
     * @var string
     */
    protected $tableName = 'be_groups';

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
        'tc_beuser_facility' => [
            [
                'className' => BuildRelationFacilityPropertyHelper::class,
                'configuration' => [
                    'relationTable' => 'tx_groupdelegation_begroups_facility_mm'
                ]
            ]
        ]
    ];
}
