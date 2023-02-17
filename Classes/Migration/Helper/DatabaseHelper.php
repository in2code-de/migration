<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\Helper;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use In2code\Migration\Migration\Log\Log;
use In2code\Migration\Utility\DatabaseUtility;
use In2code\Migration\Utility\StringUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DatabaseHelper implements SingletonInterface
{
    /**
     * @var Log
     */
    protected $log = null;

    public function __construct()
    {
        $this->log = GeneralUtility::makeInstance(Log::class);
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
        $sql = 'select * from ' . $tableName . ' where '
            . $this->buildWhereClauseFromPropertiesArray($row, ['tstamp', 'crdate', 'uid']);
        $existingRow = $connection->executeQuery($sql)->fetch();
        if ($existingRow === false) {
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
     * Get rootline of a pageidentifier (same pid and every parent pid up to 0)
     *
     * @param int $pageIdentifier
     * @param int[] $rootline
     * @return int[] e.g. [1000,100,10,1]
     * @throws Exception
     */
    public function getRootline(int $pageIdentifier, array $rootline = []): array
    {
        $rootline[] = $pageIdentifier;
        if ($pageIdentifier > 0) {
            $parentPageIdentifier = $this->getParentPageIdentifier($pageIdentifier);
            if ($parentPageIdentifier > 0) {
                $rootline = $this->getRootline($parentPageIdentifier, $rootline);
            }
        }
        return $rootline;
    }

    /**
     * [
     *      'pid' => 2,
     *      'title' => 'test'
     * ]
     *
     *      =>
     *
     * '`pid`=2 and `title`="test"'
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
    ): string {
        $whereString = '';
        foreach ($properties as $propertyName => $propertyValue) {
            if (!in_array($propertyName, $excludeFields)) {
                if (!empty($whereString)) {
                    $whereString .= ' and ';
                }
                if (empty($propertyValue)) {
                    $whereString .= '(`' . $propertyName . '`=\'\' or `' . $propertyName . '` is null)';
                } else {
                    $whereString .= '`' . $propertyName . '`=';
                    if (is_numeric($propertyValue) === false) {
                        $propertyValue = StringUtility::quoteString($propertyValue);
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
     * @param int $pageIdentifier
     * @return int
     * @throws Exception
     */
    protected function getParentPageIdentifier(int $pageIdentifier): int
    {
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable('pages');
        return (int)$queryBuilder
            ->select('pid')
            ->from('pages')
            ->where('uid=' . (int)$pageIdentifier)
            ->execute()
            ->fetchOne();
    }
}
