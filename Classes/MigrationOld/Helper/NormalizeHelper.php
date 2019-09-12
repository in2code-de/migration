<?php
namespace In2code\Migration\MigrationOld\Helper;

/**
 * Class NormalizeHelper
 */
class NormalizeHelper
{

    /**
     * Try to normalize a record
     *      ['abc', '123'] => ['abc', 123]
     *
     * @param array $record
     * @return array
     */
    public function normalizeRecord(array $record): array
    {
        foreach ($record as &$property) {
            $property = $this->normalizeProperty($property);
        }
        return $record;
    }

    /**
     * @param int|string $property
     * @return int|string
     */
    protected function normalizeProperty($property)
    {
        if (is_numeric($property)) {
            $property = (int)$property;
        }
        return $property;
    }
}
