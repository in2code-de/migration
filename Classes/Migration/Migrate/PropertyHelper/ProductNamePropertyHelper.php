<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

/**
 * Class ProductNamePropertyHelper
 */
class ProductNamePropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * Fieldname from table tx_udgmvproducts_domain_model_product to use
     *
     * @var string
     */
    protected $oldPropertyName = 'name';

    /**
     * @return void
     */
    public function manipulate()
    {
        $properties = $this->getProductProperties();
        $this->setProperty($properties[$this->oldPropertyName]);
    }

    /**
     * @return bool
     */
    public function shouldMigrate(): bool
    {
        $properties = $this->getProductProperties();
        return !empty($properties[$this->oldPropertyName]);
    }

    /**
     * @return array
     */
    protected function getProductProperties(): array
    {
        $properties = [];
        $row = (array)$this->getDatabase()->exec_SELECTgetSingleRow(
            '*',
            'tx_udgmvproducts_domain_model_product',
            'pid=' . $this->getPropertyFromRecord('uid') . ' and deleted = 0 and hidden = 0'
        );
        if (!empty($row['uid'])) {
            $properties = $row;
        }
        return $properties;
    }
}
