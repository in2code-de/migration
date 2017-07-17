<?php
namespace In2code\In2template\Migration\DatabaseScript;

/**
 * Class ParentCalendarCategoryDatabaseScript
 */
class ParentCalendarCategoryDatabaseScript extends AbstractDatabaseScript implements DatabaseScriptInterface
{

    /**
     * @var string
     */
    protected $title = 'Kalender';

    /**
     * @var string
     */
    protected $oldTable = 'tx_pthskaadmin_domain_model_appointmentcategory';

    /**
     * @return array
     */
    public function getSqlQueries(): array
    {
        if ($this->getCategoryParentUid() > 0) {
            return [];
        }
        $this->log->addMessage('Add parent calender category');
        $query = 'insert into sys_category (pid, title, _migrated, _migrated_uid, _migrated_table) '
            . 'values (2529, "' . $this->title . '", 1, 0, "tx_pthskaadmin_domain_model_appointmentcategory");';
        return [$query];
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
