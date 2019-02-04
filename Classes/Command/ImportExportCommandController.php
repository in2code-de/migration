<?php
namespace In2code\Migration\Command;

use Helhum\Typo3Console\Mvc\Controller\CommandController;
use In2code\Migration\Utility\ObjectUtility;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Error\Exception;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Import;

/**
 * Class ImportExportCommandController
 *
 * inspired by https://gist.github.com/sascha-egerer/e75edf82d2c7eb27d78297117aa2bfb3 - script of Sascha Egerer
 */
class ImportExportCommandController extends CommandController
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Import
     */
    protected $import;

    /**
     * @var Export
     */
    protected $export;

    /**
     * @var string
     */
    private $backendUserGroupBackup = null;

    /**
     * Export data to a t3d typo3 export file (Format: XML)
     *
     *      Example cli command to store export in file.xml:
     *      ./vendor/bin/typo3 importexport:export 123 > ~/Desktop/file.xml
     *      ./vendor/bin/typo3 importexport:export 123 --recursive-levels=99 > ~/Desktop/file2.xml
     *
     * @param int $pid The page id to start from
     * @param int $recursiveLevels Define if the export should be recursive and how many levels
     * @param array $tables Add table names here which are THE ONLY ones which will be included into export AND if
     *     found as relations.
     * @return void
     * @throws Exception
     * @cli
     */
    public function exportCommand(int $pid, int $recursiveLevels = 0, array $tables = [])
    {
        if (empty($tables)) {
            $tables[] = '_ALL';
        }

        // this is required to get access to all records
        $this->makeBeUserAdmin();

        $this->export->init(true);
        $this->export->maxFileSize = 100000000;
        $this->export->setCharset(ObjectUtility::getLanguageService()->charSet);

        // Define all tables as static so no relations will get exportet
        // $this->export->relStaticTables = ['_ALL'];

        #   Set which tables relations we will allow:
        $this->export->relOnlyTables = $tables;    // exclusively includes. See comment in the class

        $this->export->setHeaderBasics();

        // If the ID is zero, export root
        if ($pid === 0) {
            $sPage = [
                'uid' => 0,
                'title' => 'ROOT'
            ];
        } else {
            $sPage = BackendUtility::getRecordWSOL('pages', $pid, '*');
        }

        $idH = [];
        if (is_array($sPage)) {
            $tree = $this->objectManager->get(PageTreeView::class);
            $tree->init();
            $tree->tree[] = ['row' => $sPage];
            $tree->buffer_idH = [];
            if ($recursiveLevels > 0) {
                $tree->getTree($pid, $recursiveLevels, '');
            }
            $idH[$pid]['uid'] = $pid;
            if (!empty($tree->buffer_idH)) {
                $idH[$pid]['subrow'] = $tree->buffer_idH;
            }
        }

        // In any case we should have a multi-level array, $idH, with the page structure here
        if (is_array($idH)) {
            // Sets the pagetree and gets a 1-dim array in return with the pages (in correct submission order BTW...)
            $flatList = $this->export->setPageTree($idH);
            foreach (array_keys($flatList) as $key) {
                $this->export->export_addRecord('pages', BackendUtility::getRecord('pages', $key));
                $this->addRecordsForPid($key, $tables, 999);
            }
        }

        // After adding ALL records we set relations:
        for ($a = 0; $a < 10; $a++) {
            $addR = $this->export->export_addDBRelations($a);
            if (empty($addR)) {
                break;
            }
        }

        // Finally files are added:
        // MUST be after the DBrelations are set so that files from ALL added records are included!
        $this->export->export_addFilesFromRelations();

        $this->export->export_addFilesFromSysFilesRecords();

        $this->resetBackendUser();

        if (!empty($this->export->errorLog)) {
            throw new Exception(implode(PHP_EOL, $this->export->errorLog), 1452878761);
        }

        $content = $this->export->compileMemoryToFileContent('xml');
        $this->outputLine($content);
    }

    /**
     * Import a t3d (Format: XML) file directly
     *
     *      Note in TYPO3 8.6 and newer:
     *      Example cli command to import file file.xml into page 123:
     *      ./vendor/bin/typo3 impexp:import fileadmin/_temp_/file.xml 123
     *
     *      In older versions this command can be used in the same way:
     *      ./vendor/bin/typo3 importexport:import ...
     *
     * @param string $file The full absolute path to the file
     * @param int $pid The pid under which the t3d file should be imported
     * @param bool $update Updates all records that have the same UID instead of creating new ones. This option
     *     requires that the structure you import already exists on this server and only needs to be updated with new
     *     content!
     * @param bool $forceUids BE CAREFUL! This will force the uids defined in the import any may override important
     *     data!
     * @param bool $forceImport Import also if file has already been imported!
     * @param bool $ignorePages BE CAREFUL! That might break the import! Prevent import of a page. This is useful if
     *     you try to import just content of one page.
     * @return int
     * @throws \Exception
     * @cli
     */
    public function importCommand(
        $file,
        $pid,
        $update = false,
        $forceUids = false,
        $forceImport = false,
        $ignorePages = false
    ) {
        if (VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) >= 8006000) {
            throw new \LogicException(
                'NOTE: You can simply use the command controller of the impexp system extension ' .
                '- example call (with xml-file (relative path) and PID to import in): ' .
                './vendor/bin/typo3 impexp:import fileadmin/_temp_/file.xml 123',
                1526472483
            );
        }
        if (!@is_file($file)) {
            throw new \Exception('Given file does not exist (' . $file . ')');
        }
        $importResult = false;
        $hashOfCurrentFile = sha1_file($file);
        $alreadyImportedFiles = (array)$this->registry->get('database_dumper', 'dumpImport');

        if (!array_search($hashOfCurrentFile, $alreadyImportedFiles) || $forceImport) {
            $this->makeBeUserAdmin();
            $this->import->init();

            $importResponse = false;

            $this->import->force_all_UIDS = $forceUids;
            $this->import->update = $update;
            $this->import->global_ignore_pid = true;

            if ($this->import->loadFile($file, 1)) {
                if ($ignorePages) {
                    unset($this->import->dat['header']['records']['pages']);
                }

                // Import to defined page:
                $this->import->importData($pid);
                // Get id of first created page:
                $newPages = $this->import->import_mapId['pages'];
                $importResponse = (int)reset($newPages);
            }

            $this->resetBackendUser();
            // Check for errors during the import process:
            if (!empty($this->import->errorLog)) {
                foreach ($this->import->errorLog as $error) {
                    $this->outputLine($error);
                }
                if (!$importResponse) {
                    $this->outputLine('No page records imported what may be ok...');
                }
            }
            $alreadyImportedFiles[] = $hashOfCurrentFile;
            $this->registry->set('database_dumper', 'dumpImport', $alreadyImportedFiles);
            $this->outputLine('Import has been finished');
        } else {
            $this->outputLine('Looks like file has already been imported');
        }
        return $importResult;
    }

    /**
     * Adds records to the export object for a specific page id.
     *
     * @param int $pid Page id for which to select records to add
     * @param array $tables Array of table names to select from
     * @param int $maxNumber Max amount of records to select
     * @return void
     */
    protected function addRecordsForPid($pid, $tables, $maxNumber)
    {
        if (is_array($tables)) {
            $databaseConnection = ObjectUtility::getDatabaseConnection();
            foreach (array_keys(ObjectUtility::getTca()) as $table) {
                if ($table != 'pages' && (in_array($table, $tables) || in_array('_ALL', $tables))) {
                    if (!ObjectUtility::getTca()[$table]['ctrl']['is_static']) {
                        $res = $this->execListQueryPid($table, $pid, MathUtility::forceIntegerInRange($maxNumber, 1));
                        while ($subTrow = $databaseConnection->sql_fetch_assoc($res)) {
                            $this->export->export_addRecord($table, $subTrow);
                        }
                        $databaseConnection->sql_free_result($res);
                    }
                }
            }
        }
    }

    /**
     * Selects records from table / pid
     *
     * @param string $table Table to select from
     * @param int $pid Page ID to select from
     * @param int $limit Max number of records to select
     * @return \mysqli_result|object Database resource
     */
    protected function execListQueryPid($table, $pid, $limit)
    {
        $databaseConnection = ObjectUtility::getDatabaseConnection();
        $orderBy = ObjectUtility::getTca()[$table]['ctrl']['sortby']
            ? 'ORDER BY ' . ObjectUtility::getTca()[$table]['ctrl']['sortby']
            : ObjectUtility::getTca()[$table]['ctrl']['default_sortby'];
        $res = $databaseConnection->exec_SELECTquery(
            '*',
            $table,
            'pid=' . (int)$pid
                . BackendUtility::deleteClause($table) . BackendUtility::versioningPlaceholderClause($table),
            '',
            $databaseConnection->stripOrderBy($orderBy),
            $limit
        );
        return $res;
    }

    /**
     * Make the BE_USER an admin as this
     *
     * @return void
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function makeBeUserAdmin()
    {
        $this->backendUserGroupBackup = $GLOBALS['BE_USER']->user['admin'];
        $GLOBALS['BE_USER']->user['admin'] = 1;
    }

    /**
     * Reset the backend user admin flag back to its old value
     *
     * @return void
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function resetBackendUser()
    {
        if ($this->backendUserGroupBackup !== null) {
            $GLOBALS['BE_USER']->user['admin'] = $this->backendUserGroupBackup;
        }
    }

    /**
     * @param Export $export
     */
    public function injectExport(Export $export)
    {
        $this->export = $export;
    }

    /**
     * @param Import $import
     */
    public function injectImport(Import $import)
    {
        $this->import = $import;
    }

    /**
     * @param Registry $registry
     */
    public function injectRegistry(Registry $registry)
    {
        $this->registry = $registry;
    }
}
