<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\Repository;

use Doctrine\DBAL\DBALException;
use In2code\Migration\Migration\Log\Log;
use In2code\Migration\Utility\DatabaseUtility;
use In2code\Migration\Utility\ObjectUtility;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class GeneralRepository
 */
class GeneralRepository
{
    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * @var bool
     */
    protected $enforce = false;

    /**
     * @var Log
     */
    protected $log = null;

    /**
     * GeneralRepository constructor.
     * @param array $configuration
     * @param bool $enforce
     */
    public function __construct(array $configuration, bool $enforce)
    {
        $this->configuration = $configuration;
        $this->enforce = $enforce;
        $this->log = ObjectUtility::getObjectManager()->get(Log::class);
    }

    /**
     * @param string $tableName
     * @return array
     * @throws DBALException
     */
    public function getRecords(string $tableName): array
    {
        $connection = DatabaseUtility::getConnectionForTable($tableName);
        /** @noinspection SqlNoDataSourceInspection */
        $query = 'select * from ' . $tableName . ' where ' . $this->getWhereClause($tableName) . ' order by pid, uid;';
        return (array)$connection->executeQuery($query)->fetchAll();
    }

    /**
     * @param array $properties
     * @param string $tableName
     * @return void
     */
    public function updateRecord(array $properties, string $tableName)
    {
        if (array_key_exists('uid', $properties) === false) {
            throw new \LogicException(
                'Record of table ' . $tableName . ' needs a uid field for persisting',
                1568277411
            );
        }
        if ($this->getConfiguration('dryrun') === false) {
            $connection = DatabaseUtility::getConnectionForTable($tableName);
            $connection->update($tableName, $properties, ['uid' => (int)$properties['uid']]);
            $this->log->addMessage('Record updated', $properties, $tableName);
        } else {
            $this->log->addMessage('Record could be inserted', $properties, $tableName);
        }
    }

    /**
     * @param array $properties
     * @param string $tableName
     * @return void
     */
    public function insertRecord(array $properties, string $tableName)
    {
        if ($this->getConfiguration('dryrun') === false) {
            $connection = DatabaseUtility::getConnectionForTable($tableName);
            $connection->insert($tableName, $properties);
            $this->log->addMessage('Record inserted', $properties, $tableName);
        } else {
            $this->log->addMessage('Record could be inserted', $properties, $tableName);
        }
    }

    /**
     * Create a record if it does not exists yet in the database.
     * At the moment records will be added if it does not exist exactly with all properties. There is no update-logic
     * for same uids.
     *
     * @param string $tableName
     * @param array $row
     * @return int
     * @throws DBALException
     */
    public function createRecord(string $tableName, array $row): int
    {
        $uid = 0;
        $connection = DatabaseUtility::getConnectionForTable($tableName);
        $existingRow = (array)$connection->executeQuery('select * from ' . $tableName . ' where '
            . $this->buildWhereClauseFromPropertiesArray($row, ['tstamp', 'crdate', 'uid']))->fetch();
        if (empty($existingRow)) {
            $row = $this->addTimeFieldsToRow($row, $tableName);
            $connection->insert($tableName, $row);
            $uid = (int)$connection->lastInsertId($tableName);
        } else {
            $this->log->addNote(
                'record already exists, skipped entry (' . $this->buildWhereClauseFromPropertiesArray($row) . ')'
            );
            if (!empty($existingRow['uid'])) {
                $uid = (int)$existingRow['uid'];
            }
        }
        return $uid;
    }

    /**
     * [
     *      'pid' => 2,
     *      'title' => 'test'
     * ]
     *
     *      =>
     *
     * 'pid=2 and title="test"'
     *
     * @param array $properties
     * @param array $excludeFields
     * @return string
     */
    protected function buildWhereClauseFromPropertiesArray(
        array $properties,
        array $excludeFields = [
            'tstamp',
            'crdate'
        ]
    ) {
        $whereString = '';
        foreach ($properties as $propertyName => $propertyValue) {
            if (!in_array($propertyName, $excludeFields)) {
                if (!empty($whereString)) {
                    $whereString .= ' and ';
                }
                if (empty($propertyValue)) {
                    $whereString .= '(' . $propertyName . '=\'\' or ' . $propertyName . ' is null)';
                } else {
                    $whereString .= $propertyName . '=';
                    if (!is_numeric($propertyValue)) {
                        $propertyValue = '"' . $propertyValue . '"';
                    }
                    $whereString .= $propertyValue;
                }
            }
        }
        return $whereString;
    }

    /**
     * Add tstamp and crdate fields and properties to an existing row if the table has such fields
     *
     * @param array $row
     * @param string $tableName
     * @return array
     * @throws DBALException
     */
    protected function addTimeFieldsToRow(array $row, string $tableName): array
    {
        if (empty($row['tstamp']) && DatabaseUtility::isFieldExistingInTable('tstamp', $tableName)) {
            $row['tstamp'] = time();
        }
        if (empty($row['crdate']) && DatabaseUtility::isFieldExistingInTable('crdate', $tableName)) {
            $row['crdate'] = time();
        }
        return $row;
    }

    /**
     * @param string $tableName
     * @return string
     * @throws DBALException
     */
    protected function getWhereClause(string $tableName): string
    {
        $whereClause = 'deleted=0';
        $whereClause = $this->getWhereClauseForLimitToRecord($whereClause, $tableName);
        $whereClause = $this->getWhereClauseForLimitToPage($whereClause, $tableName);
        if ($this->enforce === false && DatabaseUtility::isFieldExistingInTable('_migrated', $tableName)) {
            $whereClause .= ' and _migrated = 0';
        }
        return $whereClause;
    }

    /**
     * @param string $whereClause
     * @param string $tableName
     * @return string
     */
    protected function getWhereClauseForLimitToRecord(string $whereClause, string $tableName): string
    {
        if ($this->getConfiguration('limitToRecord') !== null) {
            if (is_numeric($this->getConfiguration('limitToRecord')) && $this->getConfiguration('limitToRecord') > 0) {
                $whereClause .= ' and uid=' . (int)$this->getConfiguration('limitToRecord');
            }
            if (!is_numeric($this->getConfiguration('limitToRecord'))) {
                $parts = GeneralUtility::trimExplode(':', $this->getConfiguration('limitToRecord'), true);
                if ($tableName === $parts[0]) {
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
        }
        return $whereClause;
    }

    /**
     * @param string $whereClause
     * @param string $tableName
     * @return string
     */
    protected function getWhereClauseForLimitToPage(string $whereClause, string $tableName): string
    {
        if ($this->getConfiguration('limitToPage') !== null) {
            $field = 'pid';
            if ($tableName === 'pages') {
                $field = 'uid';
            }
            if ($this->getConfiguration('limitToPage') > 0) {
                if ($this->getConfiguration('recursive') === true) {
                    $whereClause .= ' and ' . $field . ' in (';
                    $whereClause .=
                        $this->getTreeBranchesFromStartingPoint((int)$this->getConfiguration('limitToPage')) . ')';
                    return $whereClause;
                } else {
                    $whereClause .= ' and ' . $field . '=' . (int)$this->getConfiguration('limitToPage');
                    return $whereClause;
                }
            }
        }
        return $whereClause;
    }

    /**
     * @param int $startPage
     * @return string
     */
    protected function getTreeBranchesFromStartingPoint(int $startPage): string
    {
        $queryGenerator = ObjectUtility::getObjectManager()->get(QueryGenerator::class);
        return (string)$queryGenerator->getTreeList($startPage, 99, 0, 1);
    }

    /**
     * @param string $property
     * @return mixed
     */
    protected function getConfiguration(string $property)
    {
        if (array_key_exists($property, $this->configuration['configuration'])) {
            return $this->configuration['configuration'][$property];
        } else {
            throw new \LogicException('Configuration key does not exist', 1568275508);
        }
    }
}
