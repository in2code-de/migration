<?php
declare(strict_types=1);
namespace In2code\Migration\Command;

use In2code\Migration\Utility\ObjectUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\QueryGenerator;

/**
 * Class HelpCommand adds some helper commands to the system
 */
class HelpCommand extends Command
{

    /**
     * Configure the command
     */
    public function configure()
    {
        $this->setDescription('Make page actions like copy, move or delete from CLI');
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
        $queryGenerator = ObjectUtility::getObjectManager()->get(QueryGenerator::class);
        $list = $queryGenerator->getTreeList((int)$input->getArgument('startPid'), 20, 0, 1);
        $output->writeln($list);
        return 0;
    }
}
