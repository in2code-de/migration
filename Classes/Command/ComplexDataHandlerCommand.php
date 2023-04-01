<?php

declare(strict_types=1);
namespace In2code\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ComplexDataHandlerCommand extends Command
{
    public function configure()
    {
        $this->setDescription('Complex Datahandler für CLI - Handle with care!' . chr(10) .
            'Please consult the documentation before using this command:' . chr(10) .
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
            'The value for the command. Must be a JSON oder Integer > 0. E. g. {"field":"images", "action":"synchronize", "language": 1}',
            ''
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
        if ((int)$input->getArgument('value') > 0) {
            $value = (int)($input->getArgument('value'));
        } else {
            try {
                $value = json_decode($input->getArgument('value'), true);
            } catch (Throwable $error) {
                $output->writeln($error);
                return parent::FAILURE;
            }
        }

        if ((is_array($value)) === false) {
            $output->writeln('value is 0 or can not be decoded as array');
            return parent::FAILURE;
        }

        $command[$input->getArgument('table')][(int)$input->getArgument('uid')][$input->getArgument('action')]
            = $value;
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
