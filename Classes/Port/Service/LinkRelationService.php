<?php
declare(strict_types=1);
namespace In2code\Migration\Port\Service;

use In2code\Migration\Utility\ArrayUtility;

/**
 * Class LinkRelationService
 * to extend the export json with files that are linked from a RTE
 */
class LinkRelationService
{
    /**
     * Hold the complete configuration like
     *
     *  'excludedTables' => [
     *      'be_users'
     *  ],
     *  'linkMapping' => [
     *      'propertiesWithLinks' => [
     *          'tt_content' => [
     *              'bodytext'
     *          ],
     *          'tx_news_domain_model_news' => [
     *              'bodytext'
     *          ]
     *      ],
     *      'propertiesWithRelations' => [
     *          'pages' => [
     *              'shortcut'
     *          ],
     *          'tt_content' => [
     *              'header_link'
     *          ],
     *          'sys_file_reference' => [
     *              'link'
     *          ]
     *      ],
     *      'propertiesWithRelationsInFlexForms' => [
     *          'tt_content' => [
     *              'pi_flexform' => [
     *                  [
     *                      // tt_news update flexform
     *                      'condition' => [
     *                          'Ctype' => 'list',
     *                          'list_type' => 9
     *                      ],
     *                      'selection' => '//T3FlexForms/data/sheet[@index="s_misc"]/language/field[@index="PIDitemDisplay"]/value'
     *                  ]
     *              ]
     *          ]
     *      ],
     *  ]
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * LinkRelationService constructor.
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param array $jsonArray
     * @return int[]
     */
    public function getFileIdentifiersFromLinks(array $jsonArray): array
    {
        $identifiers = [];
        foreach ($this->getPropertiesWithLinks() as $table => $fields) {
            if (!empty($jsonArray['records'][$table])) {
                foreach ($jsonArray['records'][$table] as $row) {
                    foreach ($fields as $field) {
                        if (!empty($row[$field])) {
                            $identifiers += $this->searchForFileLinks($row[$field]);
                        }
                    }
                }
            }
        }
        return $identifiers;
    }

    /**
     * @param string $content
     * @return int[] file identifiers
     */
    protected function searchForFileLinks(string $content): array
    {
        preg_match_all('~t3://file\?uid=(\d+)~', $content, $result);
        if (!empty($result[1])) {
            return ArrayUtility::intArray($result[1]);
        }
        return [];
    }

    /**
     * @return array
     */
    protected function getPropertiesWithLinks(): array
    {
        return (array)$this->configuration['linkMapping']['propertiesWithLinks'];
    }
}
