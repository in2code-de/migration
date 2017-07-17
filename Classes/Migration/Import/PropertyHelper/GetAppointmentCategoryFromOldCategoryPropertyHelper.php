<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

/**
 * Class GetAppointmentCategoryFromOldCategoryPropertyHelper
 */
class GetAppointmentCategoryFromOldCategoryPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @var string
     */
    protected $oldCategoryTable = 'tx_pthskaadmin_domain_model_appointmentcategory';

    /**
     * @return void
     */
    protected function manipulate()
    {
        $categoryUid = $this->getCategoryUidFromOldCategoryUid((int)$this->getProperty());
        if ($categoryUid > 0) {
            $this->setProperty($categoryUid);
        }
    }

    /**
     * @param int $oldCategoryUid
     * @return int
     */
    protected function getCategoryUidFromOldCategoryUid(int $oldCategoryUid): int
    {
        $newCategoryUid = 0;
        $row = (array)$this->getDatabase()->exec_SELECTgetSingleRow(
            'uid',
            'sys_category',
            '_migrated_uid=' . $oldCategoryUid . ' and _migrated_table="' . $this->oldCategoryTable
            . '" and deleted=0 and sys_language_uid=0'
        );
        if ($row['uid'] > 0) {
            $newCategoryUid = (int)$row['uid'];
        }
        return $newCategoryUid;
    }
}
