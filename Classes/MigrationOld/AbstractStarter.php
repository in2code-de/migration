<?php
namespace In2code\Migration\MigrationOld;

use In2code\Migration\MigrationOld\DatabaseScript\DatabaseScriptInterface;
use In2code\Migration\MigrationOld\Import\AbstractImporter;
use In2code\Migration\MigrationOld\Import\ImporterInterface;
use In2code\Migration\MigrationOld\Migrate\MigratorInterface;
use In2code\Migration\Utility\ObjectUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\ConsoleOutput;

/**
 * Class AbstractStarter
 */
abstract class AbstractStarter
{

    /**
     * @var array
     */
    protected $interfaces = [
        ImporterInterface::class,
        MigratorInterface::class,
        DatabaseScriptInterface::class
    ];

    /**
     * Define your Migrators and Importers here (Orderings will be respected)
     *
     * Example:
     *  [
     *      'className' => NewsImporter::class,
     *      'configuration' => [
     *          'migrationClassKey' => 'news'
     *      ]
     *  ]
     *
     * If you want to set dryrun or limitToRecord for testing (overwrites values form CommandController):
     *  [
     *      'className' => UserMigrator::class,
     *      'configuration' => [
     *          'migrationClassKey' => 'user',
     *          'limitToRecord' => 1123,
     *          'dryrun' => false
     *      ]
     *  ]
     *
     *
     * @var array
     */
    protected $migrationClasses = [
    ];

    /**
     * @var ConsoleOutput|null
     */
    protected $output = null;

    /**
     * ImporterStarter constructor.
     *
     * @param ConsoleOutput $output
     */
    public function __construct(ConsoleOutput $output)
    {
        $this->output = $output;
    }

    /**
     * Run through $this->migrationClasses and call migrators or importers and prove the configuration
     *
     * @param string $migrationClassKey
     * @param bool $dryrun
     * @param int|string $limitToRecord
     * @param int $limitToPage
     * @param bool $recursive
     * @return void
     * @throws \Exception
     */
    public function start(string $migrationClassKey, bool $dryrun, $limitToRecord, int $limitToPage, bool $recursive)
    {
        $localConfiguration = [
            'dryrun' => $dryrun,
            'limitToRecord' => $limitToRecord,
            'limitToPage' => $limitToPage,
            'recursive' => $recursive
        ];
        foreach ($this->migrationClasses as $migrationConfig) {
            if (!class_exists($migrationConfig['className'])) {
                throw new \Exception('Class ' . $migrationConfig['className'] . ' does not exist');
            }
            if ($this->isSubclassOfAllowedInterfaces($migrationConfig['className'])) {
                $migrationConfig['configuration'] = (array)$migrationConfig['configuration'] + $localConfiguration;
                if ($migrationClassKey === $migrationConfig['configuration']['migrationClassKey']) {
                    /** @var AbstractImporter $importerClass */
                    $class = ObjectUtility::getObjectManager()->get($migrationConfig['className'], $this->output);
                    $class->startMigration($migrationConfig['configuration']);
                }
            } else {
                throw new \Exception('Class ' . __CLASS__ . ' does not implement one of the needed interfaces');
            }
        }
    }

    /**
     * @param string $className
     * @return bool
     */
    protected function isSubclassOfAllowedInterfaces(string $className): bool
    {
        foreach ($this->interfaces as $interfaceName) {
            if (is_subclass_of($className, $interfaceName)) {
                return true;
            }
        }
        return false;
    }
}
