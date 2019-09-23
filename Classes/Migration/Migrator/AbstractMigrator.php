<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\Migrator;

use Doctrine\DBAL\DBALException;
use In2code\Migration\Migration\Exception\ConfigurationException;
use In2code\Migration\Migration\Log\Log;
use In2code\Migration\Migration\PropertyHelpers\PropertyHelperInterface;
use In2code\Migration\Migration\Repository\GeneralRepository;
use In2code\Migration\Utility\DatabaseUtility;
use In2code\Migration\Utility\ObjectUtility;
use In2code\Migration\Utility\StringUtility;

/**
 * Class AbstractMigrator
 */
abstract class AbstractMigrator
{
    /**
     * Table where to run the migration
     *
     * @var string
     */
    protected $tableName = '';

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
        $records = $generalRepository->getRecords($this->tableName);
        foreach ($records as $properties) {
            $this->log->addNote(
                'Start migrating ' . $this->tableName
                . ' (uid' . $properties['uid'] . '/pid' . $properties['pid'] . ') ...'
            );
            $properties = $this->manipulatePropertiesWithValues($properties);
            $properties = $this->manipulatePropertiesWithPropertyHelpers($properties);
            $generalRepository->updateRecord($properties, $this->tableName);
        }
        $this->executeSqlEnd();
        $this->finalMessage($records);
    }

    /**
     * @param array $properties
     * @return array
     */
    protected function manipulatePropertiesWithValues(array $properties): array
    {
        foreach ($this->values as $propertyName => $propertyValue) {
            if (array_key_exists($propertyName, $properties) === false) {
                throw new \LogicException('Property ' . $propertyName . ' does not exist', 1568278136);
            }
            $variables = [
                'properties' => $properties,
                'tableName' => $this->tableName
            ];
            $properties[$propertyName] = StringUtility::parseString((string)$propertyValue, $variables);
        }
        return $properties;
    }

    /**
     * @param array $properties
     * @return array
     * @throws ConfigurationException
     */
    protected function manipulatePropertiesWithPropertyHelpers(array $properties): array
    {
        foreach ($this->propertyHelpers as $propertyName => $helperConfigurations) {
            foreach ($helperConfigurations as $helperConfiguration) {
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
                /** @var PropertyHelperInterface $helperClass */
                $helperClass = ObjectUtility::getObjectManager()->get(
                    $helperConfiguration['className'],
                    $properties,
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
     * @return void
     */
    protected function checkProperties(): void
    {
        if ($this->tableName === '') {
            throw new \LogicException('No tablename given', 1568276662);
        }
    }

    /**
     * @param array $records
     * @return void
     */
    protected function finalMessage(array $records)
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
