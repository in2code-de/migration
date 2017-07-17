<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

/**
 * Class CrossReplacePropertyHelper
 * to replace values in any target field from any start field with str_replace
 *
 * Example configuration:
 *  [
 *      // Set pages.backend_layout from pages.tx_templavoila_to
 *      'className' => CrossReplacePropertyHelper::class,
 *      'configuration' => [
 *          'search' => [
 *              'field' => 'tx_templavoila_to',
 *              'values' => [
 *                  '31', // 2 Columns
 *                  '1', // 3 Columns
 *                  '8' // Homepage
 *              ]
 *          ],
 *          'replace' => [
 *              'field' => 'backend_layout',
 *              'values' => [
 *                  'in2template__default',
 *                  'in2template__3cols',
 *                  'in2template__homepage'
 *              ]
 *          ],
 *          'defaultValue' => ''
 *      ]
 *  ]
 */
class CrossReplacePropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        if ($this->getConfigurationByKey('search.field') === null
            || $this->getConfigurationByKey('search.values') === null
            || $this->getConfigurationByKey('replace.field') === null
            || $this->getConfigurationByKey('replace.values') === null
            || $this->getConfigurationByKey('defaultValue') === null
        ) {
            throw new \Exception('configuration is missing in ' . __CLASS__);
        }
    }

    /**
     * @return void
     */
    protected function manipulate()
    {
        $startValue = $this->getPropertyFromRecord($this->getConfigurationByKey('search.field'));
        if (in_array($startValue, $this->getConfigurationByKey('search.values'))) {
            $newValue = str_replace(
                $this->getConfigurationByKey('search.values'),
                $this->getConfigurationByKey('replace.values'),
                $startValue
            );
        } else {
            $newValue = $this->getConfigurationByKey('defaultValue');
        }
        $this->record = [$this->getConfigurationByKey('replace.field') => $newValue] + $this->record;
    }
}
