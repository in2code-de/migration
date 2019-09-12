<?php
namespace In2code\Migration\MigrationOld\Migrate\PropertyHelper;

use In2code\Migration\Utility\ObjectUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;

/**
 * Class ReplaceOnNewsPluginPropertyHelper
 */
class ReplaceOnNewsPluginPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        if ($this->getConfigurationByKey('condition') === null || $this->getConfigurationByKey('replace') === null) {
            throw new \Exception('Configuration is missing for class ' . __CLASS__, 1527496459);
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function manipulate()
    {
        $this->setProperty($this->getConfigurationByKey('replace'));
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function shouldMigrate(): bool
    {
        if ($this->isNewsPlugin()) {
            if ($this->getConfigurationByKey('condition') === 'detail' && $this->getView() === 'News->detail') {
                return true;
            }
            if ($this->getConfigurationByKey('condition') === 'list' && $this->getView() !== 'News->detail') {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    protected function isNewsPlugin(): bool
    {
        return $this->getPropertyFromRecord('CType') === 'list'
            && $this->getPropertyFromRecord('list_type') === 'news_pi1';
    }

    /**
     * @return string
     */
    protected function getView(): string
    {
        /** @var FlexFormService $ff */
        $flexForm = ObjectUtility::getObjectManager()->get(FlexFormService::class);
        $ffArray = $flexForm->convertFlexFormContentToArray($this->getPropertyFromRecord('pi_flexform'));
        return (string)$ffArray['switchableControllerActions'];
    }
}
