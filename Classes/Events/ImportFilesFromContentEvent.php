<?php

declare(strict_types=1);
namespace In2code\Migration\Events;

final class ImportFilesFromContentEvent
{
    public function __construct(
        private string $base64content,
        private string $path,
        private bool $overwriteFiles,
        private readonly array $configuration
    ){
    }

    public function getBase64content(): string
    {
        return $this->base64content;
    }

    public function setBase64content(string $base64content): self
    {
        $this->base64content = $base64content;
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
