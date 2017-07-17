<?php
namespace In2code\In2template\Migration\DatabaseScript;

/**
 * Class MediaReferencesForContentElementsDatabaseScript
 */
class MediaReferencesForContentElementsDatabaseScript extends AbstractDatabaseScript implements DatabaseScriptInterface
{

    /**
     * @return array
     */
    public function getSqlQueries(): array
    {
        $sql = 'update sys_file_reference ';
        $sql .= 'set fieldname = "assets" where tablenames = "tt_content" and fieldname = "image";';
        $this->log->addMessage('Complete Table sys_file_reference updated');
        return [$sql];
    }
}
