<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class ChangeFileRelationPropertyHelper to change existing sys_file_reference records on a condition
 *
 * Example usage:
 *
 * 'template_logo' => [
 *      [
 *          'className' => ChangeFileRelationPropertyHelper::class,
 *          'configuration' => [
 *              'conditions' => [
 *                  'backend_layout' => 'in2template__Productpage'
 *              ],
 *              'from' => [
 *                  'tablenames' => 'pages',
 *                  'fieldname' => 'image',
 *                  'uid_foreign' => '{uid}'
 *              ],
 *              'to' => [
 *                  'tablenames' => 'pages',
 *                  'fieldname' => 'template_logo',
 *                  'uid_foreign' => '{uid}'
 *              ]
 *          ]
 *      ]
 */
class ChangeFileRelationPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        $neededKeys = ['conditions', 'from', 'to'];
        foreach ($neededKeys as $key) {
            if (!is_array($this->getConfigurationByKey($key))) {
                throw new \Exception('Configuration ' . $key . ' is missing for class ' . __CLASS__);
            }
        }
    }

    /**
     * @return void
     */
    public function manipulate()
    {
        $this->getDatabase()->exec_UPDATEquery(
            'sys_file_reference',
            $this->getWhereClause(),
            $this->getNewProperties()
        );
    }

    /**
     * @return string
     */
    protected function getWhereClause(): string
    {
        $constraints = [];
        foreach ($this->getConfigurationByKey('from') as $fieldname => $value) {
            $value = $this->parseWithProperties($value);
            $constraints[] = $fieldname . '="' . $value . '"';
        }
        return implode(' and ', $constraints);
    }

    /**
     * @return array
     */
    protected function getNewProperties(): array
    {
        $newProperties = $this->getConfigurationByKey('to');
        foreach (array_keys($newProperties) as $key) {
            $newProperties[$key] = $this->parseWithProperties($newProperties[$key]);
        }
        return $newProperties;
    }

    /**
     * @param string $string
     * @return string
     */
    protected function parseWithProperties(string $string): string
    {
        $standaloneView = $this->getObjectManager()->get(StandaloneView::class);
        $standaloneView->setTemplateSource($string);
        $standaloneView->assignMultiple($this->getProperties());
        return $standaloneView->render();
    }

    /**
     * @return bool
     */
    public function shouldMigrate(): bool
    {
        $conditions = $this->getConfigurationByKey('conditions');
        foreach ($conditions as $field => $value) {
            if ($this->getPropertyFromRecord($field) !== $value) {
                return false;
            }
        }
        return true;
    }
}
