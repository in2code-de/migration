<?php

declare(strict_types=1);
namespace In2code\Migration\Events;

final class ImportBeforeEvent
{
    public function __construct(
        private array $jsonArray,
        private int $pid,
        private string $file,
        private readonly array $configuration
    ) {
    }

    public function getJsonArray(): array
    {
        return $this->jsonArray;
    }

    public function setJsonArray(array $jsonArray): void
    {
        $this->jsonArray = $jsonArray;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function setFile(string $file): void
    {
        $this->file = $file;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }
}
