<?php

declare(strict_types=1);
namespace In2code\Migration\Migration\Log;

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\SingletonInterface;

class Log implements SingletonInterface
{
    protected int $counter = 1;

    protected float $starttime = 0.00;
    protected float $executionTime = 0.00;

    protected bool $errorsOnly = false;

    protected ?OutputInterface $output = null;

    public function __construct()
    {
        $this->starttime = -microtime(true);
    }

    public function __destruct()
    {
        $this->writeLine('Runtime: ' . $this->getExecutionTime() . ' Seconds');
        $this->writeLine('Finished!');
    }

    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;
        return $this;
    }

    public function addMessage(string $message, array $properties = [], string $tableName = ''): void
    {
        if (!$this->errorsOnly) {
            $this->writeLine('[OK] ' . $this->buildPrefix($properties, $tableName) . '"' . $message . '"');
        }
    }

    public function addNote(string $message, array $properties = [], string $tableName = ''): void
    {
        if (!$this->errorsOnly) {
            $this->writeLine('[NOTE] ' . $this->buildPrefix($properties, $tableName) . '"' . $message . '"');
        }
    }

    public function addError(string $message, array $properties = [], string $tableName = ''): void
    {
        $this->writeLine('[ERROR] ' . $this->buildPrefix($properties, $tableName) . '"' . $message . '"');
    }

    protected function buildPrefix(array $properties = [], string $tableName = ''): string
    {
        $prefix = '';
        if (!empty($tableName)) {
            $prefix .= 'Table:' . $tableName . ' ';
        }
        if (!empty($properties['uid'])) {
            $prefix .= 'uid' . $properties['uid'] . ' ';
        }
        if (!empty($properties['pid'])) {
            $prefix .= 'pid' . $properties['pid'] . ' ';
        }
        return $prefix;
    }

    protected function writeLine(string $message): void
    {
        if ($this->output !== null) {
            $counterString = str_pad((string)$this->counter, 6, '0', STR_PAD_LEFT) . ': ';
            $this->output->writeln($counterString . $message);
            $this->counter++;
        }
    }

    protected function getExecutionTime(): float
    {
        $this->stop();
        return $this->executionTime;
    }

    /**
     * Calculates and sets delta
     *
     * @return void
     */
    protected function stop(): void
    {
        if ($this->starttime < 0) {
            $this->executionTime = $this->starttime + microtime(true);
        }
    }
}
