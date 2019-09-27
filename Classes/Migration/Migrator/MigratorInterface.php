<?php
declare(strict_types=1);
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
