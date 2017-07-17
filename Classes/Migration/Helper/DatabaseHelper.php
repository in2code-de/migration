<?php
namespace In2code\In2template\Migration\Helper;

use In2code\In2template\Migration\Service\Log;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class DatabaseHelper
 */
class DatabaseHelper
{

    /**
     * @var Log|null
     */
    protected $log = null;

    /**
     * Cache existing fields
     *  [
     *      'table' => ['uid', 'pid']
     *  ]
     *
     * @var array
     */
    protected $existingFields = [];

    /**
     * FileHelper constructor.
     */
    public function __construct()
    {
        $this->log = $this->getObjectManager()->get(Log::class);
    }

    /**
     * @param string $tableName
     * @return bool
     */
    public function isTableExisting(string $tableName): bool
    {
        if (array_key_exists($tableName, $this->existingFields)) {
            return true;
        } else {
            $tables = $this->getDatabase()->admin_get_tables();
            if (array_key_exists($tableName, $tables)) {
                $fieldProperties = $this->getDatabase()->admin_get_fields($tableName);
                $this->cacheExistingFields($fieldProperties, $tableName);
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $tableName
     * @param string $fieldName
     * @return bool
     */
    public function isFieldExistingInTable(string $tableName, string $fieldName): bool
    {
        if (array_key_exists($tableName, $this->existingFields)) {
            return in_array($fieldName, $this->existingFields[$tableName]);
        } else {
            $fieldProperties = $this->getDatabase()->admin_get_fields($tableName);
            $this->cacheExistingFields($fieldProperties, $tableName);
            return array_key_exists($fieldName, $fieldProperties);
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
     */
    public function createRecord(string $tableName, array $row): int
    {
        $uid = 0;
        $existingRow = $this->getDatabase()->exec_SELECTgetSingleRow(
            '*',
            $tableName,
            $this->buildWhereClauseFromPropertiesArray($row, ['tstamp', 'crdate', 'uid'])
        );
        if (empty($existingRow)) {
            $row = $this->addTimeFieldsToRow($row, $tableName);
            $this->getDatabase()->exec_INSERTquery($tableName, $row);
            $uid = (int)$this->getDatabase()->sql_insert_id();
        } else {
            $this->log->addNote(
                'record already exists, skipped entry (' . $this->buildWhereClauseFromPropertiesArray($row) . ')'
            );
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
                        $propertyValue = $this->getDatabase()->fullQuoteStr($propertyValue, '');
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
     */
    protected function addTimeFieldsToRow(array $row, string $tableName): array
    {
        if (empty($row['tstamp']) && $this->isFieldExistingInTable($tableName, 'tstamp')) {
            $row['tstamp'] = time();
        }
        if (empty($row['crdate']) && $this->isFieldExistingInTable($tableName, 'tstamp')) {
            $row['crdate'] = time();
        }
        return $row;
    }

    /**
     * @param array $fieldProperties
     * @param string $tableName
     * @return void
     */
    protected function cacheExistingFields(array $fieldProperties, string $tableName)
    {
        $this->existingFields[$tableName] = array_keys($fieldProperties);
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
