<?php
namespace In2code\In2template\Migration\Migrate;

use In2code\In2template\Migration\Migrate\PropertyHelper\ConvertOutdatedFileadminLinksPropertyHelper;

/**
 * Class BodytextMigrator
 */
class BodytextMigrator extends AbstractMigrator implements MigratorInterface
{

    /**
     * Table to migrate
     *
     * @var string
     */
    protected $tableName = 'tt_content';

    /**
     * Enforce _migrated=1 records to migrate again
     *
     * @var bool
     */
    protected $enforce = true;

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
        'bodytext' => [
            [
                'className' => ConvertOutdatedFileadminLinksPropertyHelper::class
            ]
        ]
    ];
}
