<?php
namespace In2code\In2template\Migration;

use In2code\In2template\Migration\DatabaseScript\AddMainTyposcriptTemplateDatabaseScript;
use In2code\In2template\Migration\DatabaseScript\DatabaseScriptInterface;
use In2code\In2template\Migration\DatabaseScript\DisableTypoScriptTemplatesDatabaseScript;
use In2code\In2template\Migration\DatabaseScript\MediaReferencesForContentElementsDatabaseScript;
use In2code\In2template\Migration\DatabaseScript\ParentCalendarCategoryDatabaseScript;
use In2code\In2template\Migration\DatabaseScript\RemovePagebrowserAndFilterContentElementsDatabaseScript;
use In2code\In2template\Migration\Import\AbstractImporter;
use In2code\In2template\Migration\Import\AppointmentImporter;
use In2code\In2template\Migration\Import\CalendarCategoriesEnglishImporter;
use In2code\In2template\Migration\Import\CalendarCategoriesImporter;
use In2code\In2template\Migration\Import\ImporterInterface;
use In2code\In2template\Migration\Import\NewsCategoriesImporter;
use In2code\In2template\Migration\Import\NewsImporter;
use In2code\In2template\Migration\Migrate\BackendUsergroupMigrator;
use In2code\In2template\Migration\Migrate\BackendUserMigrator;
use In2code\In2template\Migration\Migrate\BodytextMigrator;
use In2code\In2template\Migration\Migrate\ContentMigrator;
use In2code\In2template\Migration\Migrate\MigratorInterface;
use In2code\In2template\Migration\Migrate\PageMigrator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\ConsoleOutput;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class Starter
 */
class Starter
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
     * @var string
     */
    protected $migrationClassKey = '';

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
        [
            'className' => NewsCategoriesImporter::class,
            'configuration' => [
                'migrationClassKey' => 'news'
            ]
        ],
        [
            'className' => NewsImporter::class,
            'configuration' => [
                'migrationClassKey' => 'news'
            ]
        ],
        [
            'className' => PageMigrator::class,
            'configuration' => [
                'migrationClassKey' => 'content'
            ]
        ],
        [
            'className' => ContentMigrator::class,
            'configuration' => [
                'migrationClassKey' => 'content'
            ]
        ],
        [
            'className' => BodytextMigrator::class,
            'configuration' => [
                'migrationClassKey' => 'content'
            ]
        ],
        [
            'className' => DisableTypoScriptTemplatesDatabaseScript::class,
            'configuration' => [
                'migrationClassKey' => 'database'
            ]
        ],
        [
            'className' => AddMainTyposcriptTemplateDatabaseScript::class,
            'configuration' => [
                'migrationClassKey' => 'database'
            ]
        ],
        [
            'className' => MediaReferencesForContentElementsDatabaseScript::class,
            'configuration' => [
                'migrationClassKey' => 'database'
            ]
        ],
        [
            'className' => RemovePagebrowserAndFilterContentElementsDatabaseScript::class,
            'configuration' => [
                'migrationClassKey' => 'database'
            ]
        ],
        [
            'className' => ParentCalendarCategoryDatabaseScript::class,
            'configuration' => [
                'migrationClassKey' => 'calendar'
            ]
        ],
        [
            'className' => CalendarCategoriesImporter::class,
            'configuration' => [
                'migrationClassKey' => 'calendar'
            ]
        ],
        [
            'className' => CalendarCategoriesEnglishImporter::class,
            'configuration' => [
                'migrationClassKey' => 'calendar'
            ]
        ],
        [
            'className' => AppointmentImporter::class,
            'configuration' => [
                'migrationClassKey' => 'calendar'
            ]
        ],
        [
            'className' => BackendUserMigrator::class,
            'configuration' => [
                'migrationClassKey' => 'backenduser'
            ]
        ],
        [
            'className' => BackendUsergroupMigrator::class,
            'configuration' => [
                'migrationClassKey' => 'backenduser'
            ]
        ],
    ];

    /**
     * @var ConsoleOutput|null
     */
    protected $output = null;

    /**
     * ImporterStarter constructor.
     *
     * @param ConsoleOutput $output
     * @param string $migrationClassKey
     */
    public function __construct(ConsoleOutput $output, string $migrationClassKey)
    {
        $this->output = $output;
        $this->migrationClassKey = $migrationClassKey;
    }

    /**
     * Run through $this->migrationClasses and call migrators or importers and prove the configuration
     *
     * @param bool $dryrun
     * @param int|string $limitToRecord
     * @param int $limitToPage
     * @param bool $recursive
     * @return void
     * @throws \Exception
     */
    public function start(bool $dryrun, $limitToRecord, int $limitToPage, bool $recursive)
    {
        $localConfiguration = [
            'dryrun' => $dryrun,
            'limitToRecord' => $limitToRecord,
            'limitToPage' => $limitToPage,
            'recursive' => $recursive
        ];
        foreach ($this->migrationClasses as $migrationConfig) {
            if (!class_exists($migrationConfig['className'])) {
                throw new \Exception('Class ' . $migrationConfig['className'] . ' does not exists');
            }
            if ($this->isSubclassOfAllowedInterfaces($migrationConfig['className'])) {
                $migrationConfig['configuration'] = (array)$migrationConfig['configuration'] + $localConfiguration;
                if ($this->migrationClassKey === $migrationConfig['configuration']['migrationClassKey']) {
                    /** @var AbstractImporter $importerClass */
                    $class = $this->getObjectManager()->get($migrationConfig['className'], $this->output);
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

    /**
     * @return ObjectManager
     */
    protected function getObjectManager(): ObjectManager
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }
}
