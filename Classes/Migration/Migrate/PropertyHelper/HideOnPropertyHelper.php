<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

/**
 * Class HideOnPropertyHelper
 */
class HideOnPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        if ($this->getConfigurationByKey('fieldName') === null) {
            throw new \Exception('Configuration fieldName is missing for class ' . __CLASS__);
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function manipulate()
    {
        $this->setProperty($this->getValue());
    }

    /**
     * @return bool
     */
    public function shouldMigrate(): bool
    {
        return $this->getOldProperty() > 0;
    }

    /**
     * @return string
     */
    protected function getValue(): string
    {
        $value = '';
        $value .= $this->getValueForMobile();
        $value .= $this->getValueForTablet();
        $value .= $this->getValueForDesktop();
        return ltrim($value, ',');
    }

    /**
     * @return string
     */
    protected function getValueForMobile(): string
    {
        $value = '';
        if ($this->getOldProperty() === '1' || $this->getOldProperty() === '3' || $this->getOldProperty() === '5'
            || $this->getOldProperty() === '7') {
            $value = ',4';
        }
        return $value;
    }

    /**
     * @return string
     */
    protected function getValueForTablet(): string
    {
        $value = '';
        if ($this->getOldProperty() === '2' || $this->getOldProperty() === '3' || $this->getOldProperty() === '6'
            || $this->getOldProperty() === '7') {
            $value = ',2';
        }
        return $value;
    }

    /**
     * @return string
     */
    protected function getValueForDesktop(): string
    {
        $value = '';
        if ($this->getOldProperty() === '4' || $this->getOldProperty() === '5' || $this->getOldProperty() === '6'
            || $this->getOldProperty() === '7') {
            $value = ',1';
        }
        return $value;
    }

    /**
     * @return string
     */
    protected function getOldProperty(): string
    {
        return $this->getPropertyFromRecord($this->getConfigurationByKey('fieldName'));
    }
}
