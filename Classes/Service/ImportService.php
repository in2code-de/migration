<?php
namespace In2code\Migration\Service;

use Doctrine\DBAL\DBALException;
use In2code\Migration\Utility\DatabaseUtility;
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
     * @var array
     */
    protected $jsonArray = [];

    /**
     * @var ImportMappingService
     */
    protected $importMappingService = null;

    /**
     * ImportService constructor.
     * @param string $file
     * @param int $pid
     * @param array $excludedTables
     */
    public function __construct(string $file, int $pid, array $excludedTables = [])
    {
        $this->importMappingService = ObjectUtility::getObjectManager()->get(ImportMappingService::class);
        $this->file = $file;
        $this->pid = $pid;
        $this->excludedTables = $excludedTables;
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
        return true;
    }

    /**
     * @return void
     * @throws DBALException
     */
    protected function importPages()
    {
        foreach ($this->jsonArray['records']['pages'] as $pageProperties) {
            $this->insertRecord($pageProperties, 'pages');
        }
    }

    /**
     * @return void
     * @throws DBALException
     */
    protected function importRecords()
    {
        $excludedTables = ['pages'] + $this->excludedTables;
        foreach (array_keys($this->jsonArray['records']) as $tableName) {
            if (in_array($tableName, $excludedTables) === false) {
                foreach ($this->jsonArray['records'][$tableName] as $properties) {
                    $this->insertRecord($properties, $tableName);
                }
            }
        }
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
        $connection = DatabaseUtility::getConnectionForTable($tableName);
        $connection->insert($tableName, $this->prepareProperties($properties, $tableName));
        $newIdentifier = $connection->lastInsertId($tableName);
        $this->importMappingService->setIdentifierMapping((int)$newIdentifier, (int)$properties['uid'], $tableName);
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
            $newPid = $this->importMappingService->getNewPidFromOldPid((int)$properties['pid']);
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
     * @return void
     */
    protected function setJson()
    {
        $content = file_get_contents($this->file);
        $this->jsonArray = json_decode($content, true);
        $this->setPidForFirstPage();
    }

    /**
     * @return void
     */
    protected function setPidForFirstPage()
    {
        foreach ($this->jsonArray['records']['pages'] as &$properties) {
            $oldPid = (int)$properties['pid'];
            $properties['pid'] = $this->pid;
            break;
        }
        $this->importMappingService->setIdentifierMapping($this->pid, $oldPid, 'pages');
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
