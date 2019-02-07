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
        'be_users'
    ];

    /**
     * @param string $file Absolute path to a json export file
     * @param int $pid Page identifier to import new tree into (can also be 0 of course)
     * @return void
     */
    public function importCommand(string $file, int $pid)
    {
        $importService = $this->objectManager->get(ImportService::class, $file, $pid, $this->excludedTables);
        try {
            $importService->import();
            $message = 'success!';
        } catch (\Exception $exception) {
            $message = $exception->getMessage() . ' (' . $exception->getCode() . ')';
        }
        $this->outputLine($message);
    }
}
