<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\Importer;

/**
 * Interface ImporterInterface
 */
interface ImporterInterface
{
    /**
     * @return void
     */
    public function start(): void;
}
