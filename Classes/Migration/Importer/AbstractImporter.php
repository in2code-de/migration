<?php

declare(strict_types=1);
namespace In2code\Migration\Migration\Importer;

use Doctrine\DBAL\Driver\Exception as ExceptionDbalDriver;
use Doctrine\DBAL\Exception as ExceptionDbal;
use In2code\Migration\Exception\ConfigurationException;
use In2code\Migration\Migration\Log\Log;
use In2code\Migration\Migration\PropertyHelpers\PropertyHelperInterface;
use In2code\Migration\Migration\Repository\GeneralRepository;
use In2code\Migration\Utility\DatabaseUtility;
use In2code\Migration\Utility\StringUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractImporter
{
    /**
     * Table where to run the migration
     *
     * @var string
     */
    protected string $tableName = '';

    /**
     * Table name from migrate to
     *
     * @var string
     */
    protected string $tableNameOld = '';

    /**
     * Default fields
     *
     * @var array
     */
    protected array $mappingDefault = [
        'uid' => 'uid',
        'pid' => 'pid',
    ];

    /**
     * Not listed fields will be ignored completely!
     *      oldfieldname => newfieldname
     *
     * @var array
     */
    protected array $mapping = [];

    /**
     * Set some hard values (will be parsed with fluid engine).
     * So you can use {properties} for new and {propertiesOld} for all old properties.
     *  e.g.
     *      [
     *          'title' => 'New title',
     *          'description' => 'Nice content with {properties.title}',
     *          'bodytext' => '<f:if condition="{propertiesOld.field1}"><f:then>{propertiesOld.field1}</f:then><f:else>{propertiesOld.field2}</f:else></f:if>'
     *      ]
     *
     * @var array
     */
    protected array $values = [];

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
    protected array $propertyHelpers = [];

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
    protected array $sql = [
        'start' => [],
        'end' => [],
    ];

    /**
     * Enforce to also get already migrated records
     *
     * @var bool
     */
    protected bool $enforce = false;

    /**
     * Keep uid when importing to new table
     *
     * @var bool
     */
    protected bool $keepIdentifiers = true;

    /**
     * Truncate table before import
     *
     * @var bool
     */
    protected bool $truncate = true;

    /**
     * Filter selection of old records like "and pid > 0" (to prevent elements in a workflow e.g.)
     *
     * @var string
     */
    protected string $additionalWhere = '';

    /**
     * Group selection of old records like "url"
     *
     * @var string
     */
    protected string $groupBy = '';

    /**
     * Overwrite default order by definition
     *
     * @var string
     */
    protected string $orderBy = 'pid,uid';

    /**
     * Complete configuration from configuration file
     *
     * @var array
     */
    protected array $configuration = [];

    protected ?Log $log = null;

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
        $this->checkProperties();
        $this->log = GeneralUtility::makeInstance(Log::class);
        $this->truncateTable();
    }

    /**
     * @return void
     * @throws ConfigurationException
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    public function start(): void
    {
        $this->executeSqlStart();
        $generalRepository = GeneralUtility::makeInstance(
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
            $properties = $this->createPropertiesFromValues($properties, $propertiesOld);
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
     * @throws ExceptionDbalDriver
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

    protected function createPropertiesFromValues(array $properties, array $propertiesOld): array
    {
        foreach ($this->values as $propertyName => $propertyValue) {
            $variables = [
                'properties' => $properties,
                'propertiesOld' => $propertiesOld,
                'tableName' => $this->tableName,
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
                $helperClass = GeneralUtility::makeInstance(
                    $helperConfiguration['className'],
                    $properties,
                    $propertiesOld,
                    $propertyName,
                    $this->tableName,
                    (array)$helperConfiguration['configuration'],
                    $this->configuration['configuration'] ?? []
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
     * @throws ExceptionDbalDriver
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
     * @return array|string[]
     * @throws ExceptionDbalDriver
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
            'cruser_id' => 'cruser_id',
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
     * @throws ExceptionDbal
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
     * @throws ExceptionDbal
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
