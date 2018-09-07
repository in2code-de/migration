<?php

namespace In2code\Migration\Command;

use In2code\Migration\Migration\Starter;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class MainMigrationCommandController
 */
class MigrateCommandController extends CommandController
{

    /**
     * @param string $key "migrationClassKey": Define which migration or importer should be called - e.g. "content"
     * @param bool $dryrun Test how many records could be imported (with "--dryrun=0")
     * @param string $limitToRecord 0=disable, 12=enable(all tables), table:123(only table.uid=123)
     * @param int $limitToPage 0=disable, 12=enable(all records with pid=12)
     * @param bool $recursive true has only an effect if limitToPage is set
     * @return void
     * @throws \Exception
     * @cli
     */
    public function startCommand(
        string $key,
        bool $dryrun = true,
        $limitToRecord = '0',
        int $limitToPage = 0,
        bool $recursive = false
    ) {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $starter = $this->objectManager->get(Starter::class, $this->output, 'content');
        $starter->start($key, $dryrun, $limitToRecord, $limitToPage, $recursive);
    }
}
