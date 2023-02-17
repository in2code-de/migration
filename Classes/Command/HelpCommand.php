<?php
declare(strict_types=1);
namespace In2code\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class HelpCommand adds some helper commands to the system
 */
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
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $queryGenerator = GeneralUtility::makeInstance(QueryGenerator::class);
        $list = $queryGenerator->getTreeList((int)$input->getArgument('startPid'), 20, 0, 1);
        $output->writeln($list);
        return parent::SUCCESS;
    }
}
