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
     * Function is called before manipulate() (e.g. to do some checks before import)
     *
     * @return mixed
     */
    public function initialize();

    /**
     * Function to manipulate a record array before importing
     *
     * @return void
     */
    public function manipulate();

    /**
     * Will not call manipulate() if returns false
     *
     * @return bool
     */
    public function shouldImport(): bool;
}
