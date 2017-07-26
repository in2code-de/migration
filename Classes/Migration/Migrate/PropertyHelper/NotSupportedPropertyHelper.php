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
    public function manipulate()
    {
        $newProperties = $this->getConfigurationByKey('properties');
        $this->record = $newProperties + $this->record;
    }

    /**
     * @return bool
     */
    public function shouldMigrate(): bool
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
