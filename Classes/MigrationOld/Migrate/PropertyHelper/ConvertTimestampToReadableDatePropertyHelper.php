<?php
namespace In2code\Migration\MigrationOld\Migrate\PropertyHelper;

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class ConvertTimestampToReadableDatePropertyHelper
 */
class ConvertTimestampToReadableDatePropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     */
    public function initialize()
    {
        foreach (['format', 'append', 'prepend'] as $arrayKey) {
            if ($this->getConfigurationByKey($arrayKey) === null) {
                throw new \DomainException('Configuration is missing or wrong', 1530275522);
            }
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function manipulate()
    {
        if ($this->getProperty() === '0') {
            $property = '';
        } elseif ($this->getProperty() > 0) {
            $readableDate = strftime($this->getConfigurationByKey('format'), (int)$this->getProperty());
            $property
                = $this->getConfigurationByKey('prepend') . $readableDate . $this->getConfigurationByKey('append');
        }
        $this->setProperty($property);
    }

    /**
     * @return bool
     */
    public function shouldMigrate(): bool
    {
        return $this->getProperty() !== '' && MathUtility::canBeInterpretedAsInteger($this->getProperty());
    }
}
