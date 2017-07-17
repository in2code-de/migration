<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

use In2code\In2template\Migration\Helper\DatabaseHelper;

/**
 * Class CreateDateCyclesPropertyHelper
 */
class CreateDateCyclesPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @var string
     */
    protected $table = 'tx_hskacalendar_domain_model_datecycle';

    /**
     * @return void
     */
    protected function manipulate()
    {
        $relationAmount = 0;
        switch ($this->getPropertyFromOldRecord('rhythm')) {
            case 'single':
                $this->singleDateCycle();
                $relationAmount = 1;
                break;
            case 'block1':
                $this->block1DateCycle();
                $relationAmount = 1;
                break;
            case 'block2':
                $this->block2DateCycle();
                $relationAmount = 2;
                break;
            case 'weekly':
                $this->log->addNote('DateCycle skipped because weekly not supported');
                break;
        }
        $this->setProperty($relationAmount);
    }

    /**
     * @return void
     */
    protected function singleDateCycle()
    {
        $this->createDateCycle(
            $this->getPropertyFromOldRecord('date'),
            $this->getPropertyFromOldRecord('date'),
            $this->getPropertyFromOldRecord('start_time'),
            $this->getPropertyFromOldRecord('end_time'),
            'In2code\HskaCalendar\Domain\Model\DateCycle\SingleDateCycle'
        );
    }

    /**
     * @return void
     */
    protected function block1DateCycle()
    {
        $this->createDateCycle(
            $this->getPropertyFromOldRecord('date'),
            $this->getPropertyFromOldRecord('end_date'),
            $this->getPropertyFromOldRecord('start_time'),
            $this->getPropertyFromOldRecord('end_time'),
            'In2code\HskaCalendar\Domain\Model\DateCycle\SingleDateCycle'
        );
    }

    /**
     * @return void
     */
    protected function block2DateCycle()
    {
        $this->createDateCycle(
            $this->getPropertyFromOldRecord('date'),
            $this->getPropertyFromOldRecord('date'),
            $this->getPropertyFromOldRecord('start_time'),
            $this->getPropertyFromOldRecord('end_time'),
            'In2code\HskaCalendar\Domain\Model\DateCycle\SingleDateCycle'
        );
        $this->createDateCycle(
            $this->getPropertyFromOldRecord('date2'),
            $this->getPropertyFromOldRecord('date2'),
            $this->getPropertyFromOldRecord('start_time2'),
            $this->getPropertyFromOldRecord('end_time2'),
            'In2code\HskaCalendar\Domain\Model\DateCycle\SingleDateCycle'
        );
    }

    /**
     * @param string $dateStart
     * @param string $dateEnd
     * @param string $timeStart
     * @param string $timeEnd
     * @param string $type
     * @return void
     */
    protected function createDateCycle(
        string $dateStart,
        string $dateEnd,
        string $timeStart,
        string $timeEnd,
        string $type
    ) {
        if (!empty($dateStart)) {
            $properties = [
                'appointment' => $this->getPropertyFromOldRecord('uid'),
                'pid' => $this->getPropertyFromOldRecord('pid'),
                'type' => $type,
                'all_day' => 0,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'time_start' => $timeStart,
                'time_end' => $timeEnd,
            ];
            $databaseHelper = $this->getObjectManager()->get(DatabaseHelper::class);
            $databaseHelper->createRecord($this->table, $properties);
            $this->log->addMessage('DateCycle created');
        } else {
            $this->log->addError('Empty date start - no cycle generated');
        }
    }
}
