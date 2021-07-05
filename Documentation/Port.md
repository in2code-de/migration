## Port: Import and Export via CLI

### ExportCommand

Export a page branch into an json export file (with all files and relations)

Example CLI call

```
# Export page with pid123 and its subpages into a json file
./vendor/bin/typo3cms migration:export 123 > /home/user/export.json

# Export only page 123 (without children) and use own configuration
./vendor/bin/typo3cms migration:export 123 0 EXT:migration_extend/Configuration/Port.php > /home/user/export.json
```

**Note**: In your configuration file it is defined which relations, which mm-tables and some more should be used. 
See EXT:migration/Configuration/Port.php for an example.

### ImportCommand

Import a json file with exported data (e.g. a page branch) into an existing TYPO3 installation

Example CLI call

```
# Import page branch with subpages and files into page with uid 123
./vendor/bin/typo3cms migration:import /home/user/export.json 123

# Import page branch with subpages and files into page with uid 123 and use own configuration file
./vendor/bin/typo3cms migration:import /home/user/export.json 123 EXT:migration_extend/Configuration/Port.php
```

**Note**: In your configuration file it is defined which relations, which mm-tables and some more should be used. 
See EXT:migration/Configuration/Port.php for an example.


### Configuration file

This is a default configuration file that can be stored in your extension e.g. in path 
EXT:migration_extend/Configuration/Port.php

```
<?php
return [
    /**
     * Exclude tables from ex- and import
     */
    'excludedTables' => [
        'be_groups',
        'be_users',
        'sys_language',
        'sys_log',
        'sys_news',
        'sys_domain',
        'sys_template',
        'sys_note',
        'sys_history',
        'sys_file_storage',
        'sys_filemounts',
        'tx_extensionmanager_domain_model_extension',
        'tx_extensionmanager_domain_model_repository',
        'sys_category_record_mm'
    ],

    /**
     * Update links with new identifiers when importing records (after the import).
     * Here you can define which fields handle those links.
     */
    'linkMapping' => [
        /**
         * Links in RTE:
         * Define in which fields there are one or more links and probably a wrapping text (normally a RTE) that should
         * be replaced with a newer mapping.
         * This configuration is used twice:
         *      1) Import: Change links after importing
         *      2) Export: Find out which RTE keep links to files that should be added to the json
         *
         * Example content (like tt_content.bodytext) with links:
         * ... <a href="t3://page?uid=123">link</a> ...
         * and images in rte like:
         * ... <img src="fileadmin/image.png" data-htmlarea-file-uid="16279" data-htmlarea-file-table="sys_file" /> ...
         */
        'propertiesWithLinks' => [
            'tt_content' => [
                'bodytext'
            ],
            'tx_news_domain_model_news' => [
                'bodytext'
            ]
        ],

        /**
         * Simple UIDs in single fields:
         * Define simple fields that only hold relations (mostly to pages records)
         *
         * Example content (like pages.shortcut or tt_content.header_link) with relations/links:
         *  - "123" (link to page 123)
         *  - "123,124" (link to two pages)
         *  - "t3://page?uid=123" (link to page 123)
         */
        'propertiesWithRelations' => [
            'pages' => [
                [
                    'field' => 'shortcut',
                    'table' => 'pages'
                ]
            ],
            'tt_content' => [
                [
                    'field' => 'header_link',
                    'table' => 'pages'
                ],
                [
                    'field' => 'records',
                    'table' => 'pages'
                ],
                [
                    'field' => 'pages',
                    'table' => 'pages'
                ],
                [
                    'field' => 'tx_gridelements_container',
                    'table' => 'tt_content'
                ]
            ],
            'sys_file_reference' => [
                [
                    'field' => 'link',
                    'table' => 'pages'
                ]
            ],
            'tx_powermail_domain_model_mail' => [
                [
                    'field' => 'feuser',
                    'table' => 'fe_users'
                ]
            ],
            'tx_powermail_domain_model_answer' => [
                [
                    'field' => 'field',
                    'table' => 'tx_powermail_domain_model_field'
                ],
                [
                    'field' => 'mail',
                    'table' => 'tx_powermail_domain_model_mail'
                ]
            ],
            'tx_powermail_domain_model_page' => [
                [
                    'field' => 'forms',
                    'table' => 'tx_powermail_domain_model_form'
                ]
            ],
            'tx_powermail_domain_model_field' => [
                [
                    'field' => 'pages',
                    'table' => 'tx_powermail_domain_model_page'
                ],
                [
                    'field' => 'content_element',
                    'table' => 'tt_content'
                ]
            ],
            'tt_news_cat' => [
                [
                    'field' => 'parent_category',
                    'table' => 'tt_news_cat'
                ]
            ],
            'tx_inline_relation' => [
                [
                    'field' => 'parent_record',
                    'table' => 'tx_inline_origin',
                    'conditions' => [
                        'parent_table' => 'tx_inline_origin'
                    ]
                ]
            ],
        ],

        /**
         * Simple UIDs in FlexForm value fields:
         * Define some FlexForm fields where UIDs of records (mostly pages) should be updated
         *
         *  - "123" (link to page 123)
         *  - "123,124" (link to two pages)
         *  - "t3://page?uid=123" (link to page 123)
         */
        'propertiesWithRelationsInFlexForms' => [
            'tt_content' => [
                'pi_flexform' => [
                    [
                        // powermail: form selection
                        'condition' => [
                            'Ctype' => 'list',
                            'list_type' => 'powermail_pi1'
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="main"]/language/field[@index="settings.flexform.main.form"]/value',
                        'table' => 'tx_powermail_domain_model_form'
                    ],
                    [
                        // powermail: where to save mails
                        'condition' => [
                            'Ctype' => 'list',
                            'list_type' => 'powermail_pi1'
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="main"]/language/field[@index="settings.flexform.main.pid"]/value',
                        'table' => 'pages'
                    ],
                    [
                        // powermail: where to save mails
                        'condition' => [
                            'Ctype' => 'list',
                            'list_type' => 'powermail_pi1'
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="thx"]/language/field[@index="settings.flexform.thx.redirect"]/value',
                        'table' => 'pages'
                    ],
                    [
                        // tt_news PIDitemDisplay
                        'condition' => [
                            'Ctype' => 'list',
                            'list_type' => '9'
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="s_misc"]/language/field[@index="PIDitemDisplay"]/value',
                        'table' => 'pages'
                    ],
                    [
                        // tt_news backPid
                        'condition' => [
                            'Ctype' => 'list',
                            'list_type' => '9'
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="s_misc"]/language/field[@index="backPid"]/value',
                        'table' => 'pages'
                    ],
                    [
                        // tt_news pages
                        'condition' => [
                            'Ctype' => 'list',
                            'list_type' => '9'
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="s_misc"]/language/field[@index="pages"]/value',
                        'table' => 'pages'
                    ],
                    [
                        // tt_news pages
                        'condition' => [
                            'Ctype' => 'list',
                            'list_type' => '9'
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="sDEF"]/language/field[@index="categorySelection"]/value',
                        'table' => 'tt_news_cat'
                    ]
                ]
            ]
        ],
    ],

    /**
     * Special relations with MM-tables for ex- and import (tables are ignored if they don't exist in the system,
     * sys_file_reference is handled separately)
     */
    'relations' => [
        'pages' => [
            [
                'table' => 'sys_category_record_mm',
                'uid_local' => 'sys_category',
                'uid_foreign' => 'pages',
                'additional' => [
                    'tablenames' => 'pages',
                    'fieldname' => 'categories'
                ]
            ]
        ],
        'tt_content' => [
            [
                'table' => 'sys_category_record_mm',
                'uid_local' => 'sys_category',
                'uid_foreign' => 'tt_content',
                'additional' => [
                    'tablenames' => 'tt_content',
                    'fieldname' => 'categories'
                ]
            ]
        ],
        'tt_news' => [
            [
                'table' => 'tt_news_cat_mm',
                'uid_local' => 'tt_news',
                'uid_foreign' => 'tt_news_cat'
            ],
            [
                'table' => 'tt_news_related_mm',
                'uid_local' => 'tt_news',
                'uid_foreign' => 'tt_news'
            ]
        ]
    ],

    /**
     * If you import a json and some links points outside of this branch, identifiers can not be updated of course.
     * Per default, those identifiers are replaced with 0. If you want, you can keep old identifiers in links.
     */
    'keepNotMatchingIdentifiers' => false,

    /**
     * Attach files from oldschool links or embedded images in RTE fields like
     * <a href="fileadmin/file.pdf">file</a> OR
     * <img src="fileadmin/image.jpg">
     */
    'addFilesFromFileadminLinks' => [
        'paths' => [
            // don't forget the trailing slash
            'fileadmin/'
        ]
    ],

    /**
     * Check if the file is already existing while importing (compare path and name - no size or date)
     * and decide if it should be overwritten or not
     */
    'overwriteFiles' => false,

    /**
     * Decide if the related files (also linked files) should be added to json file or not. If files are not added,
     * the json will be much smaller because only the absolute URI is added to json. If you will import the file on the
     * same system (where an URI like /var/www/domain.org/public/fileadmin/file.pdf) is available, the import will try
     * to get the resource from original URI.
     * This will also help you if you run into a memory limit issue while exporting.
     */
    'addFilesToJson' => false
];
```

### FAQ

#### Q: Export/Import does not handle all my relations of extension XYZ

At the moment relations are defined in the configuration file (see above). If you miss some relations, you simply have
to add those to your configuration.

#### Q: Export files are very large? Memory limit reached on export?

The most reason for very large export files are embedded files. You can turn of file embedding via configuration file 
(see above).

#### Q: Export stops with "JSON can not be created from array. Maybe there is a charset issue in your data?"

On a few of our exports that we've done with this tool, there was a charset issue with tt_content.l18n_diffsource.
At the moment you have to clean this column before exporting if you run into the same issue.
