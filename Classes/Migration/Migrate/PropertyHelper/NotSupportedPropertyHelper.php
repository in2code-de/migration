<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

/**
 * Class NotSupportedPropertyHelper
 */
class NotSupportedPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    protected function manipulate()
    {
        if ($this->isaConditionMatching()) {
            $newProperties = $this->getConfigurationByKey('properties');
            $this->record = $newProperties + $this->record;
        }
    }

    /**
     * @return bool
     */
    protected function isaConditionMatching(): bool
    {
        foreach ($this->getConfigurationByKey('conditions') as $condition) {
            $matching = true;
            foreach ($condition as $field => $value) {
                if ($this->getPropertyFromRecord($field) !== $value) {
                    $matching = false;
                }
            }
            if ($matching === true) {
                return true;
            }
        }
        return false;
    }
}
