<?php
namespace In2code\Migration\MigrationOld\Service;

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class Log
 */
class Log implements SingletonInterface
{

    /**
     * @var int
     */
    protected $counter = 1;

    /**
     * @var float
     */
    protected $starttime = 0.00;

    /**
     * @var float
     */
    protected $executionTime = 0.00;

    /**
     * @var bool
     */
    protected $errorsOnly = false;

    /**
     * @var OutputInterface
     */
    protected $output = null;

    /**
     * Log constructor.
     */
    public function __construct()
    {
        $this->starttime = -microtime(true);
    }

    /**
     * Log destructor.
     */
    public function __destruct()
    {
        $this->outputLine('Runtime: ' . $this->getExecutionTime() . ' Seconds');
    }

    /**
     * @param OutputInterface $output
     * @return $this
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @param string $message
     * @param array $properties
     * @param string $tableName
     * @return void
     */
    public function addMessage(string $message, array $properties = [], string $tableName = '')
    {
        if (!$this->errorsOnly) {
            $this->outputLine('[OK] ' . $this->buildPrefix($properties, $tableName) . '"' . $message . '"');
        }
    }

    /**
     * @param string $message
     * @param array $properties
     * @param string $tableName
     * @return void
     */
    public function addNote(string $message, array $properties = [], string $tableName = '')
    {
        if (!$this->errorsOnly) {
            $this->outputLine('[NOTE] ' . $this->buildPrefix($properties, $tableName) . '"' . $message . '"');
        }
    }

    /**
     * @param string $message
     * @param array $properties
     * @param string $tableName
     * @return void
     */
    public function addError(string $message, array $properties = [], string $tableName = '')
    {
        $this->outputLine('[ERROR] ' . $this->buildPrefix($properties, $tableName) . '"' . $message . '"');
    }

    /**
     * @param array $properties
     * @param string $tableName
     * @return string
     */
    protected function buildPrefix(array $properties = [], string $tableName = ''): string
    {
        $prefix = '';
        if (!empty($tableName)) {
            $prefix .= 'Table:' . $tableName . ' ';
        }
        if (!empty($properties['uid'])) {
            $prefix .= 'UID:' . $properties['uid'] . ' ';
        }
        if (!empty($properties['pid'])) {
            $prefix .= 'PID:' . $properties['pid'] . ' ';
        }
        return $prefix;
    }

    /**
     * @param string $message
     * @return void
     */
    protected function outputLine(string $message)
    {
        if ($this->output !== null) {
            $counterString = str_pad($this->counter, 6, '0', STR_PAD_LEFT) . ': ';
            $this->output->writeln($counterString . $message . PHP_EOL);
            $this->counter++;
        }
    }

    /**
     * @return float
     */
    protected function getExecutionTime()
    {
        $this->stop();
        return $this->executionTime;
    }

    /**
     * Calculates and sets delta
     *
     * @return void
     */
    protected function stop()
    {
        if ($this->starttime < 0) {
            $this->executionTime = $this->starttime + microtime(true);
        }
    }
}
