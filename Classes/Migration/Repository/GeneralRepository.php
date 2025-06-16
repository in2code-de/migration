<?php

declare(strict_types=1);
namespace In2code\Migration\Migration\Repository;

use Doctrine\DBAL\Driver\Exception as ExceptionDbalDriver;
use Doctrine\DBAL\Exception as ExceptionDbal;
use In2code\Migration\Migration\Log\Log;
use In2code\Migration\Service\TreeService;
use In2code\Migration\Utility\DatabaseUtility;
use LogicException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GeneralRepository
{
    protected array $configuration = [];

    protected bool $enforce = false;

    protected ?Log $log = null;
    protected ?Queue $queue = null;

    public function __construct(array $configuration, bool $enforce)
    {
        $this->configuration = $configuration;
        $this->enforce = $enforce;
        $this->log = GeneralUtility::makeInstance(Log::class);
        $this->queue = GeneralUtility::makeInstance(Queue::class);
    }

    /**
     * @param string $tableName
     * @param string $additionalWhere add additional where like "and pid>0"
     * @param string $groupby add a groupby definition
     * @param string $orderby overwrite order by "pid,uid"
     * @return array
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    public function getRecords(
        string $tableName,
        string $additionalWhere = '',
        string $groupby = '',
        string $orderby = ''
    ): array {
        $connection = DatabaseUtility::getConnectionForTable($tableName);
        /** @noinspection SqlNoDataSourceInspection */
        $query = 'select * from ' . $tableName . ' where ' . $this->getWhereClause($tableName, $additionalWhere);
        if ($groupby !== '') {
            $query .= ' group by ' . $groupby;
        }
        if ($orderby !== '') {
            $query .= ' order by ' . $orderby;
        }
        return $connection->executeQuery($query)->fetchAllAssociative();
    }

    public function updateRecord(array $properties, string $tableName): void
    {
        if (array_key_exists('uid', $properties) === false) {
            throw new LogicException(
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

    public function insertRecord(array $properties, string $tableName): void
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
     * @param string $tableName
     * @param string $additionalWhere
     * @return string
     * @throws ExceptionDbalDriver
     */
    protected function getWhereClause(string $tableName, string $additionalWhere): string
    {
        $whereClause = '1';
        if (DatabaseUtility::isFieldExistingInTable('deleted', $tableName)) {
            $whereClause = 'deleted=0';
        }
        $whereClause = $this->getWhereClauseForLimitToRecord($whereClause, $tableName);
        $whereClause = $this->getWhereClauseForLimitToPage($whereClause, $tableName);
        if ($this->enforce === false && DatabaseUtility::isFieldExistingInTable('_migrated', $tableName)) {
            $whereClause .= ' and _migrated = 0';
        }
        if ($additionalWhere !== '') {
            $whereClause .= ' ' . $additionalWhere;
        }
        return $whereClause;
    }

    protected function getWhereClauseForLimitToRecord(string $whereClause, string $tableName): string
    {
        if ($this->getConfiguration('limitToRecord') !== null) {
            if (is_numeric($this->getConfiguration('limitToRecord')) && $this->getConfiguration('limitToRecord') > 0) {
                $whereClause .= ' and uid=' . (int)$this->getConfiguration('limitToRecord');
            }
            if (is_numeric($this->getConfiguration('limitToRecord')) === false) {
                $parts = GeneralUtility::trimExplode(':', $this->getConfiguration('limitToRecord'), true);
                if ($tableName === $parts[0]) {
                    if (is_numeric($parts[1]) && $parts[1] > 0) {
                        $whereClause .= ' and uid=' . (int)$parts[1];
                        return $whereClause;
                    }
                } else {
                    $whereClause .= ' and 1=2';
                }
                return $whereClause;
            }
        }
        return $whereClause;
    }

    /**
     * @param string $whereClause
     * @param string $tableName
     * @return string
     * @throws ExceptionDbalDriver
     */
    protected function getWhereClauseForLimitToPage(string $whereClause, string $tableName): string
    {
        $treeService = GeneralUtility::makeInstance(TreeService::class);
        if ($this->getConfiguration('limitToPage') !== null) {
            $field = 'pid';
            if ($tableName === 'pages') {
                $field = 'uid';
            }
            if ($this->getConfiguration('limitToPage') > 0) {
                if ($this->getConfiguration('recursive') === true) {
                    $whereClause .= ' and ' . $field . ' in (';
                    $whereClause .= implode(',', $treeService->getAllSubpageIdentifiers((int)$this->getConfiguration('limitToPage')));
                    $whereClause .= ')';
                } else {
                    $whereClause .= ' and ' . $field . '=' . (int)$this->getConfiguration('limitToPage');
                }
                return $whereClause;
            }
        }
        return $whereClause;
    }

    protected function getConfiguration(string $property)
    {
        if (array_key_exists($property, $this->configuration['configuration'])) {
            return $this->configuration['configuration'][$property];
        }
        throw new LogicException('Configuration key does not exist', 1568275508);
    }
}
