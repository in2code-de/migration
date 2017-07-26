<?php
namespace In2code\In2template\Migration\Import;

use In2code\In2template\Migration\Helper\DatabaseHelper;
use In2code\In2template\Migration\Helper\NormalizeHelper;
use In2code\In2template\Migration\Import\PropertyHelper\PropertyHelperInterface;
use In2code\In2template\Migration\Service\Log;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\ConsoleOutput;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class AbstractImporter
 */
abstract class AbstractImporter
{

    /**
     * Table name where to migrate to
     *
     * @var string
     */
    protected $tableName = '';

    /**
     * Table name from migrate to
     *
     * @var string
     */
    protected $tableNameOld = '';

    /**
     * Default fields
     *
     * @var array
     */
    protected $mappingDefault = [
        'uid' => 'uid',
        'pid' => 'pid'
    ];

    /**
     * Not listed fields will be ignored completely!
     *      oldfieldname => newfieldname
     *
     * @var array
     */
    protected $mapping = [];

    /**
     * Fill properties with hard values - example (always fill new.pid with 123):
     *      'pid' => 123
     *
     * @var array
     */
    protected $values = [];

    /**
     * PropertyHelpers are called after initial build via mapping
     *
     *      "newProperty" => [class1, class2]
     *
     * @var array
     */
    protected $propertyHelpers = [];

    /**
     * Filter selection of old records like " and pid = 123"
     *
     * @var string
     */
    protected $additionalWhere = '';

    /**
     * Group selection of old records like "url"
     *
     * @var string
     */
    protected $groupBy = '';

    /**
     * Per default the query will try to order by "pid,uid" (if fields are existing). This value can be overwritten.
     *  like "uid DESC"
     *
     * @var string
     */
    protected $orderByOverride = '';

    /**
     * @var string
     */
    protected $helperInterface = PropertyHelperInterface::class;

    /**
     * @var Log|null
     */
    protected $log = null;

    /**
     * @var DatabaseHelper|null
     */
    protected $databaseHelper = null;

    /**
     * @var bool
     */
    protected $truncate = true;

    /**
     * Keep uid when importing to new table
     *
     * @var bool
     */
    protected $keepIdentifiers = true;

    /**
     * @var array
     */
    protected $configuration = [
        'dryrun' => true,
        'limitToRecord' => 0,
        'limitToPage' => 0,
        'recursive' => false
    ];

    /**
     * AbstractImporter constructor.
     *
     * @param ConsoleOutput $output
     * @throws \Exception
     */
    public function __construct(ConsoleOutput $output)
    {
        $this->log = $this->getObjectManager()->get(Log::class)->setOutput($output);
        $this->databaseHelper = $this->getObjectManager()->get(DatabaseHelper::class);
        if (!$this->databaseHelper->isTableExisting($this->tableNameOld)) {
            throw new \Exception('Table ' . $this->tableNameOld . ' does not exist!');
        }
        if (!$this->databaseHelper->isTableExisting($this->tableName)) {
            throw new \Exception('Table ' . $this->tableName . ' does not exist!');
        }
    }

    /**
     * @param array $configuration
     * @return array
     */
    public function startMigration(array $configuration): array
    {
        $this->configuration = $configuration;
        $this->truncateTable();
        $records = $this->importRecords();
        $this->finalMessage($records);
        return $records;
    }

    /**
     * @param array $records
     * @return void
     */
    protected function finalMessage(array $records)
    {
        if ($this->configuration['dryrun'] === false) {
            $message = count($records) . ' records successfully imported to ' . $this->tableName;
        } else {
            $message = count($records) . ' records could be imported without dryrun to ' . $this->tableName;
        }
        $this->log->addMessage($message);
    }

    /**
     * @return array
     */
    protected function importRecords(): array
    {
        $normalizeHelper = $this->getNormalizeHelper();
        $newRecords = [];
        foreach ($this->getOldRecords() as $oldRecord) {
            $oldRecord = $normalizeHelper->normalizeRecord($oldRecord);
            $newRecord = $this->buildRow($oldRecord);
            $this->importRecord($newRecord);
            $newRecords[] = $newRecord;
        }
        return $newRecords;
    }

    /**
     * @param array $oldRow
     * @return array
     */
    protected function buildRow(array $oldRow): array
    {
        $newRow = $this->buildRowFromMapping($oldRow);
        $newRow = $this->buildRowFromValues($newRow);
        $newRow = $this->buildRowFromHelpers($oldRow, $newRow);
        $newRow = $this->genericChanges($newRow);
        return $newRow;
    }

    /**
     * Build row from $this->mapping and $this->mappingDefault
     *
     * @param array $oldRow
     * @return array
     */
    protected function buildRowFromMapping(array $oldRow): array
    {
        $mapping = $this->mapping + $this->getMappingDefault();
        $newRow = [];
        foreach ($mapping as $oldPropertyName => $newPropertyName) {
            $newProperty = $oldRow[$oldPropertyName];
            $newRow[$newPropertyName] = $newProperty;
        }
        return $newRow;
    }

    /**
     * @param array $row
     * @return array
     */
    protected function buildRowFromValues(array $row): array
    {
        return $this->values + $row;
    }

    /**
     * @return array
     */
    protected function getMappingDefault(): array
    {
        $mappingDefault = $this->mappingDefault;
        $additionalDefaults = [
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'hidden' => 'hidden',
            'disable' => 'disable',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'cruser_id' => 'cruser_id'
        ];
        foreach ($additionalDefaults as $key => $value) {
            if ($this->databaseHelper->isFieldExistingInTable($this->tableName, $value)
                && $this->databaseHelper->isFieldExistingInTable($this->tableNameOld, $value)) {
                $mappingDefault += [$key => $value];
            }
        }
        return $mappingDefault;
    }

    /**
     * @param array $oldRow
     * @param array $newRow
     * @return array
     * @throws \Exception
     */
    protected function buildRowFromHelpers(array $oldRow, array $newRow): array
    {
        foreach ($this->propertyHelpers as $propertyName => $helpersConfig) {
            foreach ($helpersConfig as $helperConfig) {
                if (!class_exists($helperConfig['className'])) {
                    throw new \Exception('Class ' . $helperConfig['className'] . ' does not exists');
                }
                if (is_subclass_of($helperConfig['className'], $this->helperInterface)) {
                    /** @var PropertyHelperInterface $helperClass */
                    $helperClass = GeneralUtility::makeInstance(
                        $helperConfig['className'],
                        $oldRow,
                        $newRow,
                        $propertyName,
                        $this->tableNameOld,
                        $this->tableName,
                        (array)$helperConfig['configuration']
                    );
                    $helperClass->initialize();
                    $newRow = $helperClass->returnRecord();
                } else {
                    throw new \Exception('Class does not implement ' . $this->helperInterface);
                }
            }
        }
        return $newRow;
    }

    /**
     * @param array $row
     * @return array
     */
    protected function genericChanges(array $row): array
    {
        if ($this->databaseHelper->isFieldExistingInTable($this->tableName, '_migrated')) {
            $row['_migrated'] = 1;
        }
        if ($this->databaseHelper->isFieldExistingInTable($this->tableName, '_migrated_uid')) {
            $row['_migrated_uid'] = $row['uid'];
        }
        if ($this->databaseHelper->isFieldExistingInTable($this->tableName, '_migrated_table')) {
            $row['_migrated_table'] = $this->tableNameOld;
        }
        if (!$this->keepIdentifiers) {
            unset($row['uid']);
        }
        return $row;
    }

    /**
     * @return array
     */
    protected function getOldRecords(): array
    {
        return (array)$this->getDatabase()->exec_SELECTgetRows(
            '*',
            $this->tableNameOld,
            $this->getWhereClause(),
            $this->getGroupByString(),
            $this->getOrderByString()
        );
    }

    /**
     * @return string
     */
    protected function getWhereClause(): string
    {
        $whereClause = '1';
        if ($this->databaseHelper->isFieldExistingInTable($this->tableNameOld, 'deleted')) {
            $whereClause = 'deleted=0';
        }
        if (is_numeric($this->configuration['limitToRecord']) && $this->configuration['limitToRecord'] > 0) {
            $whereClause .= ' and uid=' . (int)$this->configuration['limitToRecord'];
        }
        if (!is_numeric($this->configuration['limitToRecord'])) {
            $parts = GeneralUtility::trimExplode(':', $this->configuration['limitToRecord'], true);
            if ($this->tableName === $parts[0]) {
                if (is_numeric($parts[1]) && $parts[1] > 0) {
                    $whereClause .= ' and uid=' . (int)$parts[1];
                }
            } else {
                $whereClause .= ' and 1=2';
            }
        }
        if ($this->configuration['limitToPage'] > 0) {
            if ($this->configuration['recursive'] === true) {
                $whereClause .= ' and pid in (';
                $whereClause .= $this->getTreeBranchesFromStartingPoint($this->configuration['limitToPage']) . ')';
            } else {
                $whereClause .= ' and pid=' . (int)$this->configuration['limitToPage'];
            }
        }
        $whereClause .= $this->additionalWhere;
        return $whereClause;
    }

    /**
     * @return string
     */
    protected function getGroupByString(): string
    {
        return $this->groupBy;
    }

    /**
     * @return string
     */
    protected function getOrderByString(): string
    {
        $orderByString = '';
        if ($this->databaseHelper->isFieldExistingInTable($this->tableNameOld, 'pid')) {
            $orderByString .= 'pid,';
        }
        if ($this->databaseHelper->isFieldExistingInTable($this->tableNameOld, 'uid')) {
            $orderByString .= 'uid,';
        }
        if (!empty($this->orderByOverride)) {
            $orderByString = $this->orderByOverride;
        }
        return rtrim($orderByString, ',');
    }

    /**
     * @param array $row
     * @return void
     */
    protected function importRecord(array $row)
    {
        if (!$this->configuration['dryrun']) {
            $this->getDatabase()->exec_INSERTquery($this->tableName, $row);
            $this->log->addMessage('New record inserted', $row, $this->tableName);
        } else {
            $this->log->addMessage('New record could be inserted', $row, $this->tableName);
        }
    }

    /**
     * Table will be truncated if
     *      - we're not running in drymode (dryrun)
     *      - truncate is activated (default)
     *      - limitToRecord is not set
     *
     * @return void
     */
    protected function truncateTable()
    {
        if (!$this->configuration['dryrun'] && $this->truncate && $this->configuration['limitToRecord'] === '0') {
            $this->getDatabase()->exec_TRUNCATEquery($this->tableName);
            $this->log->addMessage('Table ' . $this->tableName . ' truncated');
        }
    }

    /**
     * @param int $startPage
     * @return string
     */
    protected function getTreeBranchesFromStartingPoint($startPage): string
    {
        $queryGenerator = $this->getObjectManager()->get(QueryGenerator::class);
        return $queryGenerator->getTreeList($startPage, 20, 0, 1);
    }

    /**
     * @return NormalizeHelper
     */
    protected function getNormalizeHelper(): NormalizeHelper
    {
        return $this->getObjectManager()->get(NormalizeHelper::class);
    }

    /**
     * @return DatabaseConnection
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getDatabase(): DatabaseConnection
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager(): ObjectManager
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }
}
