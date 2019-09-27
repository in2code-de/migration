<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\Importer;

use Doctrine\DBAL\DBALException;
use In2code\Migration\Migration\Exception\ConfigurationException;
use In2code\Migration\Migration\Log\Log;
use In2code\Migration\Migration\PropertyHelpers\PropertyHelperInterface;
use In2code\Migration\Migration\Repository\GeneralRepository;
use In2code\Migration\Utility\DatabaseUtility;
use In2code\Migration\Utility\ObjectUtility;
use In2code\Migration\Utility\StringUtility;

/**
 * Class AbstractImporter
 */
abstract class AbstractImporter
{
    /**
     * Table where to run the migration
     *
     * @var string
     */
    protected $tableName = '';

    /**
     * Table name from migrate to
     *
     * @var string
     */
    protected $tableNameOld = '';

    /**
     * Default fields
     *
     * @var array
     */
    protected $mappingDefault = [
        'uid' => 'uid',
        'pid' => 'pid'
    ];

    /**
     * Not listed fields will be ignored completely!
     *      oldfieldname => newfieldname
     *
     * @var array
     */
    protected $mapping = [];

    /**
     * Set some hard values (will be parsed with fluid engine)
     *  e.g.
     *      [
     *          'title' => 'New title',
     *          'description' => 'Nice content with {properties.title}'
     *      ]
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
     * Define some sql statements that should be executed at the beginning or at the end of this import
     *  e.g.:
     *  [
     *      'start' => [
     *          'update sys_file_reference set fieldname="assets" where fieldname="image" and tablenames="tt_content"'
     *      ]
     *  ]
     *
     * @var array
     */
    protected $sql = [
        'start' => [],
        'end' => []
    ];

    /**
     * Enforce to also get already migrated records
     *
     * @var bool
     */
    protected $enforce = false;

    /**
     * Keep uid when importing to new table
     *
     * @var bool
     */
    protected $keepIdentifiers = true;

    /**
     * Truncate table before import
     *
     * @var bool
     */
    protected $truncate = true;

    /**
     * Filter selection of old records like "and pid > 0" (to prevent elements in a workflow e.g.)
     *
     * @var string
     */
    protected $additionalWhere = '';

    /**
     * Group selection of old records like "url"
     *
     * @var string
     */
    protected $groupBy = '';

    /**
     * Overwrite default order by definition
     *
     * @var string
     */
    protected $orderBy = 'pid,uid';

    /**
     * Complete configuration from configuration file
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * @var Log
     */
    protected $log = null;

    /**
     * AbstractMigrator constructor.
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
        $this->checkProperties();
        $this->log = ObjectUtility::getObjectManager()->get(Log::class);
        $this->truncateTable();
    }

    /**
     * @return void
     * @throws ConfigurationException
     * @throws DBALException
     */
    public function start(): void
    {
        $this->executeSqlStart();
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $generalRepository = ObjectUtility::getObjectManager()->get(
            GeneralRepository::class,
            $this->configuration,
            $this->enforce
        );
        $records = $generalRepository->getRecords(
            $this->tableNameOld,
            $this->additionalWhere,
            $this->groupBy,
            $this->orderBy
        );
        foreach ($records as $propertiesOld) {
            $this->log->addNote(
                'Start importing ' . $this->tableName
                . ' (uid' . $propertiesOld['uid'] . '/pid' . $propertiesOld['pid'] . ') ...'
            );
            $properties = $this->createPropertiesFromMapping($propertiesOld);
            $properties = $this->createPropertiesFromValues($properties);
            $properties = $this->createPropertiesFromPropertyHelpers($properties, $propertiesOld);
            $properties = $this->genericChanges($properties);
            $generalRepository->insertRecord($properties, $this->tableName);
        }
        $this->executeSqlEnd();
        $this->finalMessage($records);
    }

    /**
     * Build row from $this->mapping and $this->mappingDefault
     *
     * @param array $oldProperties
     * @return array
     * @throws DBALException
     */
    protected function createPropertiesFromMapping(array $oldProperties): array
    {
        $mapping = $this->mapping + $this->getMappingDefault();
        $newRow = [];
        foreach ($mapping as $oldPropertyName => $newPropertyName) {
            $newProperty = $oldProperties[$oldPropertyName];
            $newRow[$newPropertyName] = $newProperty;
        }
        return $newRow;
    }

    /**
     * @param array $properties
     * @return array
     */
    protected function createPropertiesFromValues(array $properties): array
    {
        foreach ($this->values as $propertyName => $propertyValue) {
            $variables = [
                'properties' => $properties,
                'tableName' => $this->tableName
            ];
            $properties[$propertyName] = StringUtility::parseString($propertyValue, $variables);
        }
        return $properties;
    }

    /**
     * @param array $properties Modified properties
     * @param array $propertiesOld Original properties (old record properties)
     * @return array
     * @throws ConfigurationException
     */
    protected function createPropertiesFromPropertyHelpers(array $properties, array $propertiesOld): array
    {
        foreach ($this->propertyHelpers as $propertyName => $helperConfigurations) {
            foreach ($helperConfigurations as $key => $helperConfiguration) {
                if (is_int($key) === false) {
                    throw new ConfigurationException('Misconfiguration of your importer class', 1569574630);
                }
                if (class_exists($helperConfiguration['className']) === false) {
                    throw new ConfigurationException(
                        'Class ' . $helperConfiguration['className'] . ' does not exist',
                        1569574672
                    );
                }
                if (is_subclass_of($helperConfiguration['className'], PropertyHelperInterface::class) === false) {
                    throw new ConfigurationException(
                        'Class does not implement ' . PropertyHelperInterface::class,
                        1569574677
                    );
                }
                /** @var PropertyHelperInterface $helperClass */
                $helperClass = ObjectUtility::getObjectManager()->get(
                    $helperConfiguration['className'],
                    $properties,
                    $propertiesOld,
                    $propertyName,
                    $this->tableName,
                    (array)$helperConfiguration['configuration']
                );
                $helperClass->initialize();
                $properties = $helperClass->returnRecord();
            }
        }
        return $properties;
    }

    /**
     * @param array $properties
     * @return array
     * @throws DBALException
     */
    protected function genericChanges(array $properties): array
    {
        if (DatabaseUtility::isFieldExistingInTable('_migrated', $this->tableName)) {
            $properties['_migrated'] = 1;
        }
        if (DatabaseUtility::isFieldExistingInTable('_migrated_uid', $this->tableName)) {
            $properties['_migrated_uid'] = $properties['uid'];
        }
        if (DatabaseUtility::isFieldExistingInTable('_migrated_table', $this->tableName)) {
            $properties['_migrated_table'] = $this->tableNameOld;
        }
        if ($this->keepIdentifiers === false) {
            unset($properties['uid']);
        }
        return $properties;
    }

    /**
     * @return void
     */
    protected function checkProperties(): void
    {
        if ($this->tableName === '') {
            throw new \LogicException('$tableName not given', 1568276662);
        }
        if ($this->tableNameOld === '') {
            throw new \LogicException('$tableNameOld not given', 1568293207);
        }
        if ($this->mapping === []) {
            throw new \LogicException('$mapping not defined', 1568293350);
        }
    }

    /**
     * @return array
     * @throws DBALException
     */
    protected function getMappingDefault(): array
    {
        $mappingDefault = $this->mappingDefault;
        $additionalDefaults = [
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'hidden' => 'hidden',
            'disable' => 'disable',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'cruser_id' => 'cruser_id'
        ];
        foreach ($additionalDefaults as $key => $value) {
            if (
                DatabaseUtility::isFieldExistingInTable($value, $this->tableNameOld) &&
                DatabaseUtility::isFieldExistingInTable($value, $this->tableName)
            ) {
                $mappingDefault += [$key => $value];
            }
        }
        return $mappingDefault;
    }

    /**
     * @param array $records
     * @return void
     */
    protected function finalMessage(array $records)
    {
        if ($this->configuration['configuration']['dryrun'] === false) {
            $message = count($records) . ' records successfully imported to ' . $this->tableName;
        } else {
            $message = count($records) . ' records could be imported without dryrun to ' . $this->tableName;
        }
        $this->log->addMessage($message);
    }

    /**
     * Table will be truncated if
     *      - we're not running in drymode (dryrun)
     *      - truncate is activated (default)
     *
     * @return void
     */
    protected function truncateTable()
    {
        if ($this->configuration['configuration']['dryrun'] === false && $this->truncate === true) {
            DatabaseUtility::getConnectionForTable($this->tableName)->truncate($this->tableName);
            $this->log->addMessage('Table ' . $this->tableName . ' truncated before import');
        }
    }

    /**
     * @return void
     * @throws DBALException
     */
    protected function executeSqlStart(): void
    {
        if (!empty($this->sql['start'])) {
            foreach ($this->sql['start'] as $sql) {
                $connection = DatabaseUtility::getConnectionForTable($this->tableName);
                $connection->executeQuery($sql);
            }
        }
    }

    /**
     * @return void
     * @throws DBALException
     */
    protected function executeSqlEnd(): void
    {
        if (!empty($this->sql['end'])) {
            foreach ($this->sql['end'] as $sql) {
                $connection = DatabaseUtility::getConnectionForTable($this->tableName);
                $connection->executeQuery($sql);
            }
        }
    }
}
