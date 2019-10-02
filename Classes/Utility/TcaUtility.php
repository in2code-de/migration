<?php
declare(strict_types=1);
namespace In2code\Migration\Utility;

use Doctrine\DBAL\DBALException;

/**
 * Class TcaUtility
 */
class TcaUtility
{
    /**
     * @param string $fieldName
     * @param string $tableName
     * @return array
     */
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
     * @throws DBALException
     */
    public static function getTableNamesToExport(array $excludedTables = []): array
    {
        $tables = self::getAllTableNames();
        $excludedTables = ['pages'] + $excludedTables;
        foreach ($tables as $key => $table) {
            if (in_array($table, $excludedTables) || DatabaseUtility::isFieldExistingInTable('pid', $table) === false) {
                unset($tables[$key]);
            }
        }
        return $tables;
    }

    /**
     * @return array
     */
    protected static function getAllTableNames(): array
    {
        return array_keys($GLOBALS['TCA']);
    }
}
