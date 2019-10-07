<?php
declare(strict_types=1);
namespace In2code\Migration\Port\Service;

/**
 * Class MappingService
 * offers functionality for a mapping from old to new identifiers while runtime of an import.
 */
class MappingService
{

    /**
     * Example mapping configuration:
     *  [
     *      'pages' => [
     *          // old => new
     *          1 => 2,
     *          4 => 6
     *      ],
     *      'tt_content' => [
     *          3 => 66
     *      ]
     *  ]
     *
     * @var array
     */
    protected $mapping = [];

    /**
     * @param int $new
     * @param int $old
     * @param string $tableName
     * @return void
     */
    public function setNew(int $new, int $old, string $tableName): void
    {
        $this->mapping[$tableName][$old] = $new;
    }

    /**
     * @param int $new
     * @param int $old
     * @return void
     */
    public function setNewPid(int $new, int $old): void
    {
        $this->mapping['pages'][$old] = $new;
    }

    /**
     * @param int $old
     * @param string $tableName
     * @return int
     */
    public function getNewFromOld(int $old, string $tableName): int
    {
        return (int)$this->mapping[$tableName][$old];
    }

    /**
     * @param int $old
     * @return int
     */
    public function getNewPidFromOldPid(int $old): int
    {
        return (int)$this->mapping['pages'][$old];
    }

    /**
     * @param string $tableName
     * @return bool
     */
    public function isTableExisting(string $tableName): bool
    {
        return array_key_exists($tableName, $this->mapping);
    }

    /**
     * @return array
     */
    public function getMapping(): array
    {
        return $this->mapping;
    }
}
