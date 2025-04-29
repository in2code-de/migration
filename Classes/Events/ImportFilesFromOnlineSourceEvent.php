<?php

declare(strict_types=1);
namespace In2code\Migration\Events;

final class ImportFilesFromOnlineSourceEvent
{
    /**
     * Overwrite path to load from source. Like "https://domain.org/fileamdin/file.jpg"
     *
     * @var string
     */
    private string $onlineSource = '';

    /**
     * Disable loading from online source for some reason
     *
     * @var bool
     */
    private bool $toLoadFromSource = true;

    public function __construct(
        private string $path,
        private bool $overwriteFiles,
        private readonly array $configuration
    ) {
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

    public function getOnlineSource(): string
    {
        if ($this->onlineSource === '') {
            return ($this->getConfiguration()['importFilesFromOnlineResource'] ?? '') . $this->getPath();
        }
        return $this->onlineSource;
    }

    public function setOnlineSource(string $onlineSource): self
    {
        $this->onlineSource = $onlineSource;
        return $this;
    }

    public function disableToLoadFromSource(): self
    {
        $this->toLoadFromSource = false;
        return $this;
    }

    public function isToLoadFromSource(): bool
    {
        return $this->toLoadFromSource;
    }
}
