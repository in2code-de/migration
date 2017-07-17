<?php
namespace In2code\In2template\Migration\DatabaseScript;

/**
 * Class AddMainTyposcriptTemplateDatabaseScript
 */
class AddMainTyposcriptTemplateDatabaseScript extends AbstractDatabaseScript implements DatabaseScriptInterface
{

    /**
     * @var string
     */
    protected $description = 'Main Template in2code';

    /**
     * @return array
     */
    public function getSqlQueries(): array
    {
        $this->log->addMessage('Added new main template');
        $queries = [
            'delete from sys_template where description = "' . $this->description . '"',
            'INSERT INTO sys_template (pid, title, root, include_static_file, description) VALUES ' .
            '(1, \'Main Template\', 1, \'EXT:in2template/Configuration/TypoScript\', \'' . $this->description . '\');'
        ];
        return $queries;
    }
}
