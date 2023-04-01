<?php

declare(strict_types=1);
namespace In2code\Migration\Command;

use In2code\Migration\Exception\ConfigurationException;
use In2code\Migration\Migration\Start;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MigrateCommand extends Command
{
    public function configure()
    {
        $this->setDescription('Start migration script');
        $this->setDefinition(
            new InputDefinition([
                new InputOption('configuration', 'c', InputOption::VALUE_OPTIONAL, 'Path to configuration file', ''),
                new InputOption(
                    'key',
                    'k',
                    InputOption::VALUE_OPTIONAL,
                    'Which migration/importer should be called? E.g. "content". Empty value means all.',
                    ''
                ),
                new InputOption('dryrun', 'd', InputOption::VALUE_OPTIONAL, 'Test before real migration?', true),
                new InputOption(
                    'limitToRecord',
                    'l',
                    InputOption::VALUE_OPTIONAL,
                    '0=disable, 12=enable(all tables with uid 12), table:123(only table.uid=123)',
                    '0'
                ),
                new InputOption(
                    'limitToPage',
                    'p',
                    InputOption::VALUE_OPTIONAL,
                    '0=disable, 12=enable(all records with pid=12)',
                    0
                ),
                new InputOption(
                    'recursive',
                    'r',
                    InputOption::VALUE_OPTIONAL,
                    'true only enabled if limitToPage is set',
                    false
                ),
            ])
        );
    }

    /**
     * Example calls:
     *  ./vendor/bin/typo3 migration:migrate --configuration /var/www/site.org/migrationconfiguration.php
     *  ./vendor/bin/typo3 migration:migrate -c /var/www/site.org/migrationconfiguration.php
     *  ./vendor/bin/typo3 migration:migrate -c /configurationfile.php -k content -p 123 -d 0
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ConfigurationException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $starter = GeneralUtility::makeInstance(Start::class);
        $starter->start($input, $output);
        return parent::SUCCESS;
    }
}
