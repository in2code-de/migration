<?php
namespace In2code\Migration\Command;

use In2code\Migration\Service\ImportService;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class ImportJsonCommandController
 */
class ImportJsonCommandController extends CommandController
{

    /**
     * Define which tables shouldn't be imported (pages is the only table that must be included)
     *
     * @var array
     */
    protected $excludedTables = [
        'sys_category_record_mm'
    ];

    /**
     * Check if the file is already existing (compare path and name) and decide of it should be overwritten or not
     *
     * @var bool
     */
    protected $overwriteFiles = false;

    /**
     * Importer command to import json export files into a current database. New uids will be inserted for records.
     * Note: At the moment only sys_file_reference is supported as mm table (e.g. no sys_category_record_mm support)
     *
     * Example CLI call: ./vendor/bin/typo3cms importjson:import /home/user/export.json 123
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
            $importService->import();
            $message = 'success!';
        } catch (\Exception $exception) {
            $message = $exception->getMessage() . ' (' . $exception->getCode() . ')';
        }
        $this->outputLine($message);
    }
}
