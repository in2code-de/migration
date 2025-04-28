<?php

declare(strict_types=1);
namespace In2code\Migration\Events;

final class ExportBeforeEvent
{
    public function __construct(
        private array $jsonArray,
        private readonly int $pid,
        private readonly int $recursive,
        private readonly array $configuration
    ) {
    }

    public function getJsonArray(): array
    {
        return $this->jsonArray;
    }

    public function setJsonArray(array $jsonArray): self
    {
        $this->jsonArray = $jsonArray;
        return $this;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function getRecursive(): int
    {
        return $this->recursive;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }
}
