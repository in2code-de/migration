<?php
namespace In2code\In2template\Migration\Helper;

use In2code\In2template\Migration\Service\Log;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class FileHelper
 */
class FileHelper
{

    /**
     * @var Log|null
     */
    protected $log = null;

    /**
     * FileHelper constructor.
     */
    public function __construct()
    {
        $this->log = $this->getObjectManager()->get(Log::class);
    }

    /**
     * get sys_file.uid from path and filename
     *      If there is no sys_file entry yet but file exists, try to index it before
     *
     * @param string $pathAndName "fileadmin/downloads/test.pdf"
     * @param array $oldRecord
     * @return int
     */
    public function findFileIdentifierFromPathAndName(string $pathAndName, array $oldRecord): int
    {
        $row = $this->getDatabase()->exec_SELECTgetSingleRow(
            'uid',
            'sys_file',
            'identifier = "' . $this->substituteFileadminFromPathAndName($pathAndName) . '"'
        );
        $identifier = (int)$row['uid'];
        $identifier = $this->tryToIndexFile($identifier, $pathAndName, $oldRecord);
        return $identifier;
    }

    /**
     * @param string $tableName
     * @param string $fieldName
     * @param int $uid
     * @return int[]
     */
    public function findFileIdentifiersFromReference(string $tableName, string $fieldName, int $uid): array
    {
        $identifiers = [];
        $rows = $this->findReferencesFromRecord($tableName, $fieldName, $uid);
        foreach ($rows as $row) {
            $identifiers[] = (int)$row['uid_local'];
        }
        return $identifiers;
    }

    /**
     * @param string $tableName
     * @param string $fieldName
     * @param int $uid
     * @return array
     */
    public function findReferencesFromRecord(string $tableName, string $fieldName, int $uid): array
    {
        return $this->getDatabase()->exec_SELECTgetRows(
            '*',
            'sys_file_reference',
            'tablenames="' . $tableName . '" and fieldname="' . $fieldName . '" and uid_foreign=' . $uid
            . ' and deleted = 0'
        );
    }

    /**
     * Create new filerelation if it does not exist yet
     *
     * @param string $tableName
     * @param string $fieldName
     * @param int $recordIdentifier
     * @param int $fileIdentifier
     * @param array $additionalProperties [title, description, alternative, link, crop, autoplay, showinpreview]
     * @return int
     */
    public function createFileRelation(
        string $tableName,
        string $fieldName,
        int $recordIdentifier,
        int $fileIdentifier,
        array $additionalProperties = []
    ) {
        $databaseHelper = $this->getObjectManager()->get(DatabaseHelper::class);
        $row = [
            'uid_local' => $fileIdentifier,
            'uid_foreign' => $recordIdentifier,
            'tablenames' => $tableName,
            'fieldname' => $fieldName,
            'table_local' => 'sys_file'
        ];
        return $databaseHelper->createRecord('sys_file_reference', $additionalProperties + $row);
    }

    /**
     * @param int $fileIdentifier
     * @return string
     */
    public function buildFileStringFromFileIdentifier(int $fileIdentifier): string
    {
        $string = '';
        if ($fileIdentifier > 0) {
            $string = 't3://file?uid=' . $fileIdentifier;
        }
        return $string;
    }

    /**
     * @param string $relativeFile e.g. uploads/pics/image.jpg
     * @param string $targetFolder e.g. fileadmin/new/
     * @param string $tableName for sys_file_reference e.g. tx_news_domain_model_news
     * @param string $fieldName for sys_file_reference e.g. image
     * @param int $recordIdentifier for sys_file_reference.uid_foreign
     * @param array $additionalProperties ['title' => 'a', 'link' => 'b', 'alternative' => 'c', 'description' => 'd']
     * @return void
     */
    public function moveFileAndCreateReference(
        string $relativeFile,
        string $targetFolder,
        string $tableName,
        string $fieldName,
        int $recordIdentifier,
        array $additionalProperties = []
    ) {
        $this->createFolderIfNotExists(GeneralUtility::getFileAbsFileName($targetFolder));
        $pathAndFilename = $this->copyFileToFileadmin(GeneralUtility::getFileAbsFileName($relativeFile), $targetFolder);
        $fileUid = $this->indexFile($pathAndFilename);
        if ($fileUid > 0) {
            $this->createFileRelation(
                $tableName,
                $fieldName,
                $recordIdentifier,
                $fileUid,
                $additionalProperties
            );
        }
    }

    /**
     * @param string $file like /var/www/uploads/file1.mp3
     * @param string $targetFolder relative path
     * @return string new relative path and filename
     */
    protected function copyFileToFileadmin($file, $targetFolder)
    {
        if (!file_exists(GeneralUtility::getFileAbsFileName($targetFolder . basename($file)))
            && file_exists($file)
        ) {
            shell_exec('cp "' . $file . '" ' . GeneralUtility::getFileAbsFileName($targetFolder));
        }
        return $targetFolder . basename($file);
    }

    /**
     * If no sys_file record found, check if file exists and try to index it to create sys_file entry
     *
     * @param string $pathAndName
     * @param int $identifier
     * @param array $oldRecord
     * @return int
     */
    public function tryToIndexFile(int $identifier, string $pathAndName, array $oldRecord): int
    {
        if ($identifier === 0) {
            $identifier = $this->indexFile($pathAndName);
            if ($identifier === 0) {
                $this->log->addError('File not found: ' . $pathAndName . ' (uid' . $oldRecord['uid'] . ')');
            }
        }
        return $identifier;
    }

    /**
     * Create sys_file entry for given filename and return uid
     *
     * @param string $file relative path and filename
     * @return int
     */
    protected function indexFile($file): int
    {
        $fileIdentifier = 0;
        if (file_exists(GeneralUtility::getFileAbsFileName($file))) {
            $resourceFactory = $this->getObjectManager()->get(ResourceFactory::class);
            $file = $resourceFactory->getFileObjectFromCombinedIdentifier($this->getCombinedIdentifier($file));
            $fileIdentifier = $file->getProperty('uid');
            $this->log->addMessage('sys_file record generated for ' . $file->getPublicUrl());
        }
        return $fileIdentifier;
    }

    /**
     * build combined identifier from absolute filename:
     *      "fileadmin/folder/test.pdf" => "1:folder/test.pdf"
     *
     * @param string $file relative path and filename
     * @return string
     */
    protected function getCombinedIdentifier($file)
    {
        $identifier = $this->substituteFileadminFromPathAndName($file);
        return '1:' . $identifier;
    }

    /**
     * "fileadmin/downloads/test.pdf" => "/downloads/test.pdf"
     *
     * @param string $pathAndName
     * @return string
     */
    protected function substituteFileadminFromPathAndName(string $pathAndName): string
    {
        $substituteString = 'fileadmin/';
        if (substr($pathAndName, 0, strlen($substituteString)) === $substituteString) {
            $pathAndName = str_replace($substituteString, '', $pathAndName);
        }
        if (substr($pathAndName, 0, 1) !== '/') {
            $pathAndName = '/' . $pathAndName;
        }
        return $pathAndName;
    }

    /**
     * Create folder
     *
     * @param string $path needs absolute path
     * @return void
     * @throws \Exception
     */
    protected function createFolderIfNotExists($path)
    {
        if (!is_dir($path) && !GeneralUtility::mkdir($path)) {
            throw new \Exception('Folder ' . $path . ' cannot be created');
        }
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
