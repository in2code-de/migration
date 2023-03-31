<?php
declare(strict_types=1);
namespace In2code\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataHandlerCommand extends Command
{
    public function configure()
    {
        $this->setDescription('Make page actions like copy, move or delete from CLI');
        $this->addArgument('startPid', InputArgument::REQUIRED, 'Which page should be handled');
        $this->addArgument('action', InputArgument::OPTIONAL, 'Which action? "copy", "move" or "delete"', 'copy');
        $this->addArgument(
            'targetPid',
            InputArgument::OPTIONAL,
            'Target pid where to paste. 1 => first subpage in 1, -1 => after 1',
            1
        );
        $this->addArgument(
            'recursion',
            InputArgument::OPTIONAL,
            'Recursion depth. 0 => Just current page, 1 => Current and subpages, etc...',
            0
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $command = [];
        $command['pages'][(int)$input->getArgument('startPid')][$input->getArgument('action')]
            = (int)$input->getArgument('targetPid');
        Bootstrap::initializeBackendAuthentication();
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->bypassAccessCheckForRecords = true;
        $dataHandler->copyTree = $input->getArgument('recursion');
        $dataHandler->neverHideAtCopy = true;
        $dataHandler->start([], $command);
        $dataHandler->process_cmdmap();
        $output->writeln($this->getMessage($dataHandler));
        return parent::SUCCESS;
    }

    protected function getMessage(DataHandler $dataHandler): string
    {
        $message = 'job successfully done!';
        if (!empty($dataHandler->errorLog)) {
            $message = 'Errors: ' . print_r($dataHandler->errorLog, true);
        }
        return $message;
    }
}
