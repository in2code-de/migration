<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\PropertyHelpers;

/**
 * Interface PropertyHelperInterface
 */
interface PropertyHelperInterface
{

    /**
     * PropertyHelperInterface constructor.
     * @param array $record
     * @param string $propertyName
     * @param string $table
     * @param array $configuration
     */
    public function __construct(array $record, string $propertyName, string $table, array $configuration = []);

    /**
     * Function is called before manipulate() (e.g. to do some checks before migration)
     *
     * @return void
     */
    public function initialize(): void;

    /**
     * @return array
     */
    public function returnRecord(): array;

    /**
     * Function to manipulate a record array
     *
     * @return void
     */
    public function manipulate(): void;

    /**
     * Will not call manipulate() if returns false
     *
     * @return bool
     */
    public function shouldMigrate(): bool;
}
