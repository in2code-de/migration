<?php
namespace In2code\In2template\Migration\Import;

use In2code\In2template\Migration\Import\PropertyHelper\FaqCategoryRelationsPropertyHelper;

/**
 * Class CreateRelationsFromSysCategoriesImporter
 */
class CreateRelationsFromSysCategoriesImporter extends AbstractImporter implements ImporterInterface
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
    protected $tableNameOld = 'sys_category_record_mm';

    /**
     * Default fields
     *
     * @var array
     */
    protected $mappingDefault = [];

    /**
     * @var string
     */
    protected $additionalWhere = ' and tablenames="tx_udgmvfaq_domain_model_question"';

    /**
     * @var bool
     */
    protected $truncate = false;

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
                'className' => FaqCategoryRelationsPropertyHelper::class
            ]
        ]
    ];
}
