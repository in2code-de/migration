<?php
declare(strict_types=1);
namespace In2code\Migration\Command;

use Doctrine\DBAL\Driver\Exception as ExceptionDbalDriver;
use Doctrine\DBAL\Exception as ExceptionDbal;
use In2code\Migration\Service\TreeService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class HelpCommand extends Command
{
    public function configure()
    {
        $this->setDescription('Returns a list of the current pid and all sub-pids');
        $this->addArgument('startPid', InputArgument::REQUIRED, 'Start page identifier');
    }

    /**
     * Returns a list of the current pid and all sub-pids (could be useful for further database operations)
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $treeService = GeneralUtility::makeInstance(TreeService::class);
        $output->writeln(implode(',', $treeService->getAllSubpageIdentifiers((int)$input->getArgument('startPid'))));
        return parent::SUCCESS;
    }
}
