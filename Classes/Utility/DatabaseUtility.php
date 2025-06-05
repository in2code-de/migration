<?php

declare(strict_types=1);
namespace In2code\Migration\Utility;

use Doctrine\DBAL\Driver\Exception as ExceptionDbalDriver;
use Doctrine\DBAL\Exception as ExceptionDbal;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class DatabaseUtility
{
    protected static array $fieldsExisting = [];

    public static function getQueryBuilderForTable(string $tableName, bool $removeRestrictions = false): QueryBuilder
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        if ($removeRestrictions === true) {
            $queryBuilder->getRestrictions()->removeAll();
        }
        return $queryBuilder;
    }

    public static function getConnectionForTable(string $tableName): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);
    }

    /**
     * @param string $tableName
     * @return bool
     * @throws ExceptionDbal
     */
    public static function isTableExisting(string $tableName): bool
    {
        $existing = false;
        $connection = self::getConnectionForTable($tableName);
        $queryResult = $connection->executeQuery('show tables;')->fetchAllAssociative();
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
     * @throws ExceptionDbal
     */
    public static function isFieldExistingInTable(string $fieldName, string $tableName): bool
    {
        $found = false;
        if (isset(self::$fieldsExisting[$tableName][$fieldName]) === false) {
            $connection = self::getConnectionForTable($tableName);
            $queryResult = $connection->executeQuery('describe ' . $tableName . ';')->fetchAllAssociative();
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
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    public static function getFilePathAndNameByStorageAndIdentifier(int $storage, string $identifier): string
    {
        return self::getPathFromStorage($storage) . ltrim($identifier, '/');
    }

    /**
     * Check if string is a specified identifier
     * like "tt_content_123" or "pages_123"
     *
     * @param string $identifier
     * @return bool
     * @throws ExceptionDbalDriver
     */
    public static function isSpecifiedIdentifier(string $identifier): bool
    {
        return self::getUidFromSpecifiedIdentifier($identifier) > 0
            && self::isTableExisting(self::getTableFromSpecifiedIdentifier($identifier));
    }

    /**
     * "tt_content_123" => 123
     *
     * @param string $identifier
     * @return int
     */
    public static function getUidFromSpecifiedIdentifier(string $identifier): int
    {
        if (stristr($identifier, '_') !== false) {
            $parts = explode('_', $identifier);
            $uid = end($parts);
            if (MathUtility::canBeInterpretedAsInteger($uid)) {
                return (int)$uid;
            }
        }
        return 0;
    }

    /**
     * "tt_content_123" => "tt_content"
     *
     * @param string $identifier
     * @return string
     */
    public static function getTableFromSpecifiedIdentifier(string $identifier): string
    {
        if (stristr($identifier, '_') !== false) {
            $parts = explode('_', $identifier);
            unset($parts[count($parts) - 1]);
            return implode('_', $parts);
        }
        return '';
    }

    /**
     * @param int $storage
     * @return string
     * @throws ExceptionDbal
     */
    protected static function getPathFromStorage(int $storage): string
    {
        if ($storage === 0) {
            return '';
        }
        $sql = 'select ExtractValue(configuration, \'//T3FlexForms/data/sheet[@index="sDEF"]/language/field[@index="basePath"]/value\') path from sys_file_storage where uid = "' . $storage . '"';
        $connection = self::getConnectionForTable('sys_file_storage');
        $path = $connection->executeQuery($sql)->fetchOne();
        return rtrim((string)$path, '/') . '/';
    }
}
