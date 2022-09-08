<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\PropertyHelpers;

use Doctrine\DBAL\Driver\Exception as ExceptionDbalDriver;
use In2code\Migration\Exception\ConfigurationException;
use In2code\Migration\Utility\DatabaseUtility;
use PDO;

/**
 * Class GridelementsToContainerChildrenPropertyHelper
 *
 * to migrate container elements (parents) from EXT:gridelements to EXT:container
 *  'configuration' => [
 *      'columns' => [
 *          // old value in tt_content.tx_gridelements_backend_layout
 *          1 => [
 *              // tt_content.tx_gridelements_columns => tt_content.colPos
 *              12 => 12,
 *              22 => 22,
 *          ],
 *      ],
 *  ]
 */
class GridelementsToContainerChildrenPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{
    /**
     * @return void
     * @throws ConfigurationException
     */
    public function initialize(): void
    {
        if (is_array($this->getConfigurationByKey('columns')) === false) {
            throw new ConfigurationException('"columns" configuration is missing or invalid', 1662640585);
        }
    }

    /**
     * @return void
     * @throws ExceptionDbalDriver
     */
    public function manipulate(): void
    {
        $configuration = $this->getConfigurationByKey('columns');
        $type = $this->getParentProperty('tx_gridelements_backend_layout');
        $column = $this->getPropertyFromRecordOld('tx_gridelements_columns');
        $properties = [
            'colPos' => $configuration[$type][$column],
            'tx_container_parent' => $this->getPropertyFromRecordOld('tx_gridelements_container'),
        ];
        $this->setProperties($properties);
    }

    /**
     * @return bool
     * @throws ExceptionDbalDriver
     */
    public function shouldMigrate(): bool
    {
        $configuration = $this->getConfigurationByKey('columns');
        if ($this->getPropertyFromRecordOld('tx_gridelements_container') > 0) {
            $type = $this->getParentProperty('tx_gridelements_backend_layout');
            $column = $this->getPropertyFromRecordOld('tx_gridelements_columns');
            return array_key_exists($type, $configuration) && array_key_exists($column, $configuration[$type]);
        }
        return false;
    }

    /**
     * @param string $propertyName
     * @return int|string|null
     * @throws ExceptionDbalDriver
     */
    protected function getParentProperty(string $propertyName)
    {
        $parentIdentifier = $this->getPropertyFromRecordOld('tx_gridelements_container');
        if ($parentIdentifier > 0) {
            $queryBuilder = DatabaseUtility::getQueryBuilderForTable($this->table, true);
            return $queryBuilder
                ->select($propertyName)
                ->from($this->table)
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($parentIdentifier, PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetchOne();
        }
        return null;
    }
}
