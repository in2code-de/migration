<?php
namespace In2code\Migration\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FileUtility
 */
class FileUtility
{

    /**
     * @param string $filename relative filename like "fileadmin/file.jpg"
     * @return string
     */
    public static function getBase64CodeFromFile(string $filename): string
    {
        $file = GeneralUtility::getFileAbsFileName($filename);
        if (is_file($file)) {
            $content = file_get_contents($file);
            return base64_encode($content);
        }
        return '';
    }

    /**
     * @param string $filename relative filename like "fileadmin/file.jpg"
     * @param string $content base64 encoded content of the file
     * @param bool $overwrite overwrite existing files
     * @return bool
     */
    public static function writeFileFromBase64Code(string $filename, string $content, bool $overwrite = false): bool
    {
        $file = GeneralUtility::getFileAbsFileName($filename);
        self::createFolderIfNotExists($file);
        if ($overwrite === true || is_file($file) === false) {
            return GeneralUtility::writeFile($file, base64_decode($content));
        }
        return false;
    }

    /**
     * @param string $pathAndFilename
     * @return void
     */
    protected static function createFolderIfNotExists(string $pathAndFilename)
    {
        $path = self::getPathFromPathAndFilename($pathAndFilename);
        if (!is_dir($path)) {
            try {
                GeneralUtility::mkdir_deep($path);
            } catch (\Exception $exception) {
                throw new \UnexpectedValueException('Folder ' . $path . ' could not be created', 1549533300);
            }
        }
    }

    /**
     * @param string $pathAndFilename
     * @return string
     */
    protected static function getPathFromPathAndFilename($pathAndFilename)
    {
        $pathInfo = pathinfo($pathAndFilename);
        return $pathInfo['dirname'];
    }
}
