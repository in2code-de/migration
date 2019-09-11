<?php
declare(strict_types=1);
namespace In2code\Migration\Command;

use In2code\Migration\Migration\Starter;
use In2code\Migration\Utility\ObjectUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MigrateCommand
 */
class MigrateCommand extends Command
{

    /**
     * Configure the command
     */
    public function configure()
    {
        $this->setDescription('Start migration script');
        $this->addArgument('key', InputArgument::REQUIRED, 'Which migration/importer should be called? E.g. "content"');
        $this->addArgument('dryrun', InputArgument::OPTIONAL, 'Test before real migration?', true);
        $this->addArgument(
            'limitToRecord',
            InputArgument::OPTIONAL,
            '0=disable, 12=enable(all tables with uid 12), table:123(only table.uid=123)',
            '0'
        );
        $this->addArgument('limitToPage', InputArgument::OPTIONAL, '0=disable, 12=enable(all records with pid=12)', 0);
        $this->addArgument('recursive', InputArgument::OPTIONAL, 'true only enabled if limitToPage is set', false);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $starter = ObjectUtility::getObjectManager()->get(Starter::class, $output, 'content');
        $starter->start(
            $input->getArgument('key'),
            (bool)$input->getArgument('dryrun'),
            $input->getArgument('limitToRecord'),
            (int)$input->getArgument('limitToPage'),
            (bool)$input->getArgument('recursive')
        );
        $output->writeln('finished');
        return 0;
    }
}
