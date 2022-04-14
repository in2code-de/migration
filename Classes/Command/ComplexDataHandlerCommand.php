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
class ComplexDataHandlerCommand extends Command
{

    /**
     * Configure the command
     */
    public function configure()
    {
        $this->setDescription('Complex Datahandler für CLI - Handle with care!' . chr(10) .
            'Please consult the documentaion before using this command:' . chr(10) .
            'https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Typo3CoreEngine/Index.html' . chr(10) .
            'and' . chr(10) .
            'https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Typo3CoreEngine/Database/Index.html' . chr(10) .
            chr(10) .
            'Notice! Every command is running as admin and without access checks!');
        $this->addArgument('table', InputArgument::REQUIRED, 'Name of the database table');
        $this->addArgument('uid', InputArgument::REQUIRED, 'The UID of the record that is manipulated. This is always an integer');
        $this->addArgument('action', InputArgument::REQUIRED, 'The command type you want to execute.');
        $this->addArgument(
            'value',
            InputArgument::OPTIONAL,
            'The value for the command. Must be a JSON oder Integer > 0. E. g. {"field":"images", "action":"synchronize", "language: 1}',
            ''
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
        if (intval($input->getArgument('value'))) {
            $value = intval($input->getArgument('value'));
        } else {
            try {
                $value = json_decode($input->getArgument('value'), TRUE);
            } catch(Exception $error) {
                $output->writeln($error);
                return 1;
            }
        }

        if ($value === 0 || !(is_array($value))) {
            $output->writeln('value is 0 or can not be decoded as array');
            return 1;
        }

        $command[$input->getArgument('table')][(int)$input->getArgument('uid')][$input->getArgument('action')]
            = $value;
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
