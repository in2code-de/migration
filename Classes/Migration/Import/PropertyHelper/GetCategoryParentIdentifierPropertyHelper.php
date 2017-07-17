<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

/**
 * Class GetCategoryParentIdentifierPropertyHelper
 */
class GetCategoryParentIdentifierPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     */
    protected function manipulate()
    {
        $this->setProperty($this->getCategoryParentUid());
    }

    /**
     * @return int
     */
    protected function getCategoryParentUid(): int
    {
        $row = (array)$this->getDatabase()->exec_SELECTgetSingleRow(
            'uid',
            'sys_category',
            '_migrated_uid=0 and _migrated=1 and _migrated_table="' . $this->oldTable . '" and type = ""'
        );
        return (int)$row['uid'];
    }
}
