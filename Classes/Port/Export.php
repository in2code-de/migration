<?php
declare(strict_types=1);
namespace In2code\Migration\Port;

use Doctrine\DBAL\DBALException;
use In2code\Migration\Exception\JsonCanNotBeCreatedException;
use In2code\Migration\Port\Service\LinkRelationService;
use In2code\Migration\Signal\SignalTrait;
use In2code\Migration\Utility\DatabaseUtility;
use In2code\Migration\Utility\FileUtility;
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
     * Hold the complete configuration like
     *
     *  'excludedTables' => [
     *      'be_users'
     *  ],
     *  'relations' => [
     *      'pages' => [
     *          [
     *              'table' => 'sys_category_record_mm',
     *              'uid_local' => 'sys_category',
     *              'uid_foreign' => 'pages',
     *              'additional' => [
     *                  'tablenames' => 'pages',
     *                  'fieldname' => 'categories'
     *              ]
     *          ]
     *      ]
     *  ]
     *
     * @var array
     */
    protected $configuration = [];

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
     *          ],
     *          12346 => [
     *              'path' => 'fileadmin/file.pdf',
     *              'uri' => '/var/www/domain.org/public/fileadmin/file.pdf'
     *              'fileIdentifier' => 12346
     *          ]
     *      ],
     *      'mm' => [
     *          'sys_category_record_mm' => [
     *              [
     *                  'uid_local' => 123,
     *                  'uid_foreign' => 124,
     *                  'tablenames' => 'pages',
     *                  'fieldname' => 'categories'
     *              ]
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
     * @param array $configuration
     * @throws InvalidSlotException
     * @throws InvalidSlotReturnException
     */
    public function __construct(int $pid, int $recursive = 99, array $configuration = [])
    {
        $this->pid = $pid;
        $this->recursive = $recursive;
        $this->configuration = $configuration;
        $this->signalDispatch(__CLASS__, 'initial', [$this]);
    }

    /**
     * @return string
     * @throws DBALException
     * @throws InvalidSlotException
     * @throws InvalidSlotReturnException
     * @throws JsonCanNotBeCreatedException
     */
    public function export(): string
    {
        $this->buildJson();
        $this->signalDispatch(__CLASS__, 'beforeExport', [$this]);
        return $this->getJson();
    }

    /**
     * @return string
     * @throws JsonCanNotBeCreatedException
     */
    protected function getJson(): string
    {
        $result = json_encode($this->jsonArray, JSON_HEX_TAG);
        if ($result === false) {
            throw new JsonCanNotBeCreatedException(
                'JSON can not be created from array. Maybe there is a charset issue in your data?',
                1573585866
            );
        }
        return $result;
    }

    /**
     * @return void
     * @throws DBALException
     */
    protected function buildJson(): void
    {
        $this->jsonArray = [
            'records' => [
                'pages' => $this->getPageProperties()
            ]
        ];
        $this->extendPagesWithTranslations();
        $this->extendWithOtherTables();
        $this->extendWithFiles();
        $this->extendWithFilesFromLinks();
        $this->extendWithMmRelations();
    }

    /**
     * Build a basic array with pages
     *
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
     * Extend page records with more page records with sys_language_uid>0
     * @return void
     */
    protected function extendPagesWithTranslations(): void
    {
        foreach ($this->jsonArray['records']['pages'] as $pageProperties) {
            if ((int)$pageProperties['uid'] > 0) {
                $records = $this->getRecordsFromPageAndTable(
                    (int)$pageProperties['uid'],
                    'pages',
                    ' and sys_language_uid>0'
                );
                $this->jsonArray['records']['pages'] = array_merge($this->jsonArray['records']['pages'], $records);
            }
        }
    }

    /**
     * Add records (like tt_content) to pages
     *
     * @return void
     * @throws DBALException
     */
    protected function extendWithOtherTables(): void
    {
        foreach (TcaUtility::getTableNamesToExport($this->configuration['excludedTables']) as $table) {
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
     * Attach files from related sys_file_reference records
     *
     * @return void
     * @throws DBALException
     */
    protected function extendWithFiles(): void
    {
        if(isset($this->jsonArray['records']['sys_file_reference'])){
            foreach ((array)$this->jsonArray['records']['sys_file_reference'] as $referenceProperties) {
                $fileIdentifier = (int)$referenceProperties['uid_local'];
                $this->extendWithFilesBasic($fileIdentifier);
            }
        }
    }

    /**
     * Attach files from links in RTE fields
     *
     * @return void
     * @throws DBALException
     */
    protected function extendWithFilesFromLinks(): void
    {
        $linkRelationService = GeneralUtility::makeInstance(LinkRelationService::class, $this->configuration);
        $identifiers = $linkRelationService->getFileIdentifiersFromLinks($this->jsonArray);
        foreach ($identifiers as $fileIdentifier) {
            $this->extendWithFilesBasic($fileIdentifier);
        }
    }

    /**
     * Try to find mm relations that should be added
     *
     * @return void
     * @throws DBALException
     */
    protected function extendWithMmRelations(): void
    {
        foreach ((array)$this->configuration['relations'] as $table => $configurations) {
            if (DatabaseUtility::isTableExisting($table)) {
                foreach ($configurations as $configuration) {
                    $tableMm = $configuration['table'];
                    if (DatabaseUtility::isTableExisting($tableMm)) {
                        $queryBuilder = DatabaseUtility::getQueryBuilderForTable($tableMm, true);
                        $whereClause = $this->getWhereClauseForExtensionWithMmRelations($configuration, $table);
                        if ($whereClause !== '') {
                            $rows = (array)$queryBuilder
                                ->select('*')
                                ->from($tableMm)
                                ->where($whereClause)
                                ->execute()
                                ->fetchAll();
                            if (empty($this->jsonArray['mm'][$tableMm])) {
                                $this->jsonArray['mm'][$tableMm] = [];
                            }
                            $this->jsonArray['mm'][$tableMm] = array_merge($this->jsonArray['mm'][$tableMm], $rows);
                        }
                    }
                }
            }
        }
    }

    /**
     * Attach a file to the array by given sys_file.uid
     *
     * @param int $fileIdentifier
     * @return void
     * @throws DBALException
     */
    protected function extendWithFilesBasic(int $fileIdentifier): void
    {
        $fileProperties = $this->getPropertiesFromIdentifierAndTable($fileIdentifier, 'sys_file');
        $this->jsonArray['records']['sys_file'][(int)$fileProperties['uid']] = $fileProperties;

        $pathAndFilename = DatabaseUtility::getFilePathAndNameByStorageAndIdentifier(
            (int)$fileProperties['storage'],
            $fileProperties['identifier']
        );
        $fileArray = [
            'path' => $pathAndFilename,
            'fileIdentifier' => (int)$fileProperties['uid']
        ];
        $absolutePaF = GeneralUtility::getFileAbsFileName($pathAndFilename);
        if ($this->configuration['addFilesToJson'] === true) {
            $fileArray['base64'] = FileUtility::getBase64CodeFromFile($absolutePaF);
        } else {
            $fileArray['uri'] = $absolutePaF;
        }
        $this->jsonArray['files'][(int)$fileProperties['uid']] = $fileArray;
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
     * @param string $addWhere
     * @return array
     */
    protected function getRecordsFromPageAndTable(int $pageIdentifier, string $tableName, string $addWhere = ''): array
    {
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $queryBuilder->getRestrictions()->removeByType(StartTimeRestriction::class);
        $queryBuilder->getRestrictions()->removeByType(EndTimeRestriction::class);
        return (array)$queryBuilder
            ->select('*')
            ->from($tableName)
            ->where('pid=' . $pageIdentifier . $addWhere)
            ->execute()
            ->fetchAll();
    }

    /**
     * @return int[]
     */
    protected function getPageIdentifiersForExport(): array
    {
        $queryGenerator = GeneralUtility::makeInstance(QueryGenerator::class);
        $list = $queryGenerator->getTreeList($this->pid, $this->recursive, 0, 1);
        return GeneralUtility::intExplode(',', $list);
    }

    /**
     * @param array $configuration
     * @param string $table
     * @return string
     */
    protected function getWhereClauseForExtensionWithMmRelations(array $configuration, string $table): string
    {
        $identifiers = $this->getIdentifiersForTable($table);
        if ($identifiers !== []) {
            $lookupField = 'uid_local';
            if ($table === $configuration['uid_foreign']) {
                $lookupField = 'uid_foreign';
            }
            $where = $lookupField . ' in (' . implode(',', $identifiers) . ')';
            if (!empty($configuration['additional'])) {
                foreach ($configuration['additional'] as $field => $value) {
                    $where .= ' and ' . $field . '="' . $value . '"';
                }
            }
            return $where;
        }
        return '';
    }

    /**
     * @param string $tableName
     * @return int[]
     */
    protected function getIdentifiersForTable(string $tableName): array
    {
        $identifiers = [];
        if(isset($this->getJsonArray()['records'][$tableName])){
            foreach ((array)$this->getJsonArray()['records'][$tableName] as $record) {
                if (!empty($record['uid'])) {
                    $identifiers[] = (int)$record['uid'];
                }
            }
        }
        return $identifiers;
    }

    /**
     * @return array
     */
    public function getJsonArray(): array
    {
        return $this->jsonArray;
    }

    /**
     * @noinspection PhpUnused
     * @param array $jsonArray
     * @return Export
     */
    public function setJsonArray(array $jsonArray): self
    {
        $this->jsonArray = $jsonArray;
        return $this;
    }
}
