<?php
declare(strict_types=1);
namespace In2code\Migration\Service;

use Doctrine\DBAL\Driver\Exception as ExceptionDbalDriver;
use Doctrine\DBAL\Exception as ExceptionDbal;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TreeService
{
    const TABLE_NAME = 'pages';

    /**
     * Successor of TYPO3\CMS\Core\Database\QueryGenerator->getTreeList as it was removed in TYPO3 12
     *
     * @param int $pageIdentifier Start page identifier
     * @param bool $addStart Add start page identifier to list
     * @param bool $addHidden Should records with pages.hidden=1 be added?
     * @return array
     * @throws ExceptionDbalDriver
     * @throws ExceptionDbal
     */
    public function getAllSubpageIdentifiers(
        int $pageIdentifier,
        bool $addStart = true,
        bool $addHidden = true
    ): array {
        $identifiers = [];
        if ($addStart === true) {
            $identifiers[] = $pageIdentifier;
        }
        foreach ($this->getChildrenPageIdentifiers($pageIdentifier, $addHidden) as $identifier) {
            $identifiers = array_merge($identifiers, $this->getAllSubpageIdentifiers($identifier, true, $addHidden));
        }
        return $identifiers;
    }

    /**
     * @param int $pageIdentifier
     * @param bool $addHidden
     * @return array
     * @throws ExceptionDbal
     */
    protected function getChildrenPageIdentifiers(int $pageIdentifier, bool $addHidden): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder
            ->select('uid', 'uid')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageIdentifier, PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('sys_language_uid', 0)
            );
        if ($addHidden === false) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        }
        $result = $queryBuilder->execute()->fetchAllKeyValue();
        return array_values($result);
    }
}