<?php
declare(strict_types=1);
namespace In2code\Migration\Events;

final class ExportInitialEvent
{
    private int $pid;
    private int $recursive;
    private array $configuration;

    public function __construct(int $pid, int $recursive, array $configuration)
    {
        $this->pid = $pid;
        $this->recursive = $recursive;
        $this->configuration = $configuration;
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

    public function getRecursive(): int
    {
        return $this->recursive;
    }

    public function setRecursive(int $recursive): self
    {
        $this->recursive = $recursive;
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