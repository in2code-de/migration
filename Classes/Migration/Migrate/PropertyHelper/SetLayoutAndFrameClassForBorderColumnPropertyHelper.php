<?php
namespace In2code\Migration\Migration\Migrate\PropertyHelper;

/**
 * Class SetLayoutAndFrameClassForBorderColumnPropertyHelper
 */
class SetLayoutAndFrameClassForBorderColumnPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        if (!is_array($this->getConfigurationByKey('conditions'))
            && !is_array($this->getConfigurationByKey('values'))
            && !is_array($this->getConfigurationByKey('valuesImage'))
            && !is_array($this->getConfigurationByKey('valuesImageBelow'))) {
            throw new \LogicException('Configuration is missing for class ' . __CLASS__, 1527582074);
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function manipulate()
    {
        if ($this->hasContentImages()) {
            $properties = $this->getValuesForContentWithImages();
        } else {
            $properties = $this->getValuesForContentWithoutImages();
        }
        $this->setProperties($properties);
    }

    /**
     * @return array
     */
    protected function getValuesForContentWithImages(): array
    {
        if ($this->isImageBelowContent()) {
            $properties = $this->getConfigurationByKey('valuesImageBelow') + $this->getProperties();
        } else {
            $properties = $this->getConfigurationByKey('valuesImage') + $this->getProperties();
        }
        return $properties;
    }

    /**
     * @return array
     */
    protected function getValuesForContentWithoutImages(): array
    {
        return $this->getConfigurationByKey('values') + $this->getProperties();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function shouldMigrate(): bool
    {
        foreach ($this->getConfigurationByKey('conditions') as $fieldname => $values) {
            if (!in_array($this->getPropertyFromRecord($fieldname), $values)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    protected function hasContentImages(): bool
    {
        return $this->getPropertyFromRecord('image') > 0;
    }

    /**
     * @return bool
     */
    protected function isImageBelowContent(): bool
    {
        $imageOrient = ['8', '9', '10'];
        return in_array($this->getPropertyFromRecord('imageorient'), $imageOrient);
    }
}
