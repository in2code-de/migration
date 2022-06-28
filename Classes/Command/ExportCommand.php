<?php
declare(strict_types=1);
namespace In2code\Migration\Command;

use Doctrine\DBAL\DBALException;
use In2code\Migration\Exception\ConfigurationException;
use In2code\Migration\Exception\JsonCanNotBeCreatedException;
use In2code\Migration\Port\Export;
use In2code\Migration\Utility\ObjectUtility;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;

/**
 * Class ExportCommand
 * offers own json based export command for TYPO3 page-trees to fit the need to insert large page trees into
 * existing TYPO3 instances.
 */
class ExportCommand extends AbstractPortCommand
{
    /**
     * Configure the command
     */
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

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        if ($input->hasArgument('pid') && $input->getArgument('pid') == 0) {
            throw new \RuntimeException('Exporting page 0 is not supported', 1656413814095);
        }
    }

    /**
     * Own export command to export whole pagetrees with all records to a file which contains a json and can be
     * imported again with a different import command.
     * Example CLI call: ./vendor/bin/typo3cms migration:export 123 > /home/user/export.json
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws DBALException
     * @throws InvalidSlotException
     * @throws InvalidSlotReturnException
     * @throws ConfigurationException
     * @throws JsonCanNotBeCreatedException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $exportService = ObjectUtility::getObjectManager()->get(
            Export::class,
            (int)$input->getArgument('pid'),
            (int)$input->getArgument('recursive'),
            $this->getCompleteConfiguration($input->getArgument('configuration'))
        );
        $output->writeln($exportService->export());
        return 0;
    }
}
