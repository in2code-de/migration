<?php
namespace In2code\In2template\Migration\Import;

/**
 * Class FaqCategoriesSysCategoryImporter
 */
class FaqCategoriesSysCategoryImporter extends AbstractImporter implements ImporterInterface
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
    protected $tableNameOld = 'sys_category';

    /**
     * @var array
     */
    protected $mapping = [
        'title' => 'title'
    ];

    /**
     * @var string
     */
    protected $additionalWhere = ' and pid = 8097';

    /**
     * @var bool
     */
    protected $truncate = false;

    /**
     * @var bool
     */
    protected $keepIdentifiers = false;

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
