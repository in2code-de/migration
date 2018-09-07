<?php
namespace In2code\Migration\Migration\Migrate\PropertyHelper;

/**
 * Class UpdateNewsFlexFormPropertyHelper
 *
 *  Example configuration:
 *      'configuration' => [
 *          'conditions' => [
 *              'CType' => ['list'],
 *              'list_type' => ['news_pi1'],
 *              'colPos' => ['0']
 *          ],
 *          'mapping' => [
 *              'settings.list.paginate.itemsPerPage' => '8'
 *          ]
 *      ]
 */
class UpdateNewsFlexFormPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        if (!is_array($this->getConfigurationByKey('conditions'))
            || !is_array($this->getConfigurationByKey('mapping'))) {
            throw new \Exception('Configuration is missing for class ' . __CLASS__, 1527499145);
        }
    }

    /**
     * preg_replace(
     *      "/(<field\sindex=\"settings.limit\">\n\s+<value\sindex=\"vDEF\">)([^<]*)(<\/value>)/U",
     *      "${1}123${3}",
     *      $input_lines
     * );
     *
     * @return void
     * @throws \Exception
     */
    public function manipulate()
    {
        $flexForm = $this->getProperty();
        $keys = $this->getMappingKeys();
        foreach ($keys as $key) {
            $flexForm = preg_replace(
                '~(<field\sindex=\"' . $key . '\">\n\s+<value\sindex=\"vDEF\">)([^<]*)(<\/value>)~U',
                '${1}' . $this->getMappingValueToKey($key) . '${3}',
                $flexForm
            );
        }
        $this->setProperty($flexForm);
    }

    /**
     * @return array
     */
    protected function getMappingKeys(): array
    {
        return array_keys($this->getConfigurationByKey('mapping'));
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getMappingValueToKey(string $key): string
    {
        return $this->getConfigurationByKey('mapping')[$key];
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function shouldMigrate(): bool
    {
        $isFitting = true;
        foreach ($this->getConfigurationByKey('conditions') as $field => $values) {
            if (!is_string($field) || !is_array($values)) {
                throw new \Exception('Possible misconfiguration of configuration of ' . __CLASS__);
            }
            if (!in_array($this->getPropertyFromRecord($field), $values)) {
                $isFitting = false;
                break;
            }
        }
        return $isFitting;
    }
}
