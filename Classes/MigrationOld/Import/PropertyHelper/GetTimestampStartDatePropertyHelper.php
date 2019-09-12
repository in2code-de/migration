<?php
namespace In2code\Migration\MigrationOld\Import\PropertyHelper;

/**
 * Class GetTimestampStartDatePropertyHelper
 */
class GetTimestampStartDatePropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
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
        $time = \DateTime::createFromFormat(
            'U',
            $this->getPropertyFromOldRecord($this->getConfigurationByKey('fields.time'))
        );
        $dateTimestamp = ($date !== false ? $date->getTimestamp() : 0);
        $timeTimestamp = ($time !== false ? $time->getTimestamp() : 0);
        $timestamp = $dateTimestamp + $timeTimestamp;
        $this->setProperty($timestamp);
    }
}
