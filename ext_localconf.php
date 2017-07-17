<?php

if (defined('TYPO3_MODE')) {
    // Extkey fallback
    if (!isset($_EXTKEY)) {
        $_EXTKEY = 'in2template';
    }

    /**
     * CommandControllers
     */
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
        \In2code\In2template\Command\MainMigrationCommandController::class;
}
