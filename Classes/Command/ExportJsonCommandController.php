<?php
namespace In2code\Migration\Command;

use Doctrine\DBAL\DBALException;
use In2code\Migration\Service\ExportService;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class ExportJsonCommandController
 */
class ExportJsonCommandController extends CommandController
{

    /**
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
        'tx_extensionmanager_domain_model_extension',
        'tx_extensionmanager_domain_model_repository'
    ];

    /**
     * Own export command to export whole pagetrees with all records to a file which contains a json and can be
     * imported again with a different import command.
     *
     * @param int $pid
     * @param int $recursive
     * @return void
     * @cli
     * @throws DBALException
     */
    public function exportCommand(int $pid, int $recursive = 99)
    {
        $exportService = $this->objectManager->get(ExportService::class, $pid, $recursive, $this->excludedTables);
        $this->outputLine($exportService->export());
    }
}
