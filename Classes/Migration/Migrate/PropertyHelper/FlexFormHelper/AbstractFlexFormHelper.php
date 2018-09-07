<?php
namespace In2code\Migration\Migration\Migrate\PropertyHelper\FlexFormHelper;

use In2code\Migration\Migration\Migrate\PropertyHelper\AbstractPropertyHelper;
use In2code\Migration\Migration\Migrate\PropertyHelper\PropertyHelperInterface;
use In2code\Migration\Migration\Service\Log;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Service\FlexFormService;

/**
 * Class AbstractFlexFormHelper
 */
abstract class AbstractFlexFormHelper implements FlexFormHelperInterface
{

    /**
     * @var null|AbstractPropertyHelper
     */
    protected $propertyHelper = null;

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * @var Log|null
     */
    protected $log = null;

    /**
     * AbstractFceHelper constructor.
     *
     * @param PropertyHelperInterface $propertyHelper
     * @param array $configuration
     */
    public function __construct(PropertyHelperInterface $propertyHelper, array $configuration)
    {
        $this->propertyHelper = $propertyHelper;
        $this->configuration = $configuration;
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
    protected function getFlexFormArray(): array
    {
        $flexFormService = $this->getObjectManager()->get(FlexFormService::class);
        return $flexFormService->convertFlexFormContentToArray(
            $this->propertyHelper->getPropertyFromRecord('pi_flexform')
        );
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    protected function getConfigurationByKey(string $key)
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
