<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

/**
 * Class ReplacePropertyHelper
 * to replace given keys with other mapped keys simply with str_replace([...], [...], $property)
 *
 *  Configuration example:
 *      [
 *          'className' => ReplacePropertyHelper::class,
 *          'configuration' => [
 *              'search' => ['no_title', 'prof'],
 *              'replace' => [0, 1],
 *              'default' => 0
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
    protected function manipulate()
    {
        $value = $this->getProperty();
        if (in_array($this->getProperty(), $this->getConfigurationByKey('search'))) {
            $value = str_replace(
                $this->getConfigurationByKey('search'),
                $this->getConfigurationByKey('replace'),
                $this->getProperty()
            );
        } elseif ($this->getConfigurationByKey('default')) {
            $value = $this->getConfigurationByKey('default');
        }
        $this->setProperty($value);
    }
}
