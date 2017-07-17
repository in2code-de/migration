<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

/**
 * Interface PropertyHelperInterface
 */
interface PropertyHelperInterface
{

    /**
     * PropertyHelperInterface constructor.
     *
     * @param array $oldRecord
     * @param array $newRecord
     * @param string $propertyName
     * @param string $oldTable
     * @param string $newTable
     * @param array $configuration
     */
    public function __construct(
        array $oldRecord,
        array $newRecord,
        string $propertyName,
        string $oldTable,
        string $newTable,
        array $configuration = []
    );

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
