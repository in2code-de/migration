<?php
namespace In2code\In2template\Command;

use In2code\In2template\Migration\Starter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class MainMigrationCommandController
 */
class MainMigrationCommandController extends CommandController
{

    /**
     * Migrate database with some defined queries.
     *
     * @return void
     */
    public function migrateDatabaseCommand()
    {
        $starter = $this->getObjectManager()->get(Starter::class, $this->output, 'database');
        $starter->start(false, '0', 0, false);
    }

    /**
     * Migrate existing news.
     *
     * @param bool $dryrun Test how many records could be imported (with "--dryrun=0")
     * @param string $limitToRecord 0=disable, 12=enable(all tables), table:123(only table.uid=123)
     * @param int $limitToPage 0=disable, 12=enable(all records with pid=12)
     * @param bool $recursive true has only an effect if limitToPage is set
     * @return void
     */
    public function migrateNewsCommand($dryrun = true, $limitToRecord = '0', $limitToPage = 0, $recursive = false)
    {
        $starter = $this->getObjectManager()->get(Starter::class, $this->output, 'news');
        $starter->start($dryrun, $limitToRecord, $limitToPage, $recursive);
    }

    /**
     * Migrate existing be_users and be_groups.
     *
     * @param bool $dryrun Test how many records could be imported (with "--dryrun=0")
     * @param string $limitToRecord 0=disable, 12=enable(all tables), table:123(only table.uid=123)
     * @return void
     */
    public function migrateBackendUsersCommand($dryrun = true, $limitToRecord = '0')
    {
        $starter = $this->getObjectManager()->get(Starter::class, $this->output, 'backenduser');
        $starter->start($dryrun, $limitToRecord, 0, true);
    }

    /**
     * Migrate pages and tt_content values
     *
     * @param bool $dryrun Test how many records could be imported (with "--dryrun=0")
     * @param string $limitToRecord 0=disable, 12=enable(all tables), table:123(only table.uid=123)
     * @param int $limitToPage 0=disable, 12=enable(all records with pid=12)
     * @param bool $recursive true has only an effect if limitToPage is set
     * @return void
     */
    public function migratePagesAndContentCommand(
        $dryrun = true,
        $limitToRecord = '0',
        $limitToPage = 0,
        $recursive = false
    ) {
        $starter = $this->getObjectManager()->get(Starter::class, $this->output, 'content');
        $starter->start($dryrun, $limitToRecord, $limitToPage, $recursive);
    }

    /**
     * Migrate existing news.
     *
     * @param bool $dryrun Test how many records could be imported (with "--dryrun=0")
     * @param string $limitToRecord 0=disable, 12=enable(all tables), table:123(only table.uid=123)
     * @param int $limitToPage 0=disable, 12=enable(all records with pid=12)
     * @param bool $recursive true has only an effect if limitToPage is set
     * @return void
     */
    public function migrateCalendarCommand($dryrun = true, $limitToRecord = '0', $limitToPage = 0, $recursive = false)
    {
        $starter = $this->getObjectManager()->get(Starter::class, $this->output, 'calendar');
        $starter->start($dryrun, $limitToRecord, $limitToPage, $recursive);
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager(): ObjectManager
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }
}
