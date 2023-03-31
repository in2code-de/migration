<?php
declare(strict_types=1);
namespace In2code\Migration\Events;

final class ExportBeforeEvent
{
    private array $jsonArray;
    private int $pid;
    private int $recursive;
    private array $configuration;

    public function __construct(array $jsonArray, int $pid, int $recursive, array $configuration)
    {
        $this->jsonArray = $jsonArray;
        $this->pid = $pid;
        $this->recursive = $recursive;
        $this->configuration = $configuration;
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