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

    public function getPid(): int
    {
        return $this->pid;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }
}