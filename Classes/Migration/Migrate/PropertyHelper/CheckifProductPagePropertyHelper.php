<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

/**
 * Class CheckifProductPagePropertyHelper
 */
class CheckifProductPagePropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        if ($this->getConfigurationByKey('replace') === null) {
            throw new \Exception('Configuration is missing for class ' . __CLASS__);
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function manipulate()
    {
        $this->setProperty($this->getConfigurationByKey('replace'));
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function shouldMigrate(): bool
    {
        $row = (array)$this->getDatabase()->exec_SELECTgetSingleRow(
            'uid',
            'tx_udgmvproducts_domain_model_product',
            'pid=' . $this->getPropertyFromRecord('uid') . ' and deleted = 0 and hidden = 0'
        );
        return !empty($row['uid']);
    }
}
