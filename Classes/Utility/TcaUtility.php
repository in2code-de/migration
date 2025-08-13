<?php

declare(strict_types=1);
namespace In2code\Migration\Utility;

use Doctrine\DBAL\Driver\Exception as ExceptionDbalDriver;
use Doctrine\DBAL\Exception as ExceptionDbal;

class TcaUtility
{
    public static function getTcaOfField(string $fieldName, string $tableName): array
    {
        if (empty($GLOBALS['TCA'][$tableName]['columns'][$fieldName])) {
            throw new \LogicException('No TCA to field ' . $fieldName . ' and table ' . $tableName, 1570026984);
        }
        return (array)$GLOBALS['TCA'][$tableName]['columns'][$fieldName];
    }

    /**
     * @param array $excludedTables
     * @return array
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    public static function getTableNamesToExport(array $excludedTables = []): array
    {
        $tables = self::getAllTableNames();
        $excludedTables = array_merge(['pages'], $excludedTables);
        foreach ($tables as $key => $table) {
            if (in_array($table, $excludedTables) || DatabaseUtility::isFieldExistingInTable('pid', $table) === false) {
                unset($tables[$key]);
            }
        }
        return $tables;
    }

    public static function getRootLevelConfiguration(string $tableName): int
    {
        return (int)($GLOBALS['TCA'][$tableName]['ctrl']['rootLevel'] ?? 0);
    }

    protected static function getAllTableNames(): array
    {
        return array_keys($GLOBALS['TCA']);
    }
}
