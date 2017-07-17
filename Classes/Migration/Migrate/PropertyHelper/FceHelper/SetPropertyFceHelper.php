<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper\FceHelper;

/**
 * Class SetPropertyFceHelper
 */
class SetPropertyFceHelper extends AbstractFceHelper implements FceHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function start()
    {
        $newProperties = [
            $this->getConfigurationByKey('property') => $this->getConfigurationByKey('value')
        ];
        $this->propertyHelper->setProperties($newProperties);
    }
}
