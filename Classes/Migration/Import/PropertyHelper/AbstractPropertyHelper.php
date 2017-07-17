<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

use In2code\In2template\Migration\Service\Log;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class AbstractHelper
 */
abstract class AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @var array
     */
    protected $oldRecord = [];

    /**
     * @var array
     */
    protected $newRecord = [];

    /**
     * @var string
     */
    protected $oldTable = '';

    /**
     * @var string
     */
    protected $newTable = '';

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
     * @param array $oldRecord
     * @param array $newRecord
     * @param string $propertyName
     * @param string $oldTable
     * @param string $newTable
     * @param array $configuration
     */
    public function __construct(
        array $oldRecord,
        array $newRecord,
        string $propertyName,
        string $oldTable,
        string $newTable,
        array $configuration = []
    ) {
        $this->oldRecord = $oldRecord;
        $this->newRecord = $newRecord;
        $this->propertyName = $propertyName;
        $this->oldTable = $oldTable;
        $this->newTable = $newTable;
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
        return $this->getNewRecord();
    }

    /**
     * @return void
     */
    protected function manipulate()
    {
    }

    /**
     * @param string|int $value
     * @return void
     */
    protected function setProperty($value)
    {
        $this->newRecord[$this->propertyName] = $value;
    }

    /**
     * @param string|int $value
     * @param string $property
     * @return void
     */
    protected function setPropertyByName(string $property, $value)
    {
        $this->newRecord[$property] = $value;
    }

    /**
     * @return array
     */
    protected function getOldRecord(): array
    {
        return $this->oldRecord;
    }

    /**
     * @return array
     */
    protected function getNewRecord(): array
    {
        return $this->newRecord;
    }

    /**
     * @return string
     */
    protected function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /**
     * @param string $propertyName
     * @return string|int
     */
    protected function getPropertyFromOldRecord(string $propertyName)
    {
        $oldRecord = $this->getOldRecord();
        $property = '';
        if (!empty($oldRecord[$propertyName])) {
            $property = $oldRecord[$propertyName];
        }
        return $property;
    }

    /**
     * @param string $propertyName
     * @return string|int
     */
    protected function getPropertyFromNewRecord(string $propertyName)
    {
        $newRecord = $this->getNewRecord();
        $property = '';
        if (!empty($newRecord[$propertyName])) {
            $property = $newRecord[$propertyName];
        }
        return $property;
    }

    /**
     * @return string
     */
    protected function getProperty(): string
    {
        return $this->getPropertyFromNewRecord($this->getPropertyName());
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    protected function getConfigurationByKey(string $key)
    {
        if (array_key_exists($key, $this->configuration)) {
            return $this->configuration[$key];
        }
        return null;
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager(): ObjectManager
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }

    /**
     * @return DatabaseConnection
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getDatabase(): DatabaseConnection
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
