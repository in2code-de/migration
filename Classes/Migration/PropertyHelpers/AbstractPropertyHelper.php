<?php

declare(strict_types=1);

namespace In2code\Migration\Migration\PropertyHelpers;

use In2code\Migration\Exception\ConfigurationException;
use In2code\Migration\Migration\Log\Log;
use Throwable;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractPropertyHelper implements PropertyHelperInterface
{
    protected array $record = [];

    /**
     * Original properties (not modified for migrators, old record properties for importers)
     *
     * @var array
     */
    protected array $recordOld = [];

    protected string $table = '';

    /**
     * Property to manipulate
     *
     * @var string
     */
    protected string $propertyName = '';

    protected array $configuration = [];
    protected array $migrationConfiguration = [];
    protected array $checkForConfiguration = [];

    protected ?Log $log = null;

    /**
     * @param array $record
     * @param array $recordOld Original properties (not modified for migrators, old record properties for importers)
     * @param string $propertyName
     * @param string $table
     * @param array $configuration
     * @param array $migrationConfiguration
     * @throws ConfigurationException
     */
    public function __construct(
        array $record,
        array $recordOld,
        string $propertyName,
        string $table,
        array $configuration = [],
        array $migrationConfiguration = []
    ) {
        $this->record = $record;
        $this->recordOld = $recordOld;
        $this->propertyName = $propertyName;
        $this->table = $table;
        $this->configuration = $configuration;
        $this->migrationConfiguration = $migrationConfiguration;
        $this->log = GeneralUtility::makeInstance(Log::class);

        if ($this->checkForConfiguration !== []) {
            foreach ($this->checkForConfiguration as $key) {
                if (array_key_exists($key, $this->configuration) === false) {
                    throw new ConfigurationException(
                        'Missing configuration in property helper ' . static::class,
                        1568287339
                    );
                }
            }
        }
    }

    public function initialize(): void
    {
    }

    public function returnRecord(): array
    {
        if ($this->shouldMigrate()) {
            $this->manipulate();
        }
        return $this->getRecord();
    }

    public function manipulate(): void
    {
    }

    public function shouldMigrate(): bool
    {
        return true;
    }

    /**
     * This function can be used in the shouldMigrate() function and will check for default condition-field-values like:
     *  'configuration' => [
     *      'conditions' => [
     *          'CType' => [
     *              'list'
     *          ],
     *          'list_type' => [
     *              'jhmagnificpopup_pi1'
     *          ]
     *      ]
     *  ]
     *
     * @return bool
     * @throws ConfigurationException
     */
    public function shouldMigrateByDefaultConditions(): bool
    {
        $isFitting = true;
        foreach ((array)$this->getConfigurationByKey('conditions') as $field => $values) {
            if (!is_string($field) || !is_array($values)) {
                throw new ConfigurationException(
                    'Misconfiguration of configuration of ' . static::class,
                    1569407191
                );
            }
            if (!in_array($this->getPropertyFromRecord($field), $values)) {
                $isFitting = false;
                break;
            }
        }
        return $isFitting;
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    protected function getRecordOld(): array
    {
        return $this->recordOld;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /**
     * @param string $propertyName
     * @return string|int
     */
    public function getPropertyFromRecord(string $propertyName)
    {
        $record = $this->getRecord();
        $property = '';
        if (array_key_exists($propertyName, $record)) {
            $property = $record[$propertyName];
        }
        return $property;
    }

    /**
     * @param string $propertyName
     * @return string|int
     */
    protected function getPropertyFromRecordOld(string $propertyName)
    {
        $oldRecord = $this->getRecordOld();
        $property = '';
        if (isset($oldRecord[$propertyName])) {
            $property = $oldRecord[$propertyName];
        }
        return $property;
    }

    /**
     * @param string|int $value
     * @return void
     */
    protected function setProperty($value): void
    {
        $this->record[$this->propertyName] = $value;
    }

    public function getProperty(): string
    {
        return (string)$this->getPropertyFromRecord($this->getPropertyName());
    }

    public function getProperties(): array
    {
        return $this->record;
    }

    public function setProperties(array $properties): void
    {
        $this->record = $properties + $this->record;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getConfigurationByKey(string $key)
    {
        if (!stristr($key, '.') && array_key_exists($key, $this->configuration)) {
            return $this->configuration[$key];
        }
        if (stristr($key, '.')) {
            try {
                return ArrayUtility::getValueByPath($this->configuration, $key, '.');
            } catch (Throwable $exception) {
                unset($exception);
            }
        }
        return null;
    }

    public function isAlreadyMigrated(): bool
    {
        return !empty($this->getPropertyFromRecord('_migrated'));
    }

    public function isHidden(): bool
    {
        return !empty($this->getPropertyFromRecord('hidden'));
    }
}
