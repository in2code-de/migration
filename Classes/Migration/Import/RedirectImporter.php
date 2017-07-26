<?php
namespace In2code\In2template\Migration\Import;

use In2code\In2template\Migration\Import\PropertyHelper\GenerateUrlHashPropertyHelper;
use In2code\In2template\Migration\Import\PropertyHelper\ReplaceOnConditionPropertyHelper;

/**
 * Class RedirectImporter
 */
class RedirectImporter extends AbstractImporter implements ImporterInterface
{

    /**
     * @var string
     */
    protected $tableName = 'tx_myredirects_domain_model_redirect';

    /**
     * @var string
     */
    protected $tableNameOld = 'tx_abzsuburl_url';

    /**
     * @var array
     */
    protected $mapping = [
        'suburl' => 'url'
    ];

    /**
     * @var array
     */
    protected $values = [
        'pid' => 9430
    ];

    /**
     * @var string
     */
    protected $groupBy = 'suburl';

    /**
     * @var string
     */
    protected $orderByOverride = 'uid DESC';

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
        'destination' => [
            [
                'className' => ReplaceOnConditionPropertyHelper::class,
                'configuration' => [
                    'conditions' => [
                        'pid' => [
                            '703'
                        ]
                    ],
                    'replace' => [
                        'value' => 't3://page?uid={page_uid}'
                    ]
                ]
            ],
        ],
        'url_hash' => [
            [
                'className' => GenerateUrlHashPropertyHelper::class
            ]
        ]
    ];
}
