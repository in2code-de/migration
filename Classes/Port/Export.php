<?php
declare(strict_types=1);
namespace In2code\Migration\Port;

use Doctrine\DBAL\DBALException;
use In2code\Migration\Port\Service\LinkRelationService;
use In2code\Migration\Signal\SignalTrait;
use In2code\Migration\Utility\DatabaseUtility;
use In2code\Migration\Utility\FileUtility;
use In2code\Migration\Utility\ObjectUtility;
use In2code\Migration\Utility\TcaUtility;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;

/**
 * Class ExportService
 */
class Export
{
    use SignalTrait;

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
     * Array that is build just before it's packed into a json file
     *
     * Example content:
     *  [
     *      'records' => [
     *          'pages' => [
     *              [
     *                  'uid' => 123,
     *                  'title' => 'page title'
     *              ]
     *          ],
     *          'tt_content' => [
     *              [
     *                  'uid' => 1234,
     *                  'header' => 'content header'
     *              ]
     *          ]
     *      ],
     *      'files' => [
     *          12345 => [
     *              'path' => 'fileadmin/file.pdf',
     *              'base64' => 'base64:abcdef1234567890'
     *              'fileIdentifier' => 12345
     *          ]
     *      ]
     *  ]
     *
     * @var array
     */
    protected $jsonArray = [];

    /**
     * ExportService constructor.
     * @param int $pid
     * @param int $recursive
     * @param array $excludedTables
     * @throws InvalidSlotException
     * @throws InvalidSlotReturnException
     */
    public function __construct(int $pid, int $recursive = 99, array $excludedTables = [])
    {
        $this->pid = $pid;
        $this->recursive = $recursive;
        $this->excludedTables = $excludedTables;
        $this->signalDispatch(__CLASS__, 'initial', [$this]);
    }

    /**
     * @return string
     * @throws DBALException
     * @throws InvalidSlotException
     * @throws InvalidSlotReturnException
     */
    public function export(): string
    {
        $this->buildJson();
        $this->signalDispatch(__CLASS__, 'beforeExport', [$this]);
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
        $this->extendWithFilesFromLinks();
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
            foreach ((array)$this->jsonArray['records']['pages'] as $pageProperties) {
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
            foreach ((array)$this->jsonArray['records']['sys_file_reference'] as $referenceProperties) {
                $fileIdentifier = (int)$referenceProperties['uid_local'];
                $this->extendWithFilesBasic($fileIdentifier);
            }
        }
    }

    /**
     * @return void
     * @throws DBALException
     */
    protected function extendWithFilesFromLinks()
    {
        $linkRelationService = ObjectUtility::getObjectManager()->get(LinkRelationService::class);
        $identifiers = $linkRelationService->getFileIdentifiersFromLinks($this->jsonArray);
        foreach ($identifiers as $fileIdentifier) {
            $this->extendWithFilesBasic($fileIdentifier);
        }
    }

    /**
     * @param int $fileIdentifier
     * @return void
     * @throws DBALException
     */
    protected function extendWithFilesBasic(int $fileIdentifier): void
    {
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

    /**
     * @param int $identifier
     * @param string $tableName
     * @return array
     */
    protected function getPropertiesFromIdentifierAndTable(int $identifier, string $tableName): array
    {
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable($tableName, true);
        return (array)$queryBuilder
            ->select('*')
            ->from($tableName)
            ->where('uid=' . $identifier)
            ->execute()
            ->fetch();
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

    /**
     * @return array
     */
    public function getJsonArray(): array
    {
        return $this->jsonArray;
    }

    /**
     * @param array $jsonArray
     * @return Export
     */
    public function setJsonArray(array $jsonArray): self
    {
        $this->jsonArray = $jsonArray;
        return $this;
    }
}
