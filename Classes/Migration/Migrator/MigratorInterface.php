<?php
namespace In2code\Migration\Migration\Migrator;

/**
 * Interface MigratorInterface
 */
interface MigratorInterface
{
    /**
     * @return void
     */
    public function start(): void;
}
