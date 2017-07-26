<?php
namespace In2code\In2template\Command;

use In2code\In2template\Migration\Starter;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class MainMigrationCommandController
 */
class MainMigrationCommandController extends CommandController
{

    /**
     * Migrate pages.
     *
     * @param bool $dryrun Test how many records could be imported (with "--dryrun=0")
     * @param string $limitToRecord 0=disable, 12=enable(all tables), table:123(only table.uid=123)
     * @param int $limitToPage 0=disable, 12=enable(all records with pid=12)
     * @param bool $recursive true has only an effect if limitToPage is set
     * @return void
     */
    public function migratePagesCommand($dryrun = true, $limitToRecord = '0', $limitToPage = 0, $recursive = false)
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $starter = $this->objectManager->get(Starter::class, $this->output, 'page');
        $starter->start($dryrun, $limitToRecord, $limitToPage, $recursive);
    }

    /**
     * Migrate content.
     *
     * @param bool $dryrun Test how many records could be imported (with "--dryrun=0")
     * @param string $limitToRecord 0=disable, 12=enable(all tables), table:123(only table.uid=123)
     * @param int $limitToPage 0=disable, 12=enable(all records with pid=12)
     * @param bool $recursive true has only an effect if limitToPage is set
     * @return void
     */
    public function migrateContentCommand($dryrun = true, $limitToRecord = '0', $limitToPage = 0, $recursive = false)
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $starter = $this->objectManager->get(Starter::class, $this->output, 'content');
        $starter->start($dryrun, $limitToRecord, $limitToPage, $recursive);
    }

    /**
     * Migrate existing faq.
     *
     * @param bool $dryrun Test how many records could be imported (with "--dryrun=0")
     * @param string $limitToRecord 0=disable, 12=enable(all tables), table:123(only table.uid=123)
     * @param int $limitToPage 0=disable, 12=enable(all records with pid=12)
     * @param bool $recursive true has only an effect if limitToPage is set
     * @return void
     */
    public function migrateFaqCommand($dryrun = true, $limitToRecord = '0', $limitToPage = 0, $recursive = false)
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $starter = $this->objectManager->get(Starter::class, $this->output, 'faq');
        $starter->start($dryrun, $limitToRecord, $limitToPage, $recursive);
    }

    /**
     * Migrate existing redirects.
     *
     * @param bool $dryrun Test how many records could be imported (with "--dryrun=0")
     * @param string $limitToRecord 0=disable, 12=enable(all tables), table:123(only table.uid=123)
     * @param int $limitToPage 0=disable, 12=enable(all records with pid=12)
     * @param bool $recursive true has only an effect if limitToPage is set
     * @return void
     */
    public function migrateRedirectsCommand($dryrun = true, $limitToRecord = '0', $limitToPage = 0, $recursive = false)
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $starter = $this->objectManager->get(Starter::class, $this->output, 'redirect');
        $starter->start($dryrun, $limitToRecord, $limitToPage, $recursive);
    }
}
