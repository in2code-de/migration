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
    public function persistRecord(array $properties, string $tableName)
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
            $this->log->addMessage('Record could be updated', $properties, $tableName);
        }
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
                    $whereClause .= $this->getTreeBranchesFromStartingPoint($this->getConfiguration('limitToPage')) . ')';
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
        return $queryGenerator->getTreeList($startPage, 20, 0, 1);
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
