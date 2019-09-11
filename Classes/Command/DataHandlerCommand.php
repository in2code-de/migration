<?php
declare(strict_types=1);
namespace In2code\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DataHandlerCommand
 */
class DataHandlerCommand extends Command
{

    /**
     * Configure the command
     */
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

    /**
     * Make page actions like copy, move or delete from CLI
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $command = [];
        $command['pages'][(int)$input->getArgument('startPid')][$input->getArgument('action')]
            = (int)$input->getArgument('targetPid');
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->BE_USER = $GLOBALS['BE_USER'];
        $dataHandler->BE_USER->user['admin'] = 1;
        $dataHandler->userid = $GLOBALS['BE_USER']->user['uid'];
        $dataHandler->admin = true;
        $dataHandler->bypassAccessCheckForRecords = true;
        $dataHandler->copyTree = $input->getArgument('recursion');
        $dataHandler->deleteTree = true;
        $dataHandler->neverHideAtCopy = true;
        $dataHandler->start([], $command);
        $dataHandler->process_cmdmap();
        $output->writeln($this->getMessage($dataHandler));
        return 0;
    }

    /**
     * @param DataHandler $dataHandler
     * @return string
     */
    protected function getMessage(DataHandler $dataHandler)
    {
        $message = 'job successfully done!';
        if (!empty($dataHandler->errorLog)) {
            $message = 'Errors: ' . print_r($dataHandler->errorLog, true);
        }
        return $message;
    }
}
