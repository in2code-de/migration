<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\Helper;

use Doctrine\DBAL\DBALException;
use In2code\Migration\Exception\FileNotFoundException;
use In2code\Migration\Exception\FileOrFolderCouldNotBeCreatedException;
use In2code\Migration\Utility\DatabaseUtility;
use In2code\Migration\Utility\ObjectUtility;
use In2code\Migration\Utility\StringUtility;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FileHelper
 * brings some helper functions for file actions
 */
class FileHelper implements SingletonInterface
{

    /**
     * Cache storages
     * [
     *      1 => "fileadmin/"
     * ]
     *
     * @var array
     */
    protected $storages = [];

    /**
     * Search for sys_file_reference properties
     *
     * @param string $tableName
     * @param string $fieldName
     * @param int $uid
     * @return array
     * @throws DBALException
     */
    public function findReferencesFromRecord(string $tableName, string $fieldName, int $uid): array
    {
        $connection = DatabaseUtility::getConnectionForTable('sys_file_reference');
        $whereClause = 'tablenames="' . $tableName . '" and fieldname="' . $fieldName . '" and uid_foreign=' . $uid
            . ' and deleted = 0';
        return (array)$connection->executeQuery('select * from sys_file_reference where ' . $whereClause)->fetchAll();
    }

    /**
     * Search for sys_file properties
     *
     * @param int $identifier
     * @return array
     * @throws DBALException
     */
    public function findFileFromIdentifier(int $identifier): array
    {
        $connection = DatabaseUtility::getConnectionForTable('sys_file');
        return (array)$connection->executeQuery('select * from sys_file where uid=' . (int)$identifier)->fetch();
    }

    /**
     * Search for sys_file records
     *
     * @param string $tableName
     * @param string $fieldName
     * @param int $uid
     * @return array
     * @throws DBALException
     */
    public function findFilesFromRecordReferences(string $tableName, string $fieldName, int $uid): array
    {
        $references = $this->findReferencesFromRecord($tableName, $fieldName, $uid);
        $files = [];
        foreach ($references as $reference) {
            $files[] = $this->findFileFromIdentifier($reference['uid_local']);
        }
        return $files;
    }

    /**
     * Search for sys_file.uid
     *
     * @param string $identifier "/download/file.pdf" (must start with a leading slash)
     * @param int $storage "1"
     * @return int
     */
    public function findFileIdentifierFromIdentifierAndStorage(string $identifier, int $storage): int
    {
        if (StringUtility::startsWith($identifier, '/') === false) {
            $identifier = '/' . $identifier;
        }
        $queryBuilder = DatabaseUtility::getQueryBuilderForTable('sys_file');
        return (int)$queryBuilder
            ->select('uid')
            ->from('sys_file')
            ->where(
                $queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($identifier)),
                $queryBuilder->expr()->eq('storage', $queryBuilder->createNamedParameter($storage, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * Search for sys_file_storage.uid
     *
     * @param string $path
     * @return int
     * @throws DBALException
     */
    public function findIdentifierFromStoragePath(string $path): int
    {
        if (in_array($path, $this->storages) === false) {
            $sql = 'select uid from sys_file_storage where ExtractValue(configuration, ';
            $sql .= '\'//T3FlexForms/data/sheet[@index="sDEF"]/language/field[@index="basePath"]/value\') = "';
            $sql .= $path . '";';
            $connection = DatabaseUtility::getConnectionForTable('sys_file_storage');
            $identifier = (int)$connection->executeQuery($sql)->fetchColumn(0);
            $this->storages[$identifier] = $path;
        }
        return array_search($path, $this->storages);
    }

    /**
     * Return path (like "fileadmin/") from sys_file_storage.uid
     *
     * @param int $identifier
     * @return string path with trailing slash
     * @throws DBALException
     */
    public function findStoragePathFromIdentifier(int $identifier): string
    {
        if (array_key_exists($identifier, $this->storages) === false) {
            $sql = 'select ExtractValue(configuration, \'//T3FlexForms/data/sheet[@index="sDEF"]';
            $sql .= '/language/field[@index="basePath"]/value\') path from sys_file_storage where uid='
                . (int)$identifier;
            $connection = DatabaseUtility::getConnectionForTable('sys_file_storage');
            $storage = (string)$connection->executeQuery($sql)->fetchColumn(0);
            $this->storages[$identifier] = $storage;
        }
        return $this->storages[$identifier];
    }

    /**
     * Copy a file to a new target and create a reference to it
     *
     * @param string $relativeFile e.g. uploads/pics/image.jpg
     * @param string $targetFolder e.g. fileadmin/new/
     * @param string $tableName for sys_file_reference e.g. tx_news_domain_model_news
     * @param string $fieldName for sys_file_reference e.g. image
     * @param int $recordIdentifier for sys_file_reference.uid_foreign
     * @param array $additionalProperties ['title' => 'a', 'link' => 'b', 'alternative' => 'c', 'description' => 'd']
     * @param int $storageIdentifier
     * @return void
     * @throws DBALException
     * @throws FileNotFoundException
     * @throws FileOrFolderCouldNotBeCreatedException
     */
    public function copyFileAndCreateReference(
        string $relativeFile,
        string $targetFolder,
        string $tableName,
        string $fieldName,
        int $recordIdentifier,
        array $additionalProperties = [],
        int $storageIdentifier = 1
    ): void {
        $this->createFolderIfNotExists(GeneralUtility::getFileAbsFileName($targetFolder));
        $pathAndFilename = $this->copyFileToTargetFolder(
            GeneralUtility::getFileAbsFileName($relativeFile), $targetFolder
        );
        $fileUid = $this->indexFile($pathAndFilename, $storageIdentifier);
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
     * Create new filerelation if it does not exist yet
     *
     * @param string $tableName
     * @param string $fieldName
     * @param int $recordIdentifier
     * @param int $fileIdentifier
     * @param array $additionalProperties [title, description, alternative, link, crop, autoplay, showinpreview]
     * @return int
     * @throws DBALException
     */
    public function createFileRelation(
        string $tableName,
        string $fieldName,
        int $recordIdentifier,
        int $fileIdentifier,
        array $additionalProperties = []
    ): int {
        $databaseHelper = ObjectUtility::getObjectManager()->get(DatabaseHelper::class);
        $properties = [
            'uid_local' => $fileIdentifier,
            'uid_foreign' => $recordIdentifier,
            'tablenames' => $tableName,
            'fieldname' => $fieldName,
            'table_local' => 'sys_file'
        ];
        return $databaseHelper->createRecord('sys_file_reference', $additionalProperties + $properties);
    }

    /**
     * @param string $file like /var/www/uploads/file1.mp3
     * @param string $targetFolder relative path like fileadmin/folder/
     * @return string new relative path and filename
     */
    protected function copyFileToTargetFolder($file, $targetFolder): string
    {
        if (!file_exists(GeneralUtility::getFileAbsFileName($targetFolder . basename($file)))
            && file_exists($file)
        ) {
            shell_exec('cp "' . $file . '" ' . GeneralUtility::getFileAbsFileName($targetFolder));
        }
        return $targetFolder . basename($file);
    }

    /**
     * Create sys_file entry for given filename and return uid
     *
     * @param string $file relative path and filename
     * @param int $storageIdentifier
     * @return int
     * @throws DBALException
     * @throws FileNotFoundException
     */
    protected function indexFile($file, int $storageIdentifier): int
    {
        $fileIdentifier = 0;
        if (file_exists(GeneralUtility::getFileAbsFileName($file))) {
            try {
                $resourceFactory = ObjectUtility::getObjectManager()->get(ResourceFactory::class);
                $file = $resourceFactory->getFileObjectFromCombinedIdentifier(
                    $this->getCombinedIdentifier($file, $storageIdentifier)
                );
                $fileIdentifier = (int)$file->getProperty('uid');
            } catch (\Exception $exception) {
                throw new FileNotFoundException(
                    'combined identifier ' . $this->getCombinedIdentifier($file, $storageIdentifier) . ' not found',
                    1569921743
                );
            }
        }
        return $fileIdentifier;
    }

    /**
     * build combined identifier from absolute filename:
     *      "fileadmin/folder/test.pdf" => "1:folder/test.pdf"
     *
     * @param string $file relative path and filename
     * @param int $storageIdentifier
     * @return string
     * @throws DBALException
     */
    protected function getCombinedIdentifier($file, int $storageIdentifier): string
    {
        $identifier = $this->substituteFileadminFromPathAndName($file, $storageIdentifier);
        return (string)$storageIdentifier . ':' . $identifier;
    }

    /**
     * "fileadmin/downloads/test.pdf" => "/downloads/test.pdf"
     *
     * @param string $pathAndName
     * @param int $storageIdentifier
     * @return string
     * @throws DBALException
     */
    protected function substituteFileadminFromPathAndName(string $pathAndName, int $storageIdentifier): string
    {
        $substituteString = $this->findStoragePathFromIdentifier($storageIdentifier);
        if (StringUtility::startsWith($pathAndName, $substituteString)) {
            $pathAndName = substr($pathAndName, strlen($substituteString));
        }
        if (substr($pathAndName, 0, 1) !== '/') {
            $pathAndName = '/' . $pathAndName;
        }
        return $pathAndName;
    }

    /**
     * @param string $path absolute path
     * @return void
     * @throws FileOrFolderCouldNotBeCreatedException
     */
    protected function createFolderIfNotExists(string $path): void
    {
        if (!is_dir($path)) {
            try {
                GeneralUtility::mkdir_deep($path);
            } catch (\Exception $exception) {
                throw new FileOrFolderCouldNotBeCreatedException('Folder ' . $path . ' cannot be created', 1569334703);
            }
        }
    }
}
