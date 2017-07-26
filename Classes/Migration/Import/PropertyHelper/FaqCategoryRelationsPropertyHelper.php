<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

/**
 * Class FaqCategoryRelationsPropertyHelper
 */
class FaqCategoryRelationsPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function manipulate()
    {
        $oldFaqUid = (int)$this->getPropertyFromOldRecord('uid_foreign');
        $newFaqUid = $this->changeOldToNewFaqUid($oldFaqUid);
        $this->setPropertyByName('uid_local', $newFaqUid);

        $oldCategoryUid = (int)$this->getPropertyFromOldRecord('uid_local');
        $newCategoryUid = $this->changeOldToNewCategoryUid($oldCategoryUid);
        $this->setPropertyByName('uid_foreign', $newCategoryUid);
    }

    /**
     * @param int $oldFaqUid
     * @return int
     */
    protected function changeOldToNewFaqUid(int $oldFaqUid): int
    {
        $row = (array)$this->database->exec_SELECTgetSingleRow(
            'uid',
            'tx_in2faq_domain_model_question',
            '_migrated_uid=' . $oldFaqUid
        );
        if (!empty($row['uid'])) {
            return (int)$row['uid'];
        }
        return 0;
    }

    /**
     * @param int $oldCategoryUid
     * @return int
     */
    protected function changeOldToNewCategoryUid(int $oldCategoryUid): int
    {
        $row = (array)$this->database->exec_SELECTgetSingleRow(
            'uid',
            'tx_in2faq_domain_model_category',
            '_migrated_uid=' . $oldCategoryUid . ' and _migrated_table="sys_category"'
        );
        if (!empty($row['uid'])) {
            return (int)$row['uid'];
        }
        return 0;
    }
}
