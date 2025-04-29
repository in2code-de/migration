<?php

declare(strict_types=1);
namespace In2code\Migration\Port;

use Doctrine\DBAL\Driver\Exception as ExceptionDbalDriver;
use Doctrine\DBAL\Exception as ExceptionDbal;
use In2code\Migration\Events\ImportBeforeEvent;
use In2code\Migration\Events\ImportFilesFromContentEvent;
use In2code\Migration\Events\ImportFilesFromOnlineSourceEvent;
use In2code\Migration\Events\ImportFilesFromUriEvent;
use In2code\Migration\Events\ImportInitialEvent;
use In2code\Migration\Exception\ConfigurationException;
use In2code\Migration\Exception\FileNotFoundException;
use In2code\Migration\Port\Service\LinkMappingService;
use In2code\Migration\Port\Service\MappingService;
use In2code\Migration\Utility\DatabaseUtility;
use In2code\Migration\Utility\FileUtility;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Import
{
    /**
     * Absolute file name with JSON export string
     *
     * @var string
     */
    protected string $file = '';

    /**
     * Page to import into
     *
     * @var int
     */
    protected int $pid = 0;

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
    protected array $configuration = [];

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
    protected array $jsonArray = [];

    protected ?MappingService $mappingService = null;
    private EventDispatcherInterface $eventDispatcher;

    /**
     * @param string $file
     * @param int $pid
     * @param array $configuration
     * @throws ConfigurationException
     * @throws FileNotFoundException
     */
    public function __construct(string $file, int $pid, array $configuration = [])
    {
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
        $this->mappingService = GeneralUtility::makeInstance(MappingService::class, $configuration);

        /** @var ImportInitialEvent $event */
        $event = $this->eventDispatcher->dispatch(new ImportInitialEvent($file, $pid, $configuration));
        $this->file = $event->getFile();
        $this->pid = $event->getPid();
        $this->configuration = $event->getConfiguration();

        $this->checkFile();
        $this->setJson();
    }

    /**
     * @return int
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    public function import(): int
    {
        /** @var ImportBeforeEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ImportBeforeEvent($this->jsonArray, $this->pid, $this->file, $this->configuration)
        );

        $this->jsonArray = $event->getJsonArray();
        $this->pid = $event->getPid();
        $this->file = $event->getFile();
        $this->configuration = $event->getConfiguration();

        $this->importPages();
        $this->importRecords();
        $this->importFileRecords();
        $this->importFileMetadata();
        $this->importFileReferenceRecords();
        $this->importFiles();
        $this->importMmRecords();
        $this->updateLinks();

        return count($this->jsonArray['records']['pages']);
    }

    /**
     * @return void
     * @throws ExceptionDbal
     * @throws ExceptionDbalDriver
     */
    protected function importPages(): void
    {
        foreach ($this->jsonArray['records']['pages'] ?? [] as $properties) {
            $this->insertRecord($properties, 'pages');
        }
    }

    /**
     * @return void
     * @throws ExceptionDbal
     * @throws ExceptionDbalDriver
     */
    protected function importRecords(): void
    {
        $excludedTables = array_merge(
            ['pages', 'sys_file', 'sys_file_reference', 'sys_file_metadata'],
            $this->configuration['excludedTables']
        );
        foreach (array_keys($this->jsonArray['records'] ?? []) as $tableName) {
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
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    protected function importFileRecords(): void
    {
        foreach ($this->jsonArray['records']['sys_file'] ?? [] as $properties) {
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

    /**
     * @return void
     * @throws ExceptionDbal
     * @throws ExceptionDbalDriver
     */
    protected function importFileMetadata(): void
    {
        foreach ($this->jsonArray['records']['sys_file_metadata'] ?? [] as $properties) {
            $properties['file'] = $this->mappingService->getNewFromOld($properties['file'], 'sys_file');
            $this->insertRecord($properties, 'sys_file_metadata');
        }
    }

    /**
     * @return void
     * @throws ExceptionDbal
     * @throws ExceptionDbalDriver
     */
    protected function importFileReferenceRecords(): void
    {
        foreach ($this->jsonArray['records']['sys_file_reference'] ?? [] as $properties) {
            $this->insertRecord($this->preparePropertiesForSysFileReference($properties), 'sys_file_reference');
        }
    }

    /**
     * Write physical files to filesystem
     *
     * @return void
     */
    protected function importFiles(): void
    {
        foreach ($this->jsonArray['files'] ?? [] as $properties) {
            if (($this->configuration['importFilesFromOnlineResource'] ?? '') === '') {
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
            } else {
                $this->importFileFromOnlineSource($properties['path'], $this->configuration['overwriteFiles']);
            }
        }
    }

    /**
     * @param string $uri Absolute start path like "/var/www/fileadmin/start.pdf"
     * @param string $path Relative target path like "fileadmin/folder/"
     * @param bool $overwriteFiles
     * @return void
     */
    protected function importFileFromUri(string $uri, string $path, bool $overwriteFiles): void
    {
        /** @var ImportFilesFromUriEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ImportFilesFromUriEvent($uri, $path, $overwriteFiles, $this->configuration)
        );
        FileUtility::copyFile(
            $event->getUri(),
            GeneralUtility::getFileAbsFileName($event->getPath()),
            $event->isOverwriteFiles()
        );
    }

    /**
     * @param string $base64content File content as base64
     * @param string $path Relative target path like "fileadmin/folder/"
     * @param bool $overwriteFiles
     * @return bool
     */
    protected function importFileFromBase64(string $base64content, string $path, bool $overwriteFiles): bool
    {
        /** @var ImportFilesFromContentEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ImportFilesFromContentEvent($base64content, $path, $overwriteFiles, $this->configuration)
        );
        return FileUtility::writeFileFromBase64Code(
            $event->getPath(),
            $event->getBase64content(),
            $event->isOverwriteFiles()
        );
    }

    protected function importFileFromOnlineSource(string $path, bool $overwriteFiles): void
    {
        /** @var ImportFilesFromOnlineSourceEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ImportFilesFromOnlineSourceEvent($path, $overwriteFiles, $this->configuration)
        );
        if ($event->isToLoadFromSource() === false) {
            return;
        }
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $response = $requestFactory->request($event->getOnlineSource(), 'GET');
        if ($response->getStatusCode() === 200) {
            $fileContent = $response->getBody()->getContents();
            $base64Content = base64_encode($fileContent);
            FileUtility::writeFileFromBase64Code(
                $event->getPath(),
                $base64Content,
                $event->isOverwriteFiles()
            );
        }
    }

    /**
     * @return void
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    protected function importMmRecords(): void
    {
        foreach ($this->jsonArray['mm'] ?? [] as $tableMm => $records) {
            if (DatabaseUtility::isTableExisting($tableMm)) {
                foreach ($records as $record) {
                    $propertiesNew = $this->getNewPropertiesForMmRelation($record, $tableMm);
                    if ($propertiesNew['uid_local'] !== 0 && $propertiesNew['uid_foreign'] !== 0) {
                        $this->insertRecord($this->getNewPropertiesForMmRelation($record, $tableMm), $tableMm);
                    }
                }
            }
        }
    }

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
        foreach ($this->configuration['relations'] ?? [] as $configurations) {
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
     * @throws ExceptionDbal
     * @throws ExceptionDbalDriver
     */
    protected function updateLinks(): void
    {
        $linkService = GeneralUtility::makeInstance(
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
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    protected function insertRecord(array $properties, string $tableName): void
    {
        $oldIdentifier = (int)($properties['uid'] ?? 0);
        $connection = DatabaseUtility::getConnectionForTable($tableName);
        $properties = $this->prepareProperties($properties, $tableName);
        if (DatabaseUtility::isFieldExistingInTable('_imported', $tableName)) {
            $properties['_imported'] = $oldIdentifier;
        }
        $connection->insert($tableName, $properties);
        if ($oldIdentifier > 0) {
            $newIdentifier = (int)$connection->lastInsertId($tableName);
            $this->mappingService->setNew($newIdentifier, $oldIdentifier, $tableName);
        }
    }

    /**
     * @param string $identifier
     * @param int $storage
     * @return bool
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    protected function isFileRecordAlreadyExisting(string $identifier, int $storage): bool
    {
        return $this->findFileUidByStorageAndIdentifier($identifier, $storage) > 0;
    }

    /**
     * @param string $identifier
     * @param int $storage
     * @return int
     * @throws ExceptionDbal
     */
    protected function findFileUidByStorageAndIdentifier(string $identifier, int $storage): int
    {
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable('sys_file');
        return (int)$queryBuilder
            ->select('uid')
            ->from('sys_file')
            ->where('storage=' . $storage . ' and identifier="' . $identifier . '"')
            ->executeQuery()
            ->fetchOne();
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
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    protected function prepareProperties(array $properties, string $tableName): array
    {
        if (array_key_exists('uid', $properties)) {
            unset($properties['uid']);
        }
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
        $jsonArray = json_decode($content, true);
        if ($jsonArray === null) {
            throw new ConfigurationException('No data in given json file', 1569913542);
        }
        $this->jsonArray = $jsonArray;
        $this->setPidForFirstPage();
    }

    /**
     * Overwrite pid of the very first page in jsonArray and store the new pid in the mapping service
     *
     * @return void
     */
    protected function setPidForFirstPage(): void
    {
        foreach ($this->jsonArray['records']['pages'] ?? [] as $properties) {
            $oldPid = (int)$properties['pid'];
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

    public function getJsonArray(): array
    {
        return $this->jsonArray;
    }

    public function setJsonArray(array $jsonArray): self
    {
        $this->jsonArray = $jsonArray;
        return $this;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }
}
