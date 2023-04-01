<?php

declare(strict_types=1);
namespace In2code\Migration\Migration\Importer;

interface ImporterInterface
{
    public function start(): void;
}
