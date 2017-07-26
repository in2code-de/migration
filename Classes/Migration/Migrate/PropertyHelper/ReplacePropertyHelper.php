<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

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
        if ($this->getConfigurationByKey('search') === null || $this->getConfigurationByKey('replace') === null) {
            throw new \Exception('configuration search, replace or default is missing');
        }
    }

    /**
     * @return void
     */
    public function manipulate()
    {
        $value = $this->getValue();
        if (in_array($value, $this->getConfigurationByKey('search'))) {
            $value = str_replace(
                $this->getConfigurationByKey('search'),
                $this->getConfigurationByKey('replace'),
                $value
            );
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
