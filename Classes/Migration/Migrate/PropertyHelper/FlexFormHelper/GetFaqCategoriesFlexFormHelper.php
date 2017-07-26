<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper\FlexFormHelper;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class GetFaqCategoriesFlexFormHelper
 */
class GetFaqCategoriesFlexFormHelper extends AbstractFlexFormHelper implements FlexFormHelperInterface
{

    /**
     * @return string
     */
    public function getVariable(): string
    {
        $ffConfiguration = $this->getFlexFormArray();
        $categories = [];
        if (!empty($ffConfiguration['switchableControllerActions'])) {
            if ($ffConfiguration['switchableControllerActions'] === 'Question->list') {
                $oldCategoryList = $ffConfiguration['settings']['category'];
                $categories = $this->convertOldSysCategoryListIntoNewList($oldCategoryList);
            }
            if ($ffConfiguration['switchableControllerActions'] === 'Question->productview') {
                $oldCategoryList = $ffConfiguration['settings']['product'];
                $categories = $this->convertOldProductCategoryListIntoNewList($oldCategoryList);
            }
        }
        return implode(',', $categories);
    }

    /**
     * @param string $categoryList
     * @return array
     */
    protected function convertOldSysCategoryListIntoNewList(string $categoryList): array
    {
        $categories = GeneralUtility::trimExplode(',', $categoryList, true);
        $newCategories = [];
        foreach ($categories as $category) {
            $row = (array)$this->getDatabase()->exec_SELECTgetSingleRow(
                'uid',
                'tx_in2faq_domain_model_category',
                '_migrated_uid=' . $category . ' and _migrated_table = "sys_category"'
            );
            if (!empty($row['uid'])) {
                $newCategories[] = (int)$row['uid'];
            }
        }
        return $newCategories;
    }

    /**
     * @param string $categoryList
     * @return array
     */
    protected function convertOldProductCategoryListIntoNewList(string $categoryList): array
    {
        $categories = GeneralUtility::trimExplode(',', $categoryList, true);
        $newCategories = [];
        foreach ($categories as $category) {
            $row = (array)$this->getDatabase()->exec_SELECTgetSingleRow(
                'uid',
                'tx_in2faq_domain_model_category',
                '_migrated_uid=' . $category . ' and _migrated_table = "tx_udgmvproducts_domain_model_product"'
            );
            if (!empty($row['uid'])) {
                $newCategories[] = (int)$row['uid'];
            }
        }
        return $newCategories;
    }
}
