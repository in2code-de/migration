<?php
namespace In2code\Migration\Command;

use Doctrine\DBAL\DBALException;
use In2code\Migration\Service\ExportService;
use In2code\Migration\Service\ImportService;
use In2code\Migration\Utility\DatabaseUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class PortCommandController
 * offers own json based import and export commands for TYPO3 page-trees to fit the need to insert huge page trees into
 * existing TYPO3 instances.
 */
class PortCommandController extends CommandController
{

    /**
     * Excluded tables for im- and export
     *
     * @var array
     */
    protected $excludedTables = [
        'be_groups',
        'be_users',
        'sys_language',
        'sys_log',
        'sys_news',
        'sys_domain',
        'sys_template',
        'sys_note',
        'sys_history',
        'sys_file_storage',
        'tx_extensionmanager_domain_model_extension',
        'tx_extensionmanager_domain_model_repository',
        'sys_category_record_mm'
    ];

    /**
     * Import: Check if the file is already existing (compare path and name - no size or date)
     * and decide of it should be overwritten or not
     *
     * @var bool
     */
    protected $overwriteFiles = false;

    /**
     * Own export command to export whole pagetrees with all records to a file which contains a json and can be
     * imported again with a different import command.
     * Example CLI call: ./vendor/bin/typo3cms port:export 123 > /home/user/export.json
     *
     * @param int $pid
     * @param int $recursive
     * @return void
     * @cli
     * @throws DBALException
     */
    public function exportCommand(int $pid, int $recursive = 99)
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $exportService = $this->objectManager->get(ExportService::class, $pid, $recursive, $this->excludedTables);
        $this->outputLine($exportService->export());
    }

    /**
     * Importer command to import json export files into a current database. New uids will be inserted for records.
     * Note: At the moment only sys_file_reference is supported as mm table (e.g. no sys_category_record_mm support)
     *
     * Example CLI call: ./vendor/bin/typo3cms port:import /home/user/export.json 123
     *
     * @param string $file Absolute path to a json export file
     * @param int $pid Page identifier to import new tree into (can also be 0 for an import into root)
     * @return void
     */
    public function importCommand(string $file, int $pid)
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $importService = $this->objectManager->get(
            ImportService::class,
            $file,
            $pid,
            $this->excludedTables,
            $this->overwriteFiles
        );
        try {
            $this->checkTarget($pid);
            $importService->import();
            $message = 'success!';
        } catch (\Exception $exception) {
            $message = $exception->getMessage() . ' (Errorcode ' . $exception->getCode() . ')';
        }
        $this->outputLine($message);
    }

    /**
     * @param int $pid
     * @return void
     */
    protected function checkTarget(int $pid)
    {
        if ($pid > 0 && $this->isPageExisting($pid) === false) {
            throw new \LogicException('Target page with uid ' . $pid . ' is not existing', 1549535363);
        }
    }

    /**
     * @param int $pid
     * @return bool
     */
    protected function isPageExisting(int $pid): bool
    {
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable('pages', true);
        return (int)$queryBuilder
                ->select('uid')
                ->from('pages')
                ->where('uid=' . (int)$pid)
                ->execute()
                ->fetchColumn(0) > 0;
    }
}
