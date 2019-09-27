<?php
declare(strict_types=1);
namespace In2code\Migration\Utility;

use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DatabaseUtility
 */
class DatabaseUtility
{
    /**
     * Cache existing fields
     *
     * @var array
     */
    protected static $fieldsExisting = [];

    /**
     * @param string $tableName
     * @param bool $removeRestrictions
     * @return QueryBuilder
     */
    public static function getQueryBuilderForTable(string $tableName, bool $removeRestrictions = false): QueryBuilder
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        if ($removeRestrictions === true) {
            $queryBuilder->getRestrictions()->removeAll();
        }
        return $queryBuilder;
    }

    /**
     * @param string $tableName
     * @return Connection
     */
    public static function getConnectionForTable(string $tableName): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);
    }

    /**
     * @param string $tableName
     * @return bool
     * @throws DBALException
     */
    public static function isTableExisting(string $tableName): bool
    {
        $existing = false;
        $connection = self::getConnectionForTable($tableName);
        $queryResult = $connection->query('show tables;')->fetchAll();
        foreach ($queryResult as $tableProperties) {
            if (in_array($tableName, array_values($tableProperties))) {
                $existing = true;
                break;
            }
        }
        return $existing;
    }

    /**
     * @param string $fieldName
     * @param string $tableName
     * @return bool
     * @throws DBALException
     */
    public static function isFieldExistingInTable(string $fieldName, string $tableName): bool
    {
        $found = false;
        if (isset(self::$fieldsExisting[$tableName][$fieldName]) === false) {
            $connection = self::getConnectionForTable($tableName);
            $queryResult = $connection->query('describe ' . $tableName . ';')->fetchAll();
            foreach ($queryResult as $fieldProperties) {
                if ($fieldProperties['Field'] === $fieldName) {
                    $found = true;
                    break;
                }
            }
            self::$fieldsExisting[$tableName][$fieldName] = $found;
        } else {
            $found = self::$fieldsExisting[$tableName][$fieldName];
        }
        return $found;
    }

    /**
     * @param int $storage
     * @param string $identifier
     * @return string
     * @throws DBALException
     */
    public static function getFilePathAndNameByStorageAndIdentifier(int $storage, string $identifier): string
    {
        return self::getPathFromStorage($storage) . ltrim($identifier, '/');
    }

    /**
     * @param int $storage
     * @return string
     * @throws DBALException
     */
    protected static function getPathFromStorage(int $storage): string
    {
        $sql = 'select ExtractValue(configuration, \'//T3FlexForms/data/sheet[@index="sDEF"]/language/field[@index="basePath"]/value\') path from sys_file_storage where uid = "' . $storage . '"';
        $connection = self::getConnectionForTable('sys_file_storage');
        $path = $connection->executeQuery($sql)->fetchColumn(0);
        return (string)$path;
    }
}
