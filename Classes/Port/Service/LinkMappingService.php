<?php
declare(strict_types=1);
namespace In2code\Migration\Port\Service;

use Doctrine\DBAL\DBALException;
use In2code\Migration\Utility\DatabaseUtility;
use In2code\Migration\Utility\StringUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LinkMappingService
 * will change links after an import with new identifiers
 */
class LinkMappingService
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
     * @var null
     */
    protected $mappingService = null;

    /**
     * LinkMappingService constructor.
     * @param MappingService $mappingService
     * @param array $configuration
     */
    public function __construct(MappingService $mappingService, array $configuration)
    {
        $this->mappingService = $mappingService;
        $this->configuration = $configuration;
    }

    /**
     * @return void
     * @throws DBALException
     */
    public function updateLinksAndRecordsInNewRecords(): void
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
                    $this->updatePropertiesWithNewRelationsInFlexForms($newProperties, $tableName);
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
            if (isset($this->getPropertiesWithLinks()[$tableName])
                && in_array($fieldName, $this->getPropertiesWithLinks()[$tableName])) {
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
            if (isset($this->getPropertiesWithRelations()[$tableName])
                && in_array($fieldName, $this->getPropertiesWithRelations()[$tableName])) {
                if (!empty($value)) {
                    $properties[$fieldName] = $this->updateValueWithSimpleLinks($value);
                }
            }
        }
        return $properties;
    }

    /**
     * @param array $properties
     * @param string $tableName
     * @return void
     * @throws DBALException
     */
    protected function updatePropertiesWithNewRelationsInFlexForms(array $properties, string $tableName): void
    {
        foreach ($properties as $fieldName => $value) {
            if (isset($this->getPropertiesWithRelationsInFlexForms()[$tableName])
                && array_key_exists($fieldName, $this->getPropertiesWithRelationsInFlexForms()[$tableName])) {
                if (!empty($value)) {
                    foreach ($this->getPropertiesWithRelationsInFlexForms()[$tableName][$fieldName] as $configuration) {
                        $conditionFits = true;
                        foreach ($configuration['condition'] as $field => $value) {
                            if ($properties[$field] !== $value) {
                                $conditionFits = true;
                            }
                        }
                        if ($conditionFits === true) {
                            $this->updateFlexFormValue($properties['uid'], $tableName, $fieldName, $configuration);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param int $identifier
     * @param string $tableName
     * @param string $fieldName
     * @param array $configuration
     * @return void
     * @throws DBALException
     */
    protected function updateFlexFormValue(
        int $identifier,
        string $tableName,
        string $fieldName,
        array $configuration
    ): void {
        $connection = DatabaseUtility::getConnectionForTable($tableName);
        $sql = 'select ExtractValue(' . $fieldName . ', \'' . $configuration['selection'] . '\') value from '
            . $tableName . ' where uid=' . (int)$identifier;
        $value = (string)$connection->executeQuery($sql)->fetchColumn(0);
        $newValue = $this->updateValueWithSimpleLinks($value);
        if (!empty($newValue)) {
            $sql = 'update ' . $tableName . ' set '
                . $fieldName . ' = UpdateXML(' . $fieldName . ', \''
                . $configuration['selection'] . '\', concat(\'<value index="vDEF">\', \''
                . $newValue . '\', \'</value>\' )) WHERE uid=' . (int)$identifier;
        }
        $connection->executeQuery($sql);
    }

    /**
     * @param string $value
     * @return string
     */
    protected function updateValueWithNewLinkMapping(string $value): string
    {
        $value = $this->updatePageLinks($value);
        $value = $this->updateFileLinks($value);
        $value = $this->updateRteImages($value);
        return $value;
    }

    /**
     * @param string $value
     * @return string
     */
    protected function updateValueWithSimpleLinks(string $value): string
    {
        if (StringUtility::isIntegerListOrInteger($value)) {
            $newValue = $this->updatePageLinksSimple($value);
        } else {
            $value = $this->updatePageLinks($value);
            $newValue = $this->updateFileLinks($value);
        }
        return $newValue;
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
     * Search for "123" or "123,124"
     *
     * @param string $value
     * @return string
     */
    protected function updatePageLinksSimple(string $value): string
    {
        $identifiers = GeneralUtility::intExplode(',', $value);
        $newIdentifiers = [];
        foreach ($identifiers as $identifier) {
            $newIdentifiers[] = $this->mappingService->getNewPidFromOldPid($identifier);
        }
        return (string)implode(',', $newIdentifiers);
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
     * Search for data-htmlarea-file-uid="123"
     *
     * @param string $value
     * @return string
     */
    protected function updateRteImages(string $value): string
    {
        $value = preg_replace_callback(
            '~(data-htmlarea-file-uid=")(\d+)~',
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
     * Replace
     * t3://file?uid=123 => t3://file?uid=234
     * and
     * data-htmlarea-file-uid="123" => data-htmlarea-file-uid="234"
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
    protected function updateRecord(array $properties, string $tableName): void
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
        return array_key_exists($tableName, $this->getPropertiesWithLinks())
            || array_key_exists($tableName, $this->getPropertiesWithRelations())
            || array_key_exists($tableName, $this->getPropertiesWithRelationsInFlexForms());
    }

    /**
     * @return array
     */
    protected function getPropertiesWithLinks(): array
    {
        return (array)$this->configuration['linkMapping']['propertiesWithLinks'];
    }

    /**
     * @return array
     */
    protected function getPropertiesWithRelations(): array
    {
        return (array)$this->configuration['linkMapping']['propertiesWithRelations'];
    }

    /**
     * @return array
     */
    protected function getPropertiesWithRelationsInFlexForms(): array
    {
        return (array)$this->configuration['linkMapping']['propertiesWithRelationsInFlexForms'];
    }
}