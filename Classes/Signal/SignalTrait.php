<?php
declare(strict_types=1);
namespace In2code\Migration\Signal;

use In2code\Migration\Utility\ObjectUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;

/**
 * Trait SignalTrait
 */
trait SignalTrait
{
    /**
     * @var bool
     */
    protected $signalEnabled = true;

    /**
     * Instance a new signalSlotDispatcher and offer a signal
     *
     * @param string $signalClassName
     * @param string $signalName
     * @param array $arguments
     * @return array
     * @throws InvalidSlotException
     * @throws InvalidSlotReturnException
     */
    protected function signalDispatch(string $signalClassName, string $signalName, array $arguments): array
    {
        if ($this->isSignalEnabled()) {
            $signalSlotDispatcher = ObjectUtility::getObjectManager()->get(Dispatcher::class);
            return $signalSlotDispatcher->dispatch($signalClassName, $signalName, $arguments);
        }
        return [];
    }

    /**
     * @return boolean
     */
    protected function isSignalEnabled(): bool
    {
        return $this->signalEnabled;
    }

    /**
     * Signal can be disabled for testing
     *
     * @return void
     */
    protected function disableSignals()
    {
        $this->signalEnabled = false;
    }
}
