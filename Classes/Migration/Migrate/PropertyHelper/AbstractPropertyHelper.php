<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

use In2code\In2template\Migration\Service\Log;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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
     * @var DatabaseConnection|null
     */
    protected $database = null;

    /**
     * @var Log|null
     */
    protected $log = null;

    /**
     * AbstractPropertyHelper constructor.
     *
     * @param array $record
     * @param string $propertyName
     * @param string $table
     * @param array $configuration
     */
    public function __construct(array $record, string $propertyName, string $table, array $configuration = [])
    {
        $this->record = $record;
        $this->propertyName = $propertyName;
        $this->table = $table;
        $this->configuration = $configuration;
        $this->database = $this->getDatabase();
        $this->log = $this->getObjectManager()->get(Log::class);
    }

    /**
     * @return void
     */
    public function initialize()
    {
    }

    /**
     * @return array
     */
    public function returnRecord(): array
    {
        $this->manipulate();
        return $this->getRecord();
    }

    /**
     * @return void
     */
    protected function manipulate()
    {
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
        if (!empty($record[$propertyName])) {
            $property = $record[$propertyName];
        }
        return $property;
    }

    /**
     * @param string|int $value
     * @return void
     */
    protected function setProperty($value)
    {
        $this->record[$this->propertyName] = $value;
    }

    /**
     * @return string
     */
    public function getProperty(): string
    {
        return $this->getPropertyFromRecord($this->getPropertyName());
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
    public function setProperties(array $properties)
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
                return ArrayUtility::getValueByPath($this->configuration, $key, '.');
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

    /**
     * @return ObjectManager
     */
    public function getObjectManager(): ObjectManager
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }

    /**
     * @return DatabaseConnection
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function getDatabase(): DatabaseConnection
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
