<?php
namespace In2code\Migration\MigrationOld\Migrate\PropertyHelper;

/**
 * Class ReplacePropertyHelper
 * to replace given keys with other mapped keys simply with str_replace([...], [...], $property)
 *
 *  Configuration example simple:
 *      [
 *          'className' => ReplacePropertyHelper::class,
 *          'configuration' => [
 *              'search' => ['no_title', 'prof'],
 *              'replace' => [0, 1]
 *          ]
 *      ]
 *
 *  Configuration example:
 *      [
 *          'className' => ReplacePropertyHelper::class,
 *          'configuration' => [
 *              'search' => ['no_title', 'prof'],
 *              'replace' => [0, 1],
 *              'default' => 0,
 *              'startField' => 'fieldnameold'
 *          ]
 *      ]
 *
 */
class ReplacePropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        if (!is_array($this->getConfigurationByKey('search'))
            || !is_array($this->getConfigurationByKey('replace'))
            || count($this->getConfigurationByKey('search')) !== count($this->getConfigurationByKey('replace'))) {
            throw new \Exception('configuration is wrong', 1525771174);
        }
    }

    /**
     * @return void
     */
    public function manipulate()
    {
        $value = $this->getValue();
        if (in_array($value, $this->getConfigurationByKey('search'))) {
            foreach ($this->getConfigurationByKey('search') as $key => $search) {
                if ($value === $search) {
                    $value = $this->getConfigurationByKey('replace')[$key];
                }
            }
            $this->setProperty($value);
        } elseif ($this->getConfigurationByKey('default') !== null) {
            $value = $this->getConfigurationByKey('default');
            $this->setProperty($value);
        }
    }

    /**
     * @return string
     */
    protected function getValue(): string
    {
        $value = $this->getProperty();
        if ($this->getConfigurationByKey('startField') !== null) {
            $value = $this->getPropertyFromRecord($this->getConfigurationByKey('startField'));
        }
        return $value;
    }
}
