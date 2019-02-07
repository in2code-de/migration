<?php
namespace In2code\Migration\Service;

/**
 * Class ImportMappingService
 */
class ImportMappingService
{

    /**
     * @var array
     */
    protected $mapping = [];

    /**
     * @param int $new
     * @param int $old
     * @param string $tableName
     * @return void
     */
    public function setIdentifierMapping(int $new, int $old, string $tableName)
    {
        $this->mapping[$tableName][$old] = $new;
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
}
