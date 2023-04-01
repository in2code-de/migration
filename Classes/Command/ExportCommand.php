<?php

declare(strict_types=1);
namespace In2code\Migration\Command;

use Doctrine\DBAL\Driver\Exception as ExceptionDbalDriver;
use Doctrine\DBAL\Exception as ExceptionDbal;
use In2code\Migration\Exception\ConfigurationException;
use In2code\Migration\Exception\JsonCanNotBeCreatedException;
use In2code\Migration\Port\Export;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ExportCommand
 * offers own json based export command for TYPO3 page-trees to fit the need to insert large page trees into
 * existing TYPO3 instances.
 */
class ExportCommand extends AbstractPortCommand
{
    public function configure()
    {
        $description = 'Own export command to export whole pagetrees with all records to a file ' .
            'which contains a json and can be imported again with a different import command.';
        $this->setDescription($description);
        $this->addArgument('pid', InputArgument::REQUIRED, 'Start page identifier');
        $this->addArgument('recursive', InputArgument::OPTIONAL, 'Recursive level', 99);
        $this->addArgument(
            'configuration',
            InputArgument::OPTIONAL,
            'Path to configuration file',
            self::CONFIGURATION_PATH
        );
    }

    /**
     * Own export command to export whole pagetrees with all records to a file which contains a json and can be
     * imported again with a different import command.
     * Example CLI call: ./vendor/bin/typo3 migration:export 123 > /home/user/export.json
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ConfigurationException
     * @throws ExceptionDbalDriver
     * @throws JsonCanNotBeCreatedException
     * @throws ExceptionDbal
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $exportService = GeneralUtility::makeInstance(
            Export::class,
            (int)$input->getArgument('pid'),
            (int)$input->getArgument('recursive'),
            $this->getCompleteConfiguration($input->getArgument('configuration'))
        );
        $output->writeln($exportService->export());
        return parent::SUCCESS;
    }
}
