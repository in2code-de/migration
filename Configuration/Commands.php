<?php
declare(strict_types=1);

use \In2code\Migration\Command\DataHandlerCommand;
use \In2code\Migration\Command\HelpCommand;
use \In2code\Migration\Command\ExportCommand;
use \In2code\Migration\Command\ImportCommand;
use \In2code\Migration\Command\MigrateCommand;

return [
    'migration:datahandler' => [
        'class' => DataHandlerCommand::class,
        'schedulable' => false
    ],
    'migration:help' => [
        'class' => HelpCommand::class,
        'schedulable' => false
    ],
    'migration:export' => [
        'class' => ExportCommand::class,
        'schedulable' => false
    ],
    'migration:import' => [
        'class' => ImportCommand::class,
        'schedulable' => false
    ],
    'migration:migrate' => [
        'class' => MigrateCommand::class,
        'schedulable' => false
    ]
];
