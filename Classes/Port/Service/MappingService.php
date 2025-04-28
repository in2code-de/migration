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
    protected array $mapping = [];

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
    protected array $configuration = [];

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    public function setNew(int $new, int $old, string $tableName): void
    {
        $this->mapping[$tableName][$old] = $new;
    }

    public function setNewPid(int $new, int $old): void
    {
        $this->mapping[self::TABLE_NAME_PAGES][$old] = $new;
    }

    public function getNewFromOld(int $old, string $tableName): int
    {
        if ($this->configuration['keepNotMatchingIdentifiers'] === true
            && ($this->mapping[$tableName][$old] ?? '') === '') {
            return $old;
        }
        return (int)($this->mapping[$tableName][$old] ?? 0);
    }

    public function getNewPidFromOldPid(int $old): int
    {
        return $this->getNewFromOld($old, self::TABLE_NAME_PAGES);
    }

    public function isTableExisting(string $tableName): bool
    {
        return array_key_exists($tableName, $this->mapping);
    }

    public function getMapping(): array
    {
        return $this->mapping;
    }
}
