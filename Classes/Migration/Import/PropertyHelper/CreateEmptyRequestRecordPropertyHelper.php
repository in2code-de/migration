<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

use In2code\In2template\Migration\Helper\DatabaseHelper;

/**
 * Class CreateEmptyRequestRecordPropertyHelper
 */
class CreateEmptyRequestRecordPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     */
    protected function manipulate()
    {
        $databaseHelper = $this->getObjectManager()->get(DatabaseHelper::class);
        $properties = [
            'pid' => $this->getPropertyFromOldRecord('pid'),
            'appointment' => $this->getPropertyFromOldRecord('uid'),
            'type' => 'In2code\HskaCalendar\Domain\Model\RequestTypes\AcademyRequest'
        ];
        $databaseHelper->createRecord('tx_hskacalendar_domain_model_request', $properties);
        $this->log->addMessage('New empty request record created');
    }
}
