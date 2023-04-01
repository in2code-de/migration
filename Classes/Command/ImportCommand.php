<?php

declare(strict_types=1);
namespace In2code\Migration\Command;

use Doctrine\DBAL\Exception as ExceptionDbal;
use In2code\Migration\Exception\ConfigurationException;
use In2code\Migration\Port\Import;
use In2code\Migration\Utility\DatabaseUtility;
use LogicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ImportCommand
 * offers own json based import command for TYPO3 page-trees to fit the need to insert large page trees into
 * existing TYPO3 instances.
 */
class ImportCommand extends AbstractPortCommand
{
    public function configure()
    {
        $description = 'Importer command to import json export files into a current database. ' .
            'New uids will be inserted for records.' .
            'Note: At the moment only sys_file_reference is supported as mm table ' .
            '(e.g. no sys_category_record_mm support)';
        $this->setDescription($description);
        $this->addArgument('file', InputArgument::REQUIRED, 'Absolute path to a json export file');
        $argumentDescription = 'Page identifier to import new tree into (can also be 0 for an import into root)';
        $this->addArgument('pid', InputArgument::REQUIRED, $argumentDescription);
        $this->addArgument(
            'configuration',
            InputArgument::OPTIONAL,
            'Path to configuration file',
            self::CONFIGURATION_PATH
        );
    }

    /**
     * Importer command to import json export files into a current database. New uids will be inserted for records.
     * Note: At the moment only sys_file_reference is supported as mm table (e.g. no sys_category_record_mm support)
     *
     * Example CLI call: ./vendor/bin/typo3 migration:import /home/user/export.json 123
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ConfigurationException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $importService = GeneralUtility::makeInstance(
            Import::class,
            $input->getArgument('file'),
            (int)$input->getArgument('pid'),
            $this->getCompleteConfiguration($input->getArgument('configuration'))
        );
        try {
            $this->checkTarget((int)$input->getArgument('pid'));
            $pages = $importService->import();
            $message = 'Success. ' . $pages . ' new pages imported!';
        } catch (Throwable $exception) {
            $message = $exception->getMessage() . ' (Errorcode ' . $exception->getCode() . ')';
        }
        $output->writeln($message);
        return parent::SUCCESS;
    }

    protected function checkTarget(int $pid)
    {
        if ($pid > 0 && $this->isPageExisting($pid) === false) {
            throw new LogicException('Target page with uid ' . $pid . ' is not existing', 1549535363);
        }
    }

    /**
     * @param int $pid
     * @return bool
     * @throws ExceptionDbal
     */
    protected function isPageExisting(int $pid): bool
    {
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable('pages', true);
        return (int)$queryBuilder
            ->select('uid')
            ->from('pages')
            ->where('uid=' . (int)$pid)
            ->execute()
            ->fetchOne() > 0;
    }
}
