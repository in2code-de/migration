<?php
namespace In2code\Migration\MigrationOld\Migrate\PropertyHelper;

/**
 * Class ChangePropertiesOnConditionPropertyHelper (former NotSupportedPropertyHelper)
 *
 *      'configuration' => [
 *          'conditions' => [
 *              [
 *                  'CType' => 'mailform'
 *                  'parent.CType' => 'textmedia'
 *              ]
 *           ],
 *           'properties' => [
 *              'CType' => 'textmedia',
 *              'list_type' => '',
 *              'bodytext' => '',
 *              'header' => 'This element is not supported any more'
 *           ],
 *          'parentRelation' => ''
 *       ]
 */
class ChangePropertiesOnConditionPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
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
