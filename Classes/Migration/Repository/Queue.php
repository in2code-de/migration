<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\Repository;

use In2code\Migration\Utility\DatabaseUtility;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class Queue allows you to manipulate values while runtime.
 * If you want to change a value of a record and the migration/importer should respect the new values when it comes
 * to persistence at the end of the runtime.
 */
class Queue implements SingletonInterface
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
     * Update your properties with properties from the queue
     *
     * @param string $tableName
     * @param int $uid
     * @param array $properties
     * @return array
     */
    public function updatePropertiesWithPropertiesFromQueue(string $tableName, int $uid, array $properties): array
    {
        if (!empty($this->queue[$tableName][$uid])) {
            $properties = $this->queue[$tableName][$uid] + $properties;
        }
        return $properties;
    }

    /**
     * Save exactly to database and keep information into a queue for later iterations
     *
     * @param string $tableName
     * @param int $uid
     * @param array $properties
     * @return void
     */
    public function writeToDatabaseAndQueue(string $tableName, int $uid, array $properties): void
    {
        $connection = DatabaseUtility::getConnectionForTable($tableName);
        $connection->update($tableName, $properties, ['uid' => (int)$properties['uid']]);
        $this->addToQueue($tableName, $uid, $properties);
    }

    /**
     * @param string $tableName
     * @param int $uid
     * @param array $properties
     * @return void
     */
    public function addToQueue(string $tableName, int $uid, array $properties): void
    {
        $this->queue[$tableName][$uid] = $properties;
    }

    /**
     * @param string $tableName
     * @param int $uid
     * @return array
     */
    public function getFromQueue(string $tableName, int $uid): array
    {
        if (!empty($this->queue[$tableName][$uid])) {
            return $this->queue[$tableName][$uid];
        }
        return [];
    }
}
