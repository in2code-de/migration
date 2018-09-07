<?php
namespace In2code\Migration\Utility;

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class ObjectUtility
 */
class ObjectUtility
{

    /**
     * @return ObjectManagerInterface
     */
    public static function getObjectManager(): ObjectManagerInterface
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }

    /**
     * @return PersistenceManager
     */
    public static function getPersistenceManager(): PersistenceManager
    {
        return self::getObjectManager()->get(PersistenceManager::class);
    }

    /**
     * @return DatabaseConnection
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function getDatabaseConnection(): DatabaseConnection
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return LanguageService
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function getTca(): array
    {
        return $GLOBALS['TCA'];
    }
}
