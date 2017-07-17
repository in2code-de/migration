<?php
namespace In2code\In2template\Migration\DatabaseScript;

/**
 * Interface DatabaseScriptInterface
 */
interface DatabaseScriptInterface
{

    /**
     * @return array
     */
    public function getSqlQueries(): array;
}
