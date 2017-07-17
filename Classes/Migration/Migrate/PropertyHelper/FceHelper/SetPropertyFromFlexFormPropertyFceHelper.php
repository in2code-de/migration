<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper;

use TYPO3\CMS\Extbase\Service\FlexFormService;

/**
 * Class SetPropertyFromFlexFormPropertyFceHelper
 */
class SetPropertyFromFlexFormPropertyFceHelper extends AbstractFceHelper implements FceHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function start()
    {
        $newProperties = [
            $this->getConfigurationByKey('property') => $this->getValue()
        ];
        $this->propertyHelper->setProperties($newProperties);
    }

    /**
     * @return string
     */
    protected function getValue(): string
    {
        $value = '';
        $flexFormArray = $this->getFlexFormArray();
        if (array_key_exists($this->getConfigurationByKey('fromProperty'), $flexFormArray)) {
            $value = $flexFormArray[$this->getConfigurationByKey('fromProperty')];
        }
        return $value;
    }

    /**
     * @return array
     */
    protected function getFlexFormArray(): array
    {
        $flexFormService = $this->getObjectManager()->get(FlexFormService::class);
        return (array)$flexFormService->convertFlexFormContentToArray(
            $this->propertyHelper->getPropertyFromRecord('tx_templavoila_flex')
        );
    }
}
