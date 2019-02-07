<?php
namespace In2code\Migration\Service;

use Doctrine\DBAL\DBALException;
use In2code\Migration\Utility\DatabaseUtility;
use In2code\Migration\Utility\FileUtility;
use In2code\Migration\Utility\ObjectUtility;
use In2code\Migration\Utility\TcaUtility;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ExportService
 */
class ExportService
{

    /**
     * @var int
     */
    protected $pid = 0;

    /**
     * @var int
     */
    protected $recursive = 99;

    /**
     * @var array
     */
    protected $excludedTables = [];

    /**
     * @var bool
     */
    protected $addFiles = true;

    /**
     * @var array
     */
    protected $jsonArray = [];

    /**
     * ExportService constructor.
     * @param int $pid
     * @param int $recursive
     * @param array $excludedTables
     */
    public function __construct(int $pid, int $recursive = 99, array $excludedTables = [])
    {
        $this->pid = $pid;
        $this->recursive = $recursive;
        $this->excludedTables = $excludedTables;
    }

    /**
     * @return string
     * @throws DBALException
     */
    public function export(): string
    {
        $this->buildJson();
        return $this->getJson();
    }

    /**
     * @return string
     */
    protected function getJson(): string
    {
        return json_encode($this->jsonArray);
    }

    /**
     * @return void
     * @throws DBALException
     */
    protected function buildJson()
    {
        $this->jsonArray = [
            'records' => [
                'pages' => $this->getPageProperties()
            ]
        ];
        $this->extendWithOtherTables();
        $this->extendWithFiles();
    }

    /**
     * @return array
     */
    protected function getPageProperties(): array
    {
        $properties = [];
        foreach ($this->getPageIdentifiersForExport() as $pageIdentifier) {
            $properties[] = $this->getPropertiesFromIdentifierAndTable($pageIdentifier, 'pages');
        }
        return $properties;
    }

    /**
     * @return void
     * @throws DBALException
     */
    protected function extendWithOtherTables()
    {
        foreach (TcaUtility::getTableNamesToExport($this->excludedTables) as $table) {
            $rows = [];
            foreach ($this->jsonArray['records']['pages'] as $pageProperties) {
                $pid = (int)$pageProperties['uid'];
                $rows = array_merge($rows, $this->getRecordsFromPageAndTable($pid, $table));
            }
            if ($rows !== []) {
                $this->jsonArray['records'][$table] = $rows;
            }
        }
    }

    /**
     * @return void
     * @throws DBALException
     */
    protected function extendWithFiles()
    {
        if ($this->addFiles === true) {
            foreach ($this->jsonArray['records']['sys_file_reference'] as $referenceProperties) {
                $fileIdentifier = (int)$referenceProperties['uid_local'];
                $fileProperties = $this->getPropertiesFromIdentifierAndTable($fileIdentifier, 'sys_file');
                $this->jsonArray['records']['sys_file'][(int)$fileProperties['uid']] = $fileProperties;

                $relativePathAndFilename = DatabaseUtility::getFilePathAndNameByStorageAndIdentifier(
                    (int)$fileProperties['storage'],
                    $fileProperties['identifier']
                );
                $this->jsonArray['files'][(int)$fileProperties['uid']] = [
                    'path' => $relativePathAndFilename,
                    'base64' => FileUtility::getBase64CodeFromFile($relativePathAndFilename),
                    'fileIdentifier' => (int)$fileProperties['uid']
                ];
            }
        }
    }

    /**
     * @param int $identifier
     * @param string $tableName
     * @return array
     */
    protected function getPropertiesFromIdentifierAndTable(int $identifier, string $tableName): array
    {
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable($tableName, true);
        $rows = (array)$queryBuilder
            ->select('*')
            ->from($tableName)
            ->where('uid=' . $identifier)
            ->execute()
            ->fetchAll();
        if (!empty($rows[0]['uid'])) {
            return $rows[0];
        }
        return [];
    }

    /**
     * @param int $pageIdentifier
     * @param string $tableName
     * @return array
     */
    protected function getRecordsFromPageAndTable(int $pageIdentifier, string $tableName): array
    {
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        return (array)$queryBuilder
            ->select('*')
            ->from($tableName)
            ->where('pid=' . $pageIdentifier)
            ->execute()
            ->fetchAll();
    }

    /**
     * @return int[]
     */
    protected function getPageIdentifiersForExport(): array
    {
        $queryGenerator = ObjectUtility::getObjectManager()->get(QueryGenerator::class);
        $list = $queryGenerator->getTreeList($this->pid, $this->recursive, 0, 1);
        return GeneralUtility::intExplode(',', $list);
    }
}
