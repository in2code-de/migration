<?php
declare(strict_types=1);
namespace In2code\Migration\Migration;

use In2code\Migration\Migration\Exception\ConfigurationException;
use In2code\Migration\Migration\Log\Log;
use In2code\Migration\Utility\ObjectUtility;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Start
 */
class Start
{
    /**
     * @var string
     */
    protected $defaultConfiguration = 'EXT:migration/Configuration/Migration.php';

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws ConfigurationException
     */
    public function start(InputInterface $input, OutputInterface $output): void
    {
        ObjectUtility::getObjectManager()->get(Log::class)->setOutput($output);

        $configuration = $this->getConfiguration($input);
        foreach ($this->getMigrationDefinitions($configuration) as $migration) {
            if (class_exists($migration['className']) === false) {
                throw new ConfigurationException(
                    'Class ' . $migration['className'] . ' does not exist',
                    1568213501
                );
            }
            if (is_subclass_of($migration['className'], MigrationInterface::class) === false) {
                throw new ConfigurationException(
                    'Class ' . $migration['className'] . ' does not implement ' . MigrationInterface::class,
                    1568213506
                );
            }
            /** @var MigrationInterface $migration */
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $migration = ObjectUtility::getObjectManager()->get($migration['className'], $configuration);
            $migration->start();
        }
    }

    /**
     * @param array $configuration
     * @return array
     */
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
     */
    protected function getConfiguration(InputInterface $input): array
    {
        $configurationPath = $input->getOption('configuration');
        if ($configurationPath === '') {
            $configurationPath = $this->defaultConfiguration;
        }
        $path = GeneralUtility::getFileAbsFileName($configurationPath);
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
