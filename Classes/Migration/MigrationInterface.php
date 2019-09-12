<?php
namespace In2code\Migration\Migration;

/**
 * Interface MigrationInterface
 */
interface MigrationInterface
{
    /**
     * @return void
     */
    public function start(): void;
}
