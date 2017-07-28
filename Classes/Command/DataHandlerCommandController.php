<?php
namespace In2code\In2template\Command;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class DataHandlerCommandController
 */
class DataHandlerCommandController extends CommandController
{

    /**
     * Do a datahandler command on cli. Useful to copy or delete pages with the CLI.
     * A message at the end will show if the job was successfully done or if there are errors.
     *
     * @param int $startPid Which page should be handled
     * @param string $action "copy" or "move" or "delete"
     * @param int $targetPid Where to paste the result. 1 => first subpage in 1, -1 => after 1
     * @param int $recursion Recursion depth. 0 => Just current page, 1 => Current and subpages, etc...
     * @return void
     */
    public function handleCommand($startPid, $action = 'copy', $targetPid = 1, $recursion = 0)
    {
        $command = [];
        $command['pages'][(int)$startPid][$action] = (int)$targetPid;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->BE_USER = $GLOBALS['BE_USER'];
        $dataHandler->BE_USER->user['admin'] = 1;
        $dataHandler->userid = $GLOBALS['BE_USER']->user['uid'];
        $dataHandler->admin = true;
        $dataHandler->bypassAccessCheckForRecords = true;
        $dataHandler->copyTree = $recursion;
        $dataHandler->deleteTree = true;
        $dataHandler->neverHideAtCopy = true;
        $dataHandler->start([], $command);
        $dataHandler->process_cmdmap();
        $this->outputLine($this->getMessage($dataHandler));
    }

    /**
     * @param DataHandler $dataHandler
     * @return string
     */
    protected function getMessage(DataHandler $dataHandler)
    {
        $message = 'job successfully done!';
        if (!empty($dataHandler->errorLog)) {
            $message = 'Errors: ' . print_r($dataHandler->errorLog, true);
        }
        return $message;
    }
}
