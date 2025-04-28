<?php

declare(strict_types=1);
namespace In2code\Migration\Events;

final class ImportFilesFromUriEvent
{
    public function __construct(
        private string $uri,
        private string $path,
        private bool $overwriteFiles,
        private readonly array $configuration
    ){
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function isOverwriteFiles(): bool
    {
        return $this->overwriteFiles;
    }

    public function setOverwriteFiles(bool $overwriteFiles): self
    {
        $this->overwriteFiles = $overwriteFiles;
        return $this;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }
}
