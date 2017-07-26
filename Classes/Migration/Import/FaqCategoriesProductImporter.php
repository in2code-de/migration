<?php
namespace In2code\In2template\Migration\Import;

/**
 * Class FaqCategoriesImporter
 */
class FaqCategoriesProductImporter extends AbstractImporter implements ImporterInterface
{

    /**
     * Table name where to migrate to
     *
     * @var string
     */
    protected $tableName = 'tx_in2faq_domain_model_category';

    /**
     * Table name from migrate to
     *
     * @var string
     */
    protected $tableNameOld = 'tx_udgmvproducts_domain_model_product';

    /**
     * @var array
     */
    protected $mapping = [
        'name' => 'title'
    ];

    protected $values = [
        'pid' => 8097
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
    ];
}
