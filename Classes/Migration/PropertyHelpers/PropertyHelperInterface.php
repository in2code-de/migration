<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\PropertyHelpers;

interface PropertyHelperInterface
{
    public function __construct(
        array $record,
        array $recordOld,
        string $propertyName,
        string $table,
        array $configuration = [],
        array $migrationConfiguration = []
    );

    /**
     * Function is called before manipulate() (e.g. to do some checks before migration)
     *
     * @return void
     */
    public function initialize(): void;

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
