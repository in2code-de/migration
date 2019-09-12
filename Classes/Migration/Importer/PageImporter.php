<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\Importer;

use In2code\Migration\Migration\PropertyHelpers\ReplaceOnConditionPropertyHelper;

/**
 * Class PageImporter
 */
class PageImporter extends AbstractImporter implements ImporterInterface
{
    /**
     * @var string
     */
    protected $tableName = 'tx_news_domain_model_news';

    /**
     * @var string
     */
    protected $tableNameOld = 'tt_news';

    /**
     * @var array
     */
    protected $mapping = [
        'title' => 'title'
    ];

    /**
     * @var array
     */
    protected $values = [
        'hidden' => '0'
    ];

    /**
     * @var array
     */
    protected $propertyHelpers = [
        'crdate' => [
            [
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'deleted' => [
                            0
                        ]
                    ],
                    'replace' => [
                        'value' => '123465'
                    ]
                ]
            ]
        ]
    ];
}
