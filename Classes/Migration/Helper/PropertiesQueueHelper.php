<?php
namespace In2code\Migration\Migration\Helper;

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class PropertiesQueueHelper helps to save important values to a queue for a later iteration
 */
class PropertiesQueueHelper implements SingletonInterface
{

    /**
     * [
     *      "tt_content" => [
     *          123 => [
     *              "hidden" => 1
     *          ]
     *      ]
     * ]
     *
     * @var array
     */
    protected $queue = [];

    /**
     * @param string $table
     * @param int $uid
     * @param array $properties
     * @return array
     */
    public function updatePropertiesWithPropertiesFromQueue(string $table, int $uid, array $properties): array
    {
        if (!empty($this->queue[$table][$uid])) {
            $properties = $this->queue[$table][$uid] + $properties;
        }
        return $properties;
    }

    /**
     * Save exactly to database and keep information into a queue for later iterations
     *
     * @param string $table
     * @param int $uid
     * @param array $properties
     * @return void
     */
    public function writeToDatabaseAndQueue(string $table, int $uid, array $properties)
    {
        $this->getDatabase()->exec_UPDATEquery('tt_content', 'uid=' . (int)$uid, $properties);
        $this->queue[$table][$uid] = $properties;
    }

    /**
     * @return DatabaseConnection
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getDatabase(): DatabaseConnection
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
