<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

/**
 * Class CreateSortingNumberFromPropertyPropertyHelper
 */
class CreateSortingNumberFromPropertyPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     */
    protected function manipulate()
    {
        $sortingArray = $this->getAllOldCategoriesSortedByProperty($this->getConfigurationByKey('property'));
        $sorting = 10000;
        if (array_key_exists($this->getPropertyFromOldRecord('uid'), $sortingArray)) {
            $sorting = $sortingArray[$this->getPropertyFromOldRecord('uid')];
        } else {
            $this->log->addError('Category not sortable: ' . $this->getPropertyFromOldRecord('title'));
        }
        $this->setProperty($sorting);
    }

    /**
     * @param string $property
     * @return array
     */
    protected function getAllOldCategoriesSortedByProperty(string $property): array
    {
        $rows = $this->database->exec_SELECTgetRows('uid', 'tt_news_cat', 'deleted = 0', '', $property);
        $categories = [];
        $sorting = 100;
        foreach ($rows as $row) {
            $categories[$sorting] = $row['uid'];
            $sorting += 100;
        }
        return array_flip($categories);
    }
}
