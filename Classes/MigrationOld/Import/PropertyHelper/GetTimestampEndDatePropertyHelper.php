<?php
namespace In2code\Migration\MigrationOld\Import\PropertyHelper;

/**
 * Class GetTimestampEndDatePropertyHelper
 */
class GetTimestampEndDatePropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        if (!is_array($this->getConfigurationByKey('fields'))) {
            throw new \LogicException('Wrong configuration given', 1529412366);
        }
    }

    /**
     * @return void
     */
    public function manipulate()
    {
        $date = \DateTime::createFromFormat(
            'Ymd',
            $this->getPropertyFromOldRecord($this->getConfigurationByKey('fields.date'))
        );
        if ($date !== false) {
            $date->setTime(0, 0, 0);
        }
        $timestamp = ($date !== false ? $date->getTimestamp() : 0);
        $this->setProperty($timestamp);
    }
}
