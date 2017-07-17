<?php
namespace In2code\In2template\Migration\DatabaseScript;

/**
 * Class DisableTypoScriptTemplatesDatabaseScript
 */
class DisableTypoScriptTemplatesDatabaseScript extends AbstractDatabaseScript implements DatabaseScriptInterface
{

    /**
     * @return array
     */
    public function getSqlQueries(): array
    {
        $this->log->addMessage('Turned all old TypoScript templates off');
        return ['update sys_template set hidden = 1 where 1 = 1;'];
    }
}
