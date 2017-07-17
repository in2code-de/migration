<?php
namespace In2code\In2template\Migration\DatabaseScript;

use In2code\In2template\Migration\Service\Log;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\ConsoleOutput;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class AbstractDatabaseScript
 */
abstract class AbstractDatabaseScript implements DatabaseScriptInterface
{

    /**
     * @var Log|null
     */
    protected $log = null;

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * AbstractImporter constructor.
     *
     * @param ConsoleOutput $output
     * @throws \Exception
     */
    public function __construct(ConsoleOutput $output)
    {
        $this->log = $this->getObjectManager()->get(Log::class)->setOutput($output);
    }

    /**
     * @return void
     */
    public function initialize()
    {
    }

    /**
     * @param array $configuration
     * @return void
     */
    public function startMigration(array $configuration)
    {
        $this->configuration = $configuration;
        foreach ($this->getSqlQueries() as $query) {
            $this->getDatabase()->sql_query($query);
        }
    }

    /**
     * @return array
     */
    public function getSqlQueries(): array
    {
        return [];
    }

    /**
     * @return DatabaseConnection
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getDatabase(): DatabaseConnection
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager(): ObjectManager
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }
}
