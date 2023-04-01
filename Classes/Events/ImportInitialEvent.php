<?php

declare(strict_types=1);
namespace In2code\Migration\Events;

final class ImportInitialEvent
{
    private string $file;
    private int $pid;
    private array $configuration;

    public function __construct(string $file, int $pid, array $configuration)
    {
        $this->file = $file;
        $this->pid = $pid;
        $this->configuration = $configuration;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function setFile(string $file): self
    {
        $this->file = $file;
        return $this;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function setPid(int $pid): self
    {
        $this->pid = $pid;
        return $this;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): self
    {
        $this->configuration = $configuration;
        return $this;
    }
}
