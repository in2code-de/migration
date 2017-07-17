<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

/**
 * Class GetUidOfDefaultLanguagePropertyHelper
 */
class GetUidOfDefaultLanguagePropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     */
    protected function manipulate()
    {
        $this->setProperty($this->getParentUid());
    }

    /**
     * @return int
     */
    protected function getParentUid(): int
    {
        $row = (array)$this->getDatabase()->exec_SELECTgetSingleRow(
            'uid',
            'sys_category',
            '_migrated_uid=' . $this->getPropertyFromOldRecord('uid') . ' and _migrated_table="' . $this->oldTable . '"'
        );
        return (int)$row['uid'];
    }
}
