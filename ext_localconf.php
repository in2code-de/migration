<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(
    function ($extKey) {
        /**
         * CommandControllers
         */
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
            \In2code\Migration\Command\MigrateCommandController::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
            \In2code\Migration\Command\DataHandlerCommandController::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
            \In2code\Migration\Command\HelpCommandController::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
            \In2code\Migration\Command\ImportExportCommandController::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
            \In2code\Migration\Command\ExportJsonCommandController::class;

        unset($extKey);
    },
    'migration'
);
