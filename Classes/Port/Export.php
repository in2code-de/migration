<?php

declare(strict_types=1);
namespace In2code\Migration\Port;

use Doctrine\DBAL\Driver\Exception as ExceptionDbalDriver;
use Doctrine\DBAL\Exception as ExceptionDbal;
use In2code\Migration\Events\ExportBeforeEvent;
use In2code\Migration\Events\ExportInitialEvent;
use In2code\Migration\Exception\JsonCanNotBeCreatedException;
use In2code\Migration\Port\Service\LinkRelationService;
use In2code\Migration\Service\TreeService;
use In2code\Migration\Utility\DatabaseUtility;
use In2code\Migration\Utility\FileUtility;
use In2code\Migration\Utility\TcaUtility;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Export
{
    protected int $pid = 0;
    protected int $recursive = 99;

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
    protected array $configuration = [];

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
    protected array $jsonArray = [];

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(int $pid, int $recursive = 99, array $configuration = [])
    {
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
        /** @var ExportInitialEvent $event */
        $event = $this->eventDispatcher->dispatch(new ExportInitialEvent($pid, $recursive, $configuration));
        $this->pid = $event->getPid();
        $this->recursive = $event->getRecursive();
        $this->configuration = $event->getConfiguration();
    }

    /**
     * @return string
     * @throws JsonCanNotBeCreatedException
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    public function export(): string
    {
        $this->buildJson();
        /** @var ExportBeforeEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ExportBeforeEvent($this->jsonArray, $this->pid, $this->recursive, $this->configuration)
        );
        $this->jsonArray = $event->getJsonArray();
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
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    protected function buildJson(): void
    {
        $this->jsonArray = [
            'records' => [
                'pages' => $this->getPageProperties(),
            ],
        ];
        $this->extendPagesWithTranslations();
        $this->extendWithOtherTables();
        $this->extendWithFiles();
        $this->extendWithFilesFromLinks();
        $this->extendWithMmRelations();
        $this->extendWithMetadata();
    }

    /**
     * Build a basic array with pages
     *
     * @return array
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
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
     *
     * @return void
     * @throws ExceptionDbal
     */
    protected function extendPagesWithTranslations(): void
    {
        foreach ($this->jsonArray['records']['pages'] ?? [] as $pageProperties) {
            if ((int)($pageProperties['uid'] ?? 0) > 0) {
                $records = $this->getPageTranslations((int)$pageProperties['uid']);
                $this->jsonArray['records']['pages'] = array_merge($this->jsonArray['records']['pages'], $records);
            }
        }
    }

    /**
     * Add records (like tt_content) to pages
     *
     * @return void
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    protected function extendWithOtherTables(): void
    {
        foreach (TcaUtility::getTableNamesToExport($this->configuration['excludedTables'] ?? []) as $table) {
            $rows = [];
            foreach (($this->jsonArray['records']['pages'] ?? []) as $pageProperties) {
                $pid = (int)($pageProperties['uid'] ?? 0);
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
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    protected function extendWithFiles(): void
    {
        foreach (($this->jsonArray['records']['sys_file_reference'] ?? []) as $referenceProperties) {
            $fileIdentifier = (int)$referenceProperties['uid_local'];
            $this->extendWithFilesBasic($fileIdentifier);
        }
    }

    /**
     * Attach files from links in RTE fields
     *
     * @return void
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    protected function extendWithFilesFromLinks(): void
    {
        $linkRelationService = GeneralUtility::makeInstance(LinkRelationService::class, $this->configuration);
        $identifiers = $linkRelationService->getFileIdentifiers($this->jsonArray);
        foreach ($identifiers as $fileIdentifier) {
            $this->extendWithFilesBasic($fileIdentifier);
        }
    }

    /**
     * Try to find mm relations that should be added
     *
     * @return void
     * @throws ExceptionDbal
     */
    protected function extendWithMmRelations(): void
    {
        foreach ($this->configuration['relations'] ?? [] as $table => $configurations) {
            if (DatabaseUtility::isTableExisting($table)) {
                foreach ($configurations as $configuration) {
                    $tableMm = $configuration['table'];
                    if (DatabaseUtility::isTableExisting($tableMm)) {
                        $queryBuilder = DatabaseUtility::getQueryBuilderForTable($tableMm, true);
                        $whereClause = $this->getWhereClauseForExtensionWithMmRelations($configuration, $table);
                        if ($whereClause !== '') {
                            $rows = $queryBuilder
                                ->select('*')
                                ->from($tableMm)
                                ->where($whereClause)
                                ->execute()
                                ->fetchAllAssociative();
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

    protected function extendWithMetadata(): void
    {
        $this->jsonArray['records']['sys_file_metadata'] = [];
        foreach ($this->jsonArray['files'] ?? [] as $file) {
            $this->jsonArray['records']['sys_file_metadata'][] =
                $this->getPropertiesFromMetadataByFileIdentifier($file['fileIdentifier']);
        }
    }

    /**
     * Attach a file to the array by given sys_file.uid
     *
     * @param int $fileIdentifier
     * @return void
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    protected function extendWithFilesBasic(int $fileIdentifier): void
    {
        $fileProperties = $this->getPropertiesFromIdentifierAndTable($fileIdentifier, 'sys_file');
        if ($fileProperties !== []) {
            $this->jsonArray['records']['sys_file'][(int)$fileProperties['uid']] = $fileProperties;

            $pathAndFilename = DatabaseUtility::getFilePathAndNameByStorageAndIdentifier(
                (int)$fileProperties['storage'],
                $fileProperties['identifier']
            );
            $fileArray = [
                'path' => $pathAndFilename,
                'fileIdentifier' => (int)$fileProperties['uid'],
            ];
            $absolutePaF = GeneralUtility::getFileAbsFileName($pathAndFilename);
            if ($this->configuration['addFilesToJson'] === true) {
                $fileArray['base64'] = FileUtility::getBase64CodeFromFile($absolutePaF);
            } else {
                $fileArray['uri'] = $absolutePaF;
            }
            $this->jsonArray['files'][(int)$fileProperties['uid']] = $fileArray;
        }
    }

    /**
     * @param int $identifier
     * @param string $tableName
     * @return array
     * @throws ExceptionDbal
     */
    protected function getPropertiesFromIdentifierAndTable(int $identifier, string $tableName): array
    {
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable($tableName, true);
        $row = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where('uid=' . $identifier)
            ->executeQuery()
            ->fetchAssociative();
        if ($row === false) {
            $row = [];
        }
        return $row;
    }

    protected function getPropertiesFromMetadataByFileIdentifier(int $fileIdentifier): array
    {
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable('sys_file_metadata', true);
        $row = $queryBuilder
            ->select('*')
            ->from('sys_file_metadata')
            ->where('file=' . $fileIdentifier)
            ->executeQuery()
            ->fetchAssociative();
        if ($row === false) {
            $row = [];
        }
        return $row;
    }

    /**
     * @param int $pageIdentifier
     * @param string $tableName
     * @param string $addWhere
     * @return array
     * @throws ExceptionDbal
     */
    protected function getRecordsFromPageAndTable(int $pageIdentifier, string $tableName, string $addWhere = ''): array
    {
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $queryBuilder->getRestrictions()->removeByType(StartTimeRestriction::class);
        $queryBuilder->getRestrictions()->removeByType(EndTimeRestriction::class);
        $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where('pid=' . $pageIdentifier . $addWhere);
        if (DatabaseUtility::isFieldExistingInTable('sys_language_uid', $tableName)) {
            $queryBuilder->addOrderBy('sys_language_uid', 'asc');
        }
        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param int $pageIdentifier
     * @return array
     * @throws ExceptionDbal
     */
    protected function getPageTranslations(int $pageIdentifier): array
    {
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $queryBuilder->getRestrictions()->removeByType(StartTimeRestriction::class);
        $queryBuilder->getRestrictions()->removeByType(EndTimeRestriction::class);
        return $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($pageIdentifier, Connection::PARAM_INT)),
                $queryBuilder->expr()->gt('sys_language_uid', 0),
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @return array
     * @throws ExceptionDbal
     * @throws ExceptionDbalDriver
     */
    protected function getPageIdentifiersForExport(): array
    {
        $treeService = GeneralUtility::makeInstance(TreeService::class, $this->recursive);
        return $treeService->getAllSubpageIdentifiers($this->pid);
    }

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

    protected function getIdentifiersForTable(string $tableName): array
    {
        $identifiers = [];
        foreach (($this->getJsonArray()['records'][$tableName] ?? []) as $record) {
            if (!empty($record['uid'])) {
                $identifiers[] = (int)$record['uid'];
            }
        }
        return $identifiers;
    }

    public function getJsonArray(): array
    {
        return $this->jsonArray;
    }

    public function setJsonArray(array $jsonArray): self
    {
        $this->jsonArray = $jsonArray;
        return $this;
    }
}
