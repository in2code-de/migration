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
}
