<?php

//#######################################################################
// Extension Manager/Repository config file for ext "in2template".
//#######################################################################

$EM_CONF[$_EXTKEY] = [
    'title' => 'migration',
    'description' => 'This extension is a boilerplate extension for any kind of in-database-migrations',
    'category' => 'misc',
    'author' => 'in2code GmbH',
    'author_email' => 'service@in2code.de',
    'dependencies' => 'extbase, fluid',
    'state' => 'stable',
    'author_company' => 'in2code GmbH',
    'version' => '2.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-8.99.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
