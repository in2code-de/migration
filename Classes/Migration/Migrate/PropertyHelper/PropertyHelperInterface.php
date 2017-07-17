<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

/**
 * Interface PropertyHelperInterface
 */
interface PropertyHelperInterface
{

    /**
     * PropertyHelperInterface constructor.
     *
     * @param array $record
     * @param string $propertyName
     * @param string $table
     */
    public function __construct(array $record, string $propertyName, string $table);

    /**
     * @return mixed
     */
    public function initialize();

    /**
     * Function to return newRecord
     *
     * @return array
     */
    public function returnRecord(): array;
}
