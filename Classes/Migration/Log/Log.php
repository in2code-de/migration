<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\Log;

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
        $this->writeLine('Runtime: ' . $this->getExecutionTime() . ' Seconds');
        $this->writeLine('Finished!');
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
            $this->writeLine('[OK] ' . $this->buildPrefix($properties, $tableName) . '"' . $message . '"');
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
            $this->writeLine('[NOTE] ' . $this->buildPrefix($properties, $tableName) . '"' . $message . '"');
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
        $this->writeLine('[ERROR] ' . $this->buildPrefix($properties, $tableName) . '"' . $message . '"');
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
            $prefix .= 'uid' . $properties['uid'] . ' ';
        }
        if (!empty($properties['pid'])) {
            $prefix .= 'pid' . $properties['pid'] . ' ';
        }
        return $prefix;
    }

    /**
     * @param string $message
     * @return void
     */
    protected function writeLine(string $message)
    {
        if ($this->output !== null) {
            $counterString = str_pad((string)$this->counter, 6, '0', STR_PAD_LEFT) . ': ';
            $this->output->writeln($counterString . $message);
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
