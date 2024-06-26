<?php

declare(strict_types=1);
namespace In2code\Migration\Events;

final class ImportBeforeEvent
{
    private array $jsonArray;
    private int $pid;
    private string $file;
    private array $configuration;

    public function __construct(array $jsonArray, int $pid, string $file, array $configuration)
    {
        $this->jsonArray = $jsonArray;
        $this->pid = $pid;
        $this->file = $file;
        $this->configuration = $configuration;
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

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }
}
