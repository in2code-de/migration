<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\PropertyHelpers;

use In2code\Migration\Migration\Exception\ConfigurationException;
use In2code\Migration\Migration\Log\Log;
use In2code\Migration\Utility\ObjectUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Class AbstractPropertyHelper
 */
abstract class AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @var array
     */
    protected $record = [];

    /**
     * @var string
     */
    protected $table = '';

    /**
     * Property to manipulate
     *
     * @var string
     */
    protected $propertyName = '';

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * @var array
     */
    protected $checkForConfiguration = [];

    /**
     * @var Log|null
     */
    protected $log = null;

    /**
     * AbstractPropertyHelper constructor.
     * @param array $record
     * @param string $propertyName
     * @param string $table
     * @param array $configuration
     * @throws ConfigurationException
     */
    public function __construct(array $record, string $propertyName, string $table, array $configuration = [])
    {
        $this->record = $record;
        $this->propertyName = $propertyName;
        $this->table = $table;
        $this->configuration = $configuration;
        $this->log = ObjectUtility::getObjectManager()->get(Log::class);

        if ($this->checkForConfiguration !== []) {
            foreach ($this->checkForConfiguration as $key) {
                if (array_key_exists($key, $this->configuration) === false) {
                    throw new ConfigurationException(
                        'Missing configuration in property helper ' . __CLASS__,
                        1568287339
                    );
                }
            }
        }
    }

    /**
     * @return void
     */
    public function initialize(): void
    {
    }

    /**
     * @return array
     */
    public function returnRecord(): array
    {
        if ($this->shouldMigrate()) {
            $this->manipulate();
        }
        return $this->getRecord();
    }

    /**
     * @return void
     */
    public function manipulate(): void
    {
    }

    /**
     * @return bool
     */
    public function shouldMigrate(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function getRecord(): array
    {
        return $this->record;
    }

    /**
     * @return string
     */
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
     * @param string|int $value
     * @return void
     */
    protected function setProperty($value): void
    {
        $this->record[$this->propertyName] = $value;
    }

    /**
     * @return string
     */
    public function getProperty(): string
    {
        return (string)$this->getPropertyFromRecord($this->getPropertyName());
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->record;
    }

    /**
     * @param array $properties
     * @return void
     */
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
        if (is_array($this->configuration)) {
            if (!stristr($key, '.') && array_key_exists($key, $this->configuration)) {
                return $this->configuration[$key];
            }
            if (stristr($key, '.')) {
                try {
                    return ArrayUtility::getValueByPath($this->configuration, $key, '.');
                } catch (\Exception $exception) {
                    unset($exception);
                }
            }
        }
        return null;
    }

    /**
     * @return bool
     */
    public function isAlreadyMigrated(): bool
    {
        return !empty($this->getPropertyFromRecord('_migrated'));
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return !empty($this->getPropertyFromRecord('hidden'));
    }
}
