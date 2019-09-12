<?php
namespace In2code\Migration\MigrationOld\Migrate\PropertyHelper;

/**
 * Class AddValueByPidPropertyHelper
 */
class AddValueByPidPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    public function initialize()
    {
        if (!is_array($this->getConfigurationByKey('mapping'))) {
            throw new \LogicException('No configuration given in ' . __CLASS__, 1527696608);
        }
    }

    /**
     * @return void
     */
    public function manipulate()
    {
        $value = $this->getConfigurationByKey('mapping')[(int)$this->getPropertyFromRecord('uid')];
        $this->setProperty($value);
    }

    /**
     * @return bool
     */
    public function shouldMigrate(): bool
    {
        return array_key_exists($this->getPropertyFromRecord('uid'), $this->getConfigurationByKey('mapping'));
    }
}
