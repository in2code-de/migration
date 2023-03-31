<?php
declare(strict_types=1);
namespace In2code\Migration\Migration;

use In2code\Migration\Exception\ConfigurationException;
use In2code\Migration\Migration\Importer\ImporterInterface;
use In2code\Migration\Migration\Log\Log;
use In2code\Migration\Migration\Migrator\MigratorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Start
{
    protected string $defaultConfiguration = 'EXT:migration/Configuration/Migration.php';

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws ConfigurationException
     */
    public function start(InputInterface $input, OutputInterface $output): void
    {
        GeneralUtility::makeInstance(Log::class)->setOutput($output);

        $configuration = $this->getConfiguration($input);
        $this->startMigrators($configuration);
    }

    /**
     * @param array $configuration
     * @return void
     * @throws ConfigurationException
     */
    protected function startMigrators(array $configuration): void
    {
        foreach ($this->getMigrationDefinitions($configuration) as $migration) {
            if (class_exists($migration['className']) === false) {
                throw new ConfigurationException(
                    'Class ' . $migration['className'] . ' does not exist',
                    1568213501
                );
            }
            if (
                is_subclass_of($migration['className'], MigratorInterface::class) === false &&
                is_subclass_of($migration['className'], ImporterInterface::class) === false
            ) {
                throw new ConfigurationException(
                    'Class ' . $migration['className'] . ' does not implement ' . MigratorInterface::class,
                    1568213506
                );
            }
            /** @var MigratorInterface $migration */
            $migration = GeneralUtility::makeInstance($migration['className'], $configuration);
            $migration->start();
        }
    }

    protected function getMigrationDefinitions(array $configuration): array
    {
        $key = $configuration['configuration']['key'];
        $migrations = [];
        foreach ($configuration['migrations'] as $migration) {
            if ($key === '' || in_array($key, $migration['keys'])) {
                $migrations[] = $migration;
            }
        }
        return $migrations;
    }

    /**
     * @param InputInterface $input
     * @return array
     * @throws ConfigurationException
     */
    protected function getConfiguration(InputInterface $input): array
    {
        $configurationPath = $input->getOption('configuration');
        if ($configurationPath === '') {
            $configurationPath = $this->defaultConfiguration;
        }
        $path = GeneralUtility::getFileAbsFileName($configurationPath);
        if (is_file($path) === false) {
            throw new ConfigurationException('File not found on ' . $path, 1569340892);
        }
        /** @noinspection PhpIncludeInspection */
        $configuration = require_once $path;
        if ($input->getOption('key') !== '') {
            $configuration['configuration']['key'] = $input->getOption('key');
        }
        if ($input->getOption('dryrun') !== true) {
            $configuration['configuration']['dryrun'] = (bool)$input->getOption('dryrun');
        }
        if ($input->getOption('limitToRecord') !== '0') {
            $configuration['configuration']['limitToRecord'] = $input->getOption('limitToRecord');
        }
        if ($input->getOption('limitToPage') !== 0) {
            $configuration['configuration']['limitToPage'] = $input->getOption('limitToPage');
        }
        if ($input->getOption('recursive') !== false) {
            $configuration['configuration']['recursive'] = (bool)$input->getOption('recursive');
        }
        return $configuration;
    }
}
