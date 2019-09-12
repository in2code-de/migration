<?php
namespace In2code\Migration\MigrationOld\Migrate\PropertyHelper;

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
     * Function is called before manipulate() (e.g. to do some checks before migration)
     *
     * @return void
     */
    public function initialize();

    /**
     * Function to manipulate a record array
     *
     * @return void
     */
    public function manipulate();

    /**
     * Will not call manipulate() if returns false
     *
     * @return bool
     */
    public function shouldMigrate(): bool;
}
