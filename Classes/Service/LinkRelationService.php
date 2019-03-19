<?php
namespace In2code\Migration\Service;

use In2code\Migration\Utility\ArrayUtility;

/**
 * Class LinkRelationService
 */
class LinkRelationService
{

    /**
     * Define in which fields can be links to files
     *
     * Example content (like tt_content.bodytext) with links
     * like
     * ... <a href="t3://file?uid=123">link</a> ...
     *
     * @var array
     */
    protected $propertiesWithLinks = [
        'tt_content' => [
            'bodytext'
        ],
        'tx_news_domain_model_news' => [
            'bodytext'
        ]
    ];

    /**
     * @param array $jsonArray
     * @return int[]
     */
    public function getFileIdentifiersFromLinks(array $jsonArray): array
    {
        $identifiers = [];
        foreach ($this->propertiesWithLinks as $table => $fields) {
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
     * Search for t3://file?uid=123
     *
     * @param string $value
     * @return string
     */
    protected function updateFileLinks(string $value): string
    {
        $value = preg_replace_callback(
            '~(t3://file\?uid=)(\d+)~',
            [$this, 'updateFileLinksCallback'],
            $value
        );
        return $value;
    }
}
