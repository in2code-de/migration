<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

/**
 * Class FaqRelationsPropertyHelper
 */
class FaqProductRelationsPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function manipulate()
    {
        $oldFaqUid = (int)$this->getPropertyFromOldRecord('uid_local');
        $newFaqUid = $this->changeOldToNewFaqUid($oldFaqUid);
        $this->setPropertyByName('uid_local', $newFaqUid);

        $oldProductUid = (int)$this->getPropertyFromOldRecord('uid_foreign');
        $newCategoryUid = $this->changeOldToNewProductUid($oldProductUid);
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
     * @param int $oldProductUid
     * @return int
     */
    protected function changeOldToNewProductUid(int $oldProductUid): int
    {
        $row = (array)$this->database->exec_SELECTgetSingleRow(
            'uid',
            'tx_in2faq_domain_model_category',
            '_migrated_uid=' . $oldProductUid . ' and _migrated_table="tx_udgmvproducts_domain_model_product"'
        );
        if (!empty($row['uid'])) {
            return (int)$row['uid'];
        }
        return 0;
    }
}
