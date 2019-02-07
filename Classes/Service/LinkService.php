<?php
namespace In2code\Migration\Service;

use In2code\Migration\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class LinkService
 */
class LinkService
{

    /**
     * Define in which fields there are links that should be replaced with a newer mapping (like bodytext with links
     * like <a href="t3://page?uid=123">link</a>
     *
     * @var array
     */
    protected $propertiesWithLinks = [
        'tt_content' => [
            'header_link',
            'bodytext'
        ],
        'sys_file_reference' => [
            'link'
        ]
    ];

    /**
     * Define simple fields that hold relations to page records (like pages.shortcut=123)
     *
     * @var array
     */
    protected $propertiesWithRelations = [
        'pages' => [
            'shortcut'
        ],
        'tt_content' => [
            'records'
        ]
    ];

    /**
     * @var null
     */
    protected $mappingService = null;

    /**
     * LinkService constructor.
     * @param MappingService $mappingService
     */
    public function __construct(MappingService $mappingService)
    {
        $this->mappingService = $mappingService;
    }

    /**
     * @return void
     */
    public function updateLinksAndRecordsInNewRecords()
    {
        foreach ($this->mappingService->getMapping() as $tableName => $identifiers) {
            if ($this->isTableInAnyLinkConfiguration($tableName)) {
                foreach ($identifiers as $identifier) {
                    $properties = $this->getPropertiesFromIdentifierAndTable($identifier, $tableName);
                    $newProperties = $this->updatePropertiesWithNewLinkMapping($properties, $tableName);
                    $newProperties = $this->updatePropertiesWithNewRelationMapping($newProperties, $tableName);
                    if ($newProperties !== $properties) {
                        $this->updateRecord($newProperties, $tableName);
                    }
                }
            }
        }
    }

    /**
     * @param array $properties
     * @param string $tableName
     * @return array
     */
    protected function updatePropertiesWithNewLinkMapping(array $properties, string $tableName): array
    {
        foreach ($properties as $fieldName => $value) {
            if (isset($this->propertiesWithLinks[$tableName])
                && in_array($fieldName, $this->propertiesWithLinks[$tableName])) {
                if (!empty($value)) {
                    $properties[$fieldName] = $this->updateValueWithNewLinkMapping($value);
                }
            }
        }
        return $properties;
    }

    /**
     * @param array $properties
     * @param string $tableName
     * @return array
     */
    protected function updatePropertiesWithNewRelationMapping(array $properties, string $tableName): array
    {
        foreach ($properties as $fieldName => $value) {
            if (isset($this->propertiesWithRelations[$tableName])
                && in_array($fieldName, $this->propertiesWithRelations[$tableName])) {
                if (!empty($value)) {
                    $identifiers = GeneralUtility::intExplode(',', $value);
                    $newIdentifiers = [];
                    foreach ($identifiers as $identifier) {
                        $newIdentifiers[] = $this->mappingService->getNewFromOld($identifier, $tableName);
                    }
                    $properties[$fieldName] = implode(',', $newIdentifiers);
                }
            }
        }
        return $properties;
    }

    /**
     * @param string $value
     * @return string
     */
    protected function updateValueWithNewLinkMapping(string $value): string
    {
        $value = $this->updatePageLinks($value);
        $value = $this->updateFileLinks($value);
        return $value;
    }

    /**
     * Search for t3://page?uid=123
     *
     * @param string $value
     * @return string
     */
    protected function updatePageLinks(string $value): string
    {
        $value = preg_replace_callback(
            '~(t3://page\?uid=)(\d+)~',
            [$this, 'updatePageLinksCallback'],
            $value
        );
        return $value;
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

    /**
     * Replace t3://page?uid=123 => t3://page?uid=234
     *
     * @param array $match
     * @return string
     */
    protected function updatePageLinksCallback(array $match): string
    {
        $oldPageIdentifier = (int)$match[2];
        $newPageIdentifier = $this->mappingService->getNewPidFromOldPid($oldPageIdentifier);
        return $match[1] . $newPageIdentifier;
    }

    /**
     * Replace t3://file?uid=123 => t3://file?uid=234
     *
     * @param array $match
     * @return string
     */
    protected function updateFileLinksCallback(array $match): string
    {
        $oldIdentifier = (int)$match[2];
        $newIdentifier = $this->mappingService->getNewFromOld($oldIdentifier, 'sys_file');
        return $match[1] . $newIdentifier;
    }

    /**
     * @param int $identifier
     * @param string $tableName
     * @return array
     */
    protected function getPropertiesFromIdentifierAndTable(int $identifier, string $tableName): array
    {
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable($tableName, true);
        $rows = (array)$queryBuilder
            ->select('*')
            ->from($tableName)
            ->where('uid=' . $identifier)
            ->execute()
            ->fetchAll();
        if (!empty($rows[0]['uid'])) {
            return $rows[0];
        }
        return [];
    }

    /**
     * @param array $properties
     * @param string $tableName
     * @return void
     */
    protected function updateRecord(array $properties, string $tableName)
    {
        if (!empty($properties['uid'])) {
            $connection = DatabaseUtility::getConnectionForTable($tableName);
            $connection->update(
                $tableName,
                $properties,
                ['uid' => (int)$properties['uid']]
            );
        }
    }

    /**
     * @param $tableName
     * @return bool
     */
    protected function isTableInAnyLinkConfiguration($tableName): bool
    {
        return array_key_exists($tableName, $this->propertiesWithLinks)
            || array_key_exists($tableName, $this->propertiesWithRelations);
    }
}
