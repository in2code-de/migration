<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\Migrator;

interface MigratorInterface
{
    public function start(): void;
}
