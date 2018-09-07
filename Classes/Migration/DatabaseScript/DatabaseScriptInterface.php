<?php
namespace In2code\Migration\Migration\DatabaseScript;

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
