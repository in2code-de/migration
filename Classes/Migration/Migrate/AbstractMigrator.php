<?php
namespace In2code\In2template\Migration\Migrate;

use In2code\In2template\Migration\Helper\DatabaseHelper;
use In2code\In2template\Migration\Helper\PropertiesQueueHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\PropertyHelperInterface;
use In2code\In2template\Migration\Service\Log;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\ConsoleOutput;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class AbstractMigrator
 */
abstract class AbstractMigrator
{

    /**
     * Table name what's to migrate
     *
     * @var string
     */
    protected $tableName = '';

    /**
     * Simply copy values from one to another column
     *
     * @var array
     */
    protected $mapping = [];

    /**
     * Fill properties with hard values - example (always fill header_layout with 1):
     *      'header_layout' => 1
     *
     * @var array
     */
    protected $values = [];

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
    protected $propertyHelpers = [];

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
     * Enforce migration of _migrated=1 records
     *
     * @var bool
     */
    protected $enforce = false;

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
     * AbstractMigrator constructor.
     *
     * @param ConsoleOutput $output
     * @throws \Exception
     */
    public function __construct(ConsoleOutput $output)
    {
        $this->log = $this->getObjectManager()->get(Log::class)->setOutput($output);
        $this->databaseHelper = $this->getObjectManager()->get(DatabaseHelper::class);
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
        $records = $this->updateRecords();
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
            $message = count($records) . ' records successfully migrated in ' . $this->tableName;
        } else {
            $message = count($records) . ' records could be migrated without dryrun in ' . $this->tableName;
        }
        $this->log->addMessage($message);
    }

    /**
     * @return array
     */
    protected function updateRecords(): array
    {
        $records = $this->getRecords();
        foreach ($records as &$record) {
            $this->log->addNote(
                'Start migrating ' . $this->tableName . ' (pid' . $record['pid'] . ' / uid' . $record['uid'] . ') ...'
            );
            $record = $this->updateRow($record);
            $this->updateRecord($record);
        }
        return $records;
    }

    /**
     * @param array $row
     * @return array
     */
    protected function updateRow(array $row): array
    {
        $row = $this->updateRowFromMapping($row);
        $row = $this->updateRowFromValues($row);
        $row = $this->updateRowFromPropertyHelpers($row);
        $row = $this->genericChanges($row);
        return $row;
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
        return $row;
    }

    /**
     * Update row from $this->mapping and $this->mappingDefault
     *
     * @param array $row
     * @return array
     */
    protected function updateRowFromMapping(array $row): array
    {
        $mapping = $this->mapping;
        foreach ($mapping as $oldPropertyName => $newPropertyName) {
            $newProperty = $row[$oldPropertyName];
            $row[$newPropertyName] = $newProperty;
        }
        return $row;
    }

    /**
     * @param array $row
     * @return array
     */
    protected function updateRowFromValues(array $row): array
    {
        return $this->values + $row;
    }

    /**
     * @param array $row
     * @return array
     * @throws \Exception
     */
    protected function updateRowFromPropertyHelpers(array $row): array
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
                        $row,
                        $propertyName,
                        $this->tableName,
                        (array)$helperConfig['configuration']
                    );
                    $helperClass->initialize();
                    $row = $helperClass->returnRecord();
                } else {
                    throw new \Exception('Class does not implement ' . $this->helperInterface);
                }
            }
        }
        return $row;
    }

    /**
     * @return array
     */
    protected function getRecords(): array
    {
        return (array)$this->getDatabase()->exec_SELECTgetRows(
            '*',
            $this->tableName,
            $this->buildWhereClause(),
            '',
            'pid, uid'
        );
    }

    /**
     * @return string
     */
    protected function buildWhereClause(): string
    {
        $whereClause = 'deleted=0';
        $whereClause = $this->buildWhereClauseForLimitToRecord($whereClause);
        $whereClause = $this->buildWhereClauseForLimitToPage($whereClause);
        if ($this->enforce === false && $this->databaseHelper->isFieldExistingInTable($this->tableName, '_migrated')) {
            $whereClause .= ' and _migrated = 0';
        }
        return $whereClause;
    }

    /**
     * @param array $row
     * @return void
     * @throws \Exception
     */
    protected function updateRecord(array $row)
    {
        if (!array_key_exists('uid', $row)) {
            throw new \Exception('Record of table ' . $this->tableName . ' can not be updated without UID field');
        }
        if (!$this->configuration['dryrun']) {
            $queueHelper = $this->getObjectManager()->get(PropertiesQueueHelper::class);
            $row = $queueHelper->updatePropertiesWithPropertiesFromQueue($this->tableName, (int)$row['uid'], $row);

            $this->getDatabase()->exec_UPDATEquery($this->tableName, 'uid=' . (int)$row['uid'], $row);
            $this->log->addMessage('Record updated', $row, $this->tableName);
        } else {
            $this->log->addMessage('Record could be updated', $row, $this->tableName);
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

    /**
     * @param $whereClause
     * @return string
     */
    protected function buildWhereClauseForLimitToRecord($whereClause): string
    {
        if (is_numeric($this->configuration['limitToRecord']) && $this->configuration['limitToRecord'] > 0) {
            $whereClause .= ' and uid=' . (int)$this->configuration['limitToRecord'];
        }
        if (!is_numeric($this->configuration['limitToRecord'])) {
            $parts = GeneralUtility::trimExplode(':', $this->configuration['limitToRecord'], true);
            if ($this->tableName === $parts[0]) {
                if (is_numeric($parts[1]) && $parts[1] > 0) {
                    $whereClause .= ' and uid=' . (int)$parts[1];
                    return $whereClause;
                }
                return $whereClause;
            } else {
                $whereClause .= ' and 1=2';
                return $whereClause;
            }
        }
        return $whereClause;
    }

    /**
     * @param $whereClause
     * @return string
     */
    protected function buildWhereClauseForLimitToPage($whereClause): string
    {
        $field = 'pid';
        if ($this->tableName === 'pages') {
            $field = 'uid';
        }
        if ($this->configuration['limitToPage'] > 0) {
            if ($this->configuration['recursive'] === true) {
                $whereClause .= ' and ' . $field . ' in (';
                $whereClause .= $this->getTreeBranchesFromStartingPoint($this->configuration['limitToPage']) . ')';
                return $whereClause;
            } else {
                $whereClause .= ' and ' . $field . '=' . (int)$this->configuration['limitToPage'];
                return $whereClause;
            }
        }
        return $whereClause;
    }
}
