<?php

declare(strict_types=1);
namespace In2code\Migration\Migration\Migrator;

use Doctrine\DBAL\Driver\Exception as ExceptionDbalDriver;
use Doctrine\DBAL\Exception as ExceptionDbal;
use In2code\Migration\Exception\ConfigurationException;
use In2code\Migration\Migration\Log\Log;
use In2code\Migration\Migration\PropertyHelpers\PropertyHelperInterface;
use In2code\Migration\Migration\Repository\GeneralRepository;
use In2code\Migration\Utility\DatabaseUtility;
use In2code\Migration\Utility\StringUtility;
use LogicException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractMigrator
{
    /**
     * Table where to run the migration
     *
     * @var string
     */
    protected string $tableName = '';

    /**
     * Set some hard values (will be parsed with fluid engine).
     * So you can use {properties} for given properties.
     *  e.g.
     *      [
     *          'title' => 'New title',
     *          'description' => 'Nice content with {properties.title}'
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
     * Define some sql statements that should be executed at the beginning or at the end of this migration
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
    }

    /**
     * @return void
     * @throws ConfigurationException
     * @throws ExceptionDbal
     * @throws ExceptionDbalDriver
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
            $this->tableName,
            $this->additionalWhere,
            $this->groupBy,
            $this->orderBy
        );
        foreach ($records as $propertiesOriginal) {
            $note = 'Start migrating record in ' . $this->tableName;
            if (array_key_exists('uid', $propertiesOriginal) && array_key_exists('pid', $propertiesOriginal)) {
                $note .= ' (uid' . $propertiesOriginal['uid'] . '/pid' . $propertiesOriginal['pid'] . ') ...';
            }
            $this->log->addNote($note);
            $properties = $this->manipulatePropertiesWithValues($propertiesOriginal);
            $properties = $this->manipulatePropertiesWithPropertyHelpers($properties, $propertiesOriginal);
            $properties = $this->genericChanges($properties);
            $generalRepository->updateRecord($properties, $this->tableName);
        }
        $this->executeSqlEnd();
        $this->finalMessage($records);
    }

    protected function manipulatePropertiesWithValues(array $properties): array
    {
        foreach ($this->values as $propertyName => $propertyValue) {
            if (array_key_exists($propertyName, $properties) === false) {
                throw new LogicException('Property ' . $propertyName . ' does not exist', 1568278136);
            }
            $variables = [
                'properties' => $properties,
                'tableName' => $this->tableName,
            ];
            $properties[$propertyName] = StringUtility::parseString((string)$propertyValue, $variables);
        }
        return $properties;
    }

    /**
     * @param array $properties
     * @param array $propertiesOld Original properties (not modified)
     * @return array
     * @throws ConfigurationException
     */
    protected function manipulatePropertiesWithPropertyHelpers(array $properties, array $propertiesOld): array
    {
        foreach ($this->propertyHelpers as $propertyName => $helperConfigurations) {
            foreach ($helperConfigurations as $key => $helperConfiguration) {
                if (is_int($key) === false) {
                    throw new ConfigurationException('Misconfiguration of your migrator class', 1569574630);
                }
                if (class_exists($helperConfiguration['className']) === false) {
                    throw new ConfigurationException(
                        'Class ' . $helperConfiguration['className'] . ' does not exist',
                        1568285755
                    );
                }
                if (is_subclass_of($helperConfiguration['className'], PropertyHelperInterface::class) === false) {
                    throw new ConfigurationException(
                        'Class does not implement ' . PropertyHelperInterface::class,
                        1568285773
                    );
                }
                $helperClass = GeneralUtility::makeInstance(
                    $helperConfiguration['className'],
                    $properties,
                    $propertiesOld,
                    $propertyName,
                    $this->tableName,
                    $helperConfiguration['configuration'] ?? [],
                    $this->configuration['configuration'] ?? []
                );
                $helperClass->initialize();
                $properties = $helperClass->returnRecord();
            }
        }
        return $properties;
    }

    protected function genericChanges(array $properties): array
    {
        if (DatabaseUtility::isFieldExistingInTable('_migrated', $this->tableName)) {
            $properties['_migrated'] = 1;
        }
        return $properties;
    }

    protected function checkProperties(): void
    {
        if ($this->tableName === '') {
            throw new LogicException('No tablename given', 1568276662);
        }
    }

    protected function finalMessage(array $records): void
    {
        if ($this->configuration['configuration']['dryrun'] === false) {
            $message = count($records) . ' record(s) successfully migrated in ' . $this->tableName;
        } else {
            $message = count($records) . ' record(s) could be migrated without dryrun in ' . $this->tableName;
        }
        $this->log->addMessage($message);
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
