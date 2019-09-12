<?php
namespace In2code\Migration\MigrationOld\Migrate\PropertyHelper;

/**
 * Class IncreasePropertyHelper
 */
class IncreasePropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \DomainException
     */
    public function initialize()
    {
        if (!is_int($this->getConfigurationByKey('valueToAdd'))
            || !is_array($this->getConfigurationByKey('condition'))) {
            throw new \DomainException('wrong configuration given in class' . __CLASS__, 1525439584);
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function manipulate()
    {
        $value = (int)$this->getProperty() + $this->getConfigurationByKey('valueToAdd');
        $this->setProperty($value);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function shouldMigrate(): bool
    {
        foreach ($this->getConfigurationByKey('condition') as $field => $value) {
            if ($this->getPropertyFromRecord($field) !== $value) {
                return false;
            }
        }
        return true;
    }
}
