<?php
namespace In2code\Migration\Service;

use Doctrine\DBAL\DBALException;
use In2code\Migration\Utility\DatabaseUtility;
use In2code\Migration\Utility\FileUtility;
use In2code\Migration\Utility\ObjectUtility;

/**
 * Class ImportService
 */
class ImportService
{

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
     * @var array
     */
    protected $excludedTables = [];

    /**
     * @var bool
     */
    protected $overwriteFiles = false;

    /**
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
     * @param array $excludedTables
     * @param bool $overwriteFiles
     */
    public function __construct(string $file, int $pid, array $excludedTables = [], bool $overwriteFiles = false)
    {
        $this->mappingService = ObjectUtility::getObjectManager()->get(MappingService::class);
        $this->file = $file;
        $this->pid = $pid;
        $this->excludedTables = $excludedTables;
        $this->overwriteFiles = $overwriteFiles;
        $this->checkFile();
        $this->setJson();
    }

    /**
     * @return bool
     * @throws DBALException
     */
    public function import(): bool
    {
        $this->importPages();
        $this->importRecords();
        $this->importFileRecords();
        $this->importFileReferenceRecords();
        $this->importImages();
        $this->updateLinks();
        return true;
    }

    /**
     * @return void
     * @throws DBALException
     */
    protected function importPages()
    {
        foreach ($this->jsonArray['records']['pages'] as $properties) {
            $this->insertRecord($properties, 'pages');
        }
    }

    /**
     * @return void
     * @throws DBALException
     */
    protected function importRecords()
    {
        $excludedTables = ['pages', 'sys_file', 'sys_file_reference'] + $this->excludedTables;
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
    protected function importFileRecords()
    {
        if (is_array($this->jsonArray['records']['sys_file'])) {
            foreach ($this->jsonArray['records']['sys_file'] as $properties) {
                if ($this->isFileRecordAlradyExisting($properties['identifier'], (int)$properties['storage'])) {
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
    protected function importFileReferenceRecords()
    {
        if (is_array($this->jsonArray['records']['sys_file_reference'])) {
            foreach ($this->jsonArray['records']['sys_file_reference'] as $properties) {
                $this->insertRecord($this->preparePropertiesForSysFileReference($properties), 'sys_file_reference');
            }
        }
    }

    /**
     * @return void
     */
    protected function importImages()
    {
        if (is_array($this->jsonArray['files'])) {
            foreach ($this->jsonArray['files'] as $properties) {
                FileUtility::writeFileFromBase64Code($properties['path'], $properties['base64'], $this->overwriteFiles);
            }
        }
    }

    /**
     * @return void
     */
    protected function updateLinks()
    {
        $linkService = ObjectUtility::getObjectManager()->get(LinkService::class, $this->mappingService);
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
    protected function insertRecord(array $properties, string $tableName)
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
    protected function isFileRecordAlradyExisting(string $identifier, int $storage): bool
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
     * Do some magic with the record properties:
     * - Remove uid field
     * - Remove not existing fields (compare with db structure of the target system)
     * - Update tstamp field (if any)
     * - Update pid field with the new from mapping
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
     */
    protected function setJson()
    {
        $content = file_get_contents($this->file);
        $array = json_decode($content, true);
        if ($array === null) {
            throw new \LogicException('No json configuration found in given file', 1549546231);
        }
        $this->jsonArray = $array;
        $this->setPidForFirstPage();
    }

    /**
     * Overwrite pid of the very first page in jsonArray and store the new pid in the mapping service
     *
     * @return void
     */
    protected function setPidForFirstPage()
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
     */
    protected function checkFile()
    {
        if (is_file($this->file) === false) {
            throw new \LogicException('File not found: ' . $this->file, 1549472056);
        }
    }
}
