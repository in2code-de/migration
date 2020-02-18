<?php
declare(strict_types=1);
namespace In2code\Migration\Port;

use Doctrine\DBAL\DBALException;
use In2code\Migration\Exception\ConfigurationException;
use In2code\Migration\Exception\FileNotFoundException;
use In2code\Migration\Port\Service\LinkMappingService;
use In2code\Migration\Port\Service\MappingService;
use In2code\Migration\Signal\SignalTrait;
use In2code\Migration\Utility\DatabaseUtility;
use In2code\Migration\Utility\FileUtility;
use In2code\Migration\Utility\ObjectUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;

/**
 * Class ImportService
 */
class Import
{
    use SignalTrait;

    /**
     * Absolute file name with JSON export string
     *
     * @var string
     */
    protected $file = '';

    /**
     * Page to import into
     *
     * @var int
     */
    protected $pid = 0;

    /**
     * Hold the complete configuration like
     *
     *  'excludedTables' => [
     *      'be_users'
     *  ],
     *  'relations' => [
     *      'pages' => [
     *          [
     *              'table' => 'sys_category_record_mm',
     *              'uid_local' => 'sys_category',
     *              'uid_foreign' => 'pages',
     *              'additional' => [
     *                  'tablenames' => 'pages',
     *                  'fieldname' => 'categories'
     *              ]
     *          ]
     *      ]
     *  ]
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * Example array from json file to import
     *
     * Example content:
     *  [
     *      'records' => [
     *          'pages' => [
     *              [
     *                  'uid' => 123,
     *                  'title' => 'page title'
     *              ]
     *          ],
     *          'tt_content' => [
     *              [
     *                  'uid' => 1234,
     *                  'header' => 'content header'
     *              ]
     *          ]
     *      ],
     *      'files' => [
     *          12345 => [
     *              'path' => 'fileadmin/file.pdf',
     *              'base64' => 'base64:abcdef1234567890'
     *              'fileIdentifier' => 12345
     *          ],
     *          12346 => [
     *              'path' => 'fileadmin/file.pdf',
     *              'uri' => '/var/www/domain.org/public/fileadmin/file.pdf'
     *              'fileIdentifier' => 12346
     *          ]
     *      ],
     *      'mm' => [
     *          'sys_category_record_mm' => [
     *              [
     *                  'uid_local' => 123,
     *                  'uid_foreign' => 124,
     *                  'tablenames' => 'pages',
     *                  'fieldname' => 'categories'
     *              ]
     *          ]
     *      ]
     *  ]
     *
     * @var array
     */
    protected $jsonArray = [];

    /**
     * @var MappingService
     */
    protected $mappingService = null;

    /**
     * ImportService constructor.
     * @param string $file
     * @param int $pid
     * @param array $configuration
     * @throws ConfigurationException
     * @throws FileNotFoundException
     * @throws InvalidSlotException
     * @throws InvalidSlotReturnException
     */
    public function __construct(string $file, int $pid, array $configuration = [])
    {
        $this->mappingService = ObjectUtility::getObjectManager()->get(MappingService::class, $configuration);
        $this->file = $file;
        $this->pid = $pid;
        $this->configuration = $configuration;
        $this->checkFile();
        $this->setJson();
        $this->signalDispatch(__CLASS__, 'beforeImport', [$this]);
    }

    /**
     * @return int
     * @throws DBALException
     * @throws InvalidSlotException
     * @throws InvalidSlotReturnException
     */
    public function import(): int
    {
        $this->importPages();
        $this->importRecords();
        $this->importFileRecords();
        $this->importFileReferenceRecords();
        $this->importFiles();
        $this->importMmRecords();
        $this->updateLinks();
        $this->signalDispatch(__CLASS__, 'afterImport', [$this]);
        return count($this->jsonArray['records']['pages']);
    }

    /**
     * @return void
     * @throws DBALException
     */
    protected function importPages(): void
    {
        foreach ($this->jsonArray['records']['pages'] as $properties) {
            $this->insertRecord($properties, 'pages');
        }
    }

    /**
     * @return void
     * @throws DBALException
     */
    protected function importRecords(): void
    {
        $excludedTables = ['pages', 'sys_file', 'sys_file_reference'] + $this->configuration['excludedTables'];
        foreach (array_keys($this->jsonArray['records']) as $tableName) {
            if (in_array($tableName, $excludedTables) === false) {
                foreach ($this->jsonArray['records'][$tableName] as $properties) {
                    $this->insertRecord($properties, $tableName);
                }
            }
        }
    }

    /**
     * Import records from table sys_file but only if they are not yet existing
     *
     * @return void
     * @throws DBALException
     */
    protected function importFileRecords(): void
    {
        if (is_array($this->jsonArray['records']['sys_file'])) {
            foreach ($this->jsonArray['records']['sys_file'] as $properties) {
                if ($this->isFileRecordAlreadyExisting($properties['identifier'], (int)$properties['storage'])) {
                    $newUid = $this->findFileUidByStorageAndIdentifier(
                        $properties['identifier'],
                        (int)$properties['storage']
                    );
                    $this->mappingService->setNew($newUid, (int)$properties['uid'], 'sys_file');
                } else {
                    $this->insertRecord($properties, 'sys_file');
                }
            }
        }
    }

    /**
     * @return void
     * @throws DBALException
     */
    protected function importFileReferenceRecords(): void
    {
        if (is_array($this->jsonArray['records']['sys_file_reference'])) {
            foreach ($this->jsonArray['records']['sys_file_reference'] as $properties) {
                $this->insertRecord($this->preparePropertiesForSysFileReference($properties), 'sys_file_reference');
            }
        }
    }

    /**
     * Write physical files to filesystem
     *
     * @return void
     */
    protected function importFiles(): void
    {
        if (is_array($this->jsonArray['files'])) {
            foreach ($this->jsonArray['files'] as $properties) {
                if (!empty($properties['uri'])) {
                    $this->importFileFromUri(
                        $properties['uri'],
                        $properties['path'],
                        $this->configuration['overwriteFiles']
                    );
                }
                if (!empty($properties['base64'])) {
                    $this->importFileFromBase64(
                        $properties['base64'],
                        $properties['path'],
                        $this->configuration['overwriteFiles']
                    );
                }
            }
        }
    }

    /**
     * @param string $uri Absolute URI like /var/www/fileadmin/start.pdf
     * @param string $path Relative target path like fileadmin/folder/
     * @param bool $overwriteFiles
     * @return void
     */
    protected function importFileFromUri(string $uri, string $path, bool $overwriteFiles): void
    {
        FileUtility::copyFile($uri, GeneralUtility::getFileAbsFileName($path), $overwriteFiles);
    }

    /**
     * @param string $base64content
     * @param string $path
     * @param bool $overwriteFiles
     * @return bool
     */
    protected function importFileFromBase64(string $base64content, string $path, bool $overwriteFiles): bool
    {
        FileUtility::writeFileFromBase64Code($path, $base64content, $overwriteFiles);
    }

    /**
     * @return void
     * @throws DBALException
     */
    protected function importMmRecords(): void
    {
        if (is_array($this->jsonArray['mm'])) {
            foreach ($this->jsonArray['mm'] as $tableMm => $records) {
                if (DatabaseUtility::isTableExisting($tableMm)) {
                    foreach ($records as $record) {
                        $connection = DatabaseUtility::getConnectionForTable($tableMm);
                        $connection->insert($tableMm, $this->getNewPropertiesForMmRelation($record, $tableMm));
                    }
                }
            }
        }
    }

    /**
     * @param array $properties
     * @param string $tableMm
     * @return array
     */
    protected function getNewPropertiesForMmRelation(array $properties, string $tableMm): array
    {
        $configuration = $this->getMmConfigurationForRecord($properties, $tableMm);
        $tablesToReplace = ['uid_local', 'uid_foreign'];
        foreach ($tablesToReplace as $tableToReplace) {
            if ($this->mappingService->isTableExisting($configuration[$tableToReplace])) {
                $properties[$tableToReplace] = $this->mappingService->getNewFromOld(
                    $properties[$tableToReplace],
                    $configuration[$tableToReplace]
                );
            }
        }
        return $properties;
    }

    /**
     * Return a configuration from configuration file for this specific MM record like
     *  [
     *      'table' => 'sys_category_record_mm',
     *      'uid_local' => 'sys_category',
     *      'uid_foreign' => 'tt_content',
     *      'additional' => [
     *          'tablenames' => 'tt_content',
     *          'fieldname' => 'categories'
     *      ]
     *  ]
     *
     * @param array $properties
     * @param string $tableMm
     * @return array
     */
    protected function getMmConfigurationForRecord(array $properties, string $tableMm): array
    {
        $configurationMm = [];
        foreach ((array)$this->configuration['relations'] as $configurations) {
            foreach ($configurations as $configuration) {
                if ($configuration['table'] === $tableMm) {
                    if (!empty($configuration['additional'])) {
                        $fit = true;
                        foreach ($configuration['additional'] as $field => $value) {
                            if (array_key_exists($field, $properties) !== true || $properties[$field] !== $value) {
                                $fit = false;
                                break;
                            }
                        }
                        if ($fit === true) {
                            $configurationMm = $configuration;
                        }
                    } else {
                        $configurationMm = $configuration;
                    }
                }
            }
        }
        return $configurationMm;
    }

    /**
     * At the end links of already new imported records will be updated with new targets
     *
     * @return void
     * @throws DBALException
     */
    protected function updateLinks(): void
    {
        $linkService = ObjectUtility::getObjectManager()->get(
            LinkMappingService::class,
            $this->mappingService,
            $this->configuration
        );
        $linkService->updateLinksAndRecordsInNewRecords();
    }

    /**
     * Insert a record to the database and pass the new identifier to the mapping service
     *
     * @param array $properties
     * @param string $tableName
     * @return void
     * @throws DBALException
     */
    protected function insertRecord(array $properties, string $tableName): void
    {
        $oldIdentifier = (int)$properties['uid'];
        $connection = DatabaseUtility::getConnectionForTable($tableName);
        $properties = $this->prepareProperties($properties, $tableName);
        $connection->insert($tableName, $properties);
        $newIdentifier = (int)$connection->lastInsertId($tableName);
        if ($oldIdentifier > 0) {
            $this->mappingService->setNew($newIdentifier, $oldIdentifier, $tableName);
        }
    }

    /**
     * @param string $identifier
     * @param int $storage
     * @return bool
     */
    protected function isFileRecordAlreadyExisting(string $identifier, int $storage): bool
    {
        return $this->findFileUidByStorageAndIdentifier($identifier, $storage) > 0;
    }

    /**
     * @param string $identifier
     * @param int $storage
     * @return int
     */
    protected function findFileUidByStorageAndIdentifier(string $identifier, int $storage): int
    {
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable('sys_file');
        return (int)$queryBuilder
            ->select('uid')
            ->from('sys_file')
            ->where('storage=' . $storage . ' and identifier="' . $identifier . '"')
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * Do some magic with record properties:
     * - Remove uid field
     * - Remove not existing fields (compare with db structure of the target system)
     * - Update tstamp field (if any)
     * - Update pid field with the new from mapping
     * - Update l18n_parent/l10n_parent/l10n_source value
     *
     * @param array $properties
     * @param string $tableName
     * @return array
     * @throws DBALException
     */
    protected function prepareProperties(array $properties, string $tableName): array
    {
        unset($properties['uid']);
        if (DatabaseUtility::isFieldExistingInTable('tstamp', $tableName) === true) {
            $properties['tstamp'] = time();
        }
        if (DatabaseUtility::isFieldExistingInTable('pid', $tableName) === true) {
            $newPid = $this->mappingService->getNewPidFromOldPid((int)$properties['pid']);
            if ($newPid > 0) {
                $properties['pid'] = $newPid;
            }
        }
        foreach (array_keys($properties) as $field) {
            if (DatabaseUtility::isFieldExistingInTable($field, $tableName) === false) {
                unset($properties[$field]);
            }
        }
        $languageFields = ['l10n_parent', 'l10n_source', 'l18n_parent'];
        foreach ($languageFields as $languageField) {
            if (!empty($properties[$languageField])) {
                $properties[$languageField]
                    = $this->mappingService->getNewFromOld((int)$properties[$languageField], $tableName);
            }
        }
        return $properties;
    }

    /**
     * @param array $properties
     * @return array
     */
    protected function preparePropertiesForSysFileReference(array $properties): array
    {
        $properties['uid_local']
            = $this->mappingService->getNewFromOld((int)$properties['uid_local'], 'sys_file');
        $properties['uid_foreign']
            = $this->mappingService->getNewFromOld((int)$properties['uid_foreign'], $properties['tablenames']);
        return $properties;
    }

    /**
     * @return void
     * @throws ConfigurationException
     */
    protected function setJson(): void
    {
        $content = file_get_contents($this->file);
        $array = json_decode($content, true);
        if ($array === null) {
            throw new ConfigurationException('No data in in given json file', 1569913542);
        }
        $this->jsonArray = $array;
        $this->setPidForFirstPage();
    }

    /**
     * Overwrite pid of the very first page in jsonArray and store the new pid in the mapping service
     *
     * @return void
     */
    protected function setPidForFirstPage(): void
    {
        foreach ($this->jsonArray['records']['pages'] as &$properties) {
            $oldPid = (int)$properties['pid'];
            $properties['pid'] = $this->pid;
            $this->mappingService->setNewPid($this->pid, $oldPid);
            break;
        }
    }

    /**
     * @return void
     * @throws FileNotFoundException
     */
    protected function checkFile(): void
    {
        if (is_file($this->file) === false) {
            throw new FileNotFoundException('File not found: ' . $this->file, 1549472056);
        }
    }

    /**
     * Getter can be used in signals
     *
     * @noinspection PhpUnused
     * @return array
     */
    public function getJsonArray(): array
    {
        return $this->jsonArray;
    }

    /**
     * Setter can be used in signals
     *
     * @noinspection PhpUnused
     * @param array $jsonArray
     * @return Import
     */
    public function setJsonArray(array $jsonArray): self
    {
        $this->jsonArray = $jsonArray;
        return $this;
    }

    /**
     * Getter can be used in signals
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }
}
