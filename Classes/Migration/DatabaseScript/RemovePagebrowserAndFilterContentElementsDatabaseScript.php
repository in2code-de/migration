<?php
namespace In2code\In2template\Migration\DatabaseScript;

/**
 * Class RemovePagebrowserAndFilterContentElementsDatabaseScript
 */
class RemovePagebrowserAndFilterContentElementsDatabaseScript extends AbstractDatabaseScript implements DatabaseScriptInterface
{

    /**
     * @return array
     */
    public function getSqlQueries(): array
    {
        $this->log->addMessage('Remove all userlisting pagebrowser and filter content elements');
        $queries = [
            'update tt_content set deleted = 1 where pi_flexform like ' .
            '\'%<value index="vDEF">Filterbox-&gt;show;Filterbox-&gt;submit;Filterbox-&gt;reset;' .
            'Filterbox-&gt;resetFilter</value>%\'',
            'update tt_content set deleted = 1 where pi_flexform like ' .
            '\'%<value index="vDEF">Pager-&gt;show</value>%\''
        ];
        return $queries;
    }
}
