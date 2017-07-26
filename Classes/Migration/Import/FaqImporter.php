<?php
namespace In2code\In2template\Migration\Import;

use In2code\In2template\Migration\Import\PropertyHelper\ReplaceCssClassesInHtmlStringPropertyHelper;

/**
 * Class FaqImporter
 */
class FaqImporter extends AbstractImporter implements ImporterInterface
{

    /**
     * Table name where to migrate to
     *
     * @var string
     */
    protected $tableName = 'tx_in2faq_domain_model_question';

    /**
     * Table name from migrate to
     *
     * @var string
     */
    protected $tableNameOld = 'tx_udgmvfaq_domain_model_question';

    /**
     * @var array
     */
    protected $mapping = [
        'question' => 'question',
        'answer' => 'answer',
        'crdate' => 'crdate'
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
        'answer' => [
            [
                'className' => ReplaceCssClassesInHtmlStringPropertyHelper::class,
                'configuration' => [
                    'search' => [
                        'btn',
                        'btn-neg',
                        'btn-green',
                        'btn-blue',
                        'contenttable-2',
                        'contenttable-3',
                        'ul-check'
                    ],
                    'replace' => [
                        'c-button',
                        'c-button--white',
                        'c-button--green',
                        '',
                        'u-table-transparent',
                        'u-table-blue',
                        'u-list-check'
                    ]
                ]
            ]
        ]
    ];
}
