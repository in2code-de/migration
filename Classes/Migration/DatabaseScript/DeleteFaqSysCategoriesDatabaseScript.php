<?php
namespace In2code\In2template\Migration\DatabaseScript;

/**
 * Class DeleteFaqSysCategoriesDatabaseScript
 */
class DeleteFaqSysCategoriesDatabaseScript extends AbstractDatabaseScript implements DatabaseScriptInterface
{

    /**
     * @return array
     */
    public function getSqlQueries(): array
    {
        $this->log->addMessage('Removed old sys_categories in pid 8097');
        return ['update sys_category set deleted = 1 where pid = 8097;'];
    }
}
