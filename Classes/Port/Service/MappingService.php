<?php
declare(strict_types=1);
namespace In2code\Migration\Port\Service;

/**
 * Class MappingService
 * offers functionality for a mapping from old to new identifiers while runtime of an import.
 */
class MappingService
{
    const TABLE_NAME_PAGES = 'pages';

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
     * Hold the complete configuration like
     *
     *  'excludedTables' => [
     *      'be_users'
     *  ],
     *  'keepNotMatchingIdentifiers' => false
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * MappingService constructor.
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

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
        $this->mapping[self::TABLE_NAME_PAGES][$old] = $new;
    }

    /**
     * @param int $old
     * @param string $tableName
     * @return int
     */
    public function getNewFromOld(int $old, string $tableName): int
    {
        if ($this->configuration['keepNotMatchingIdentifiers'] === true
            && empty($this->mapping[$tableName][$old])) {
            return $old;
        }
        return (int)$this->mapping[$tableName][$old];
    }

    /**
     * @param int $old
     * @return int
     */
    public function getNewPidFromOldPid(int $old): int
    {
        if ($this->configuration['keepNotMatchingIdentifiers'] === true
            && empty($this->mapping[self::TABLE_NAME_PAGES][$old])) {
            return $old;
        }
        return (int)$this->mapping[self::TABLE_NAME_PAGES][$old];
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
