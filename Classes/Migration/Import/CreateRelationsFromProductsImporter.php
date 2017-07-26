<?php
namespace In2code\In2template\Migration\Import;

use In2code\In2template\Migration\Import\PropertyHelper\FaqProductRelationsPropertyHelper;

/**
 * Class CreateRelationsFromProductsImporter
 */
class CreateRelationsFromProductsImporter extends AbstractImporter implements ImporterInterface
{

    /**
     * Table name where to migrate to
     *
     * @var string
     */
    protected $tableName = 'tx_in2faq_question_category_mm';

    /**
     * Table name from migrate to
     *
     * @var string
     */
    protected $tableNameOld = 'tx_udgmvfaq_question_product_mm';

    /**
     * Default fields
     *
     * @var array
     */
    protected $mappingDefault = [];

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
        'uid_local' => [
            [
                'className' => FaqProductRelationsPropertyHelper::class
            ]
        ]
    ];
}
