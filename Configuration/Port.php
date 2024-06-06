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
        'sys_category_record_mm',
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
                'bodytext',
            ],
            'tx_news_domain_model_news' => [
                'bodytext',
            ],
            'tx_news_domain_model_tag' => [
                'seo_text',
            ],
            'tx_in2faq_domain_model_question' => [
                'answer',
            ],
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
                    'table' => 'pages',
                ],
            ],
            'tt_content' => [
                [
                    'field' => 'header_link',
                    'table' => 'pages',
                ],
                [
                    'field' => 'records',
                    'table' => 'pages',
                ],
                [
                    'field' => 'pages',
                    'table' => 'pages',
                ],
                [
                    'field' => 'tx_gridelements_container',
                    'table' => 'tt_content',
                ],
                [
                    'field' => 'tx_container_parent',
                    'table' => 'tt_content',
                ],
                [
                    'field' => 'tx_news_related_news',
                    'table' => 'tx_news_domain_model_news',
                ],
            ],
            'sys_file_reference' => [
                [
                    'field' => 'link',
                    'table' => 'pages',
                ],
            ],
            'tx_powermail_domain_model_mail' => [
                [
                    'field' => 'feuser',
                    'table' => 'fe_users',
                ],
            ],
            'tx_powermail_domain_model_answer' => [
                [
                    'field' => 'field',
                    'table' => 'tx_powermail_domain_model_field',
                ],
                [
                    'field' => 'mail',
                    'table' => 'tx_powermail_domain_model_mail',
                ],
            ],
            'tx_powermail_domain_model_page' => [
                [
                    'field' => 'form',
                    'table' => 'tx_powermail_domain_model_form',
                ],
            ],
            'tx_powermail_domain_model_field' => [
                [
                    'field' => 'page',
                    'table' => 'tx_powermail_domain_model_page',
                ],
                [
                    'field' => 'content_element',
                    'table' => 'tt_content',
                ],
            ],
            'tx_powermailcond_domain_model_conditioncontainer' => [
                [
                    'field' => 'form',
                    'table' => 'tx_powermail_domain_model_form',
                ],
            ],
            'tx_powermailcond_domain_model_condition' => [
                [
                    'field' => 'conditioncontainer',
                    'table' => 'tx_powermailcond_domain_model_conditioncontainer',
                ],
                [
                    'field' => 'target_field',
                    'table' => 'tx_powermail_domain_model_field',
                ],
            ],
            'tx_powermailcond_domain_model_rule' => [
                [
                    'field' => 'conditions',
                    'table' => 'tx_powermailcond_domain_model_condition',
                ],
                [
                    'field' => 'start_field',
                    'table' => 'tx_powermail_domain_model_field',
                ],
            ],
            'tx_news_domain_model_link' => [
                [
                    'field' => 'parent',
                    'table' => 'tx_news_domain_model_news',
                ],
            ],
            'tt_news_cat' => [
                [
                    'field' => 'parent_category',
                    'table' => 'tt_news_cat',
                ],
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
                            'Ctype' => 'powermail_pi1',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="main"]/language/field[@index="settings.flexform.main.form"]/value',
                        'table' => 'tx_powermail_domain_model_form',
                    ],
                    [
                        // powermail: where to save mails
                        'condition' => [
                            'Ctype' => 'powermail_pi1',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="main"]/language/field[@index="settings.flexform.main.pid"]/value',
                        'table' => 'pages',
                    ],
                    [
                        // powermail: where to save mails
                        'condition' => [
                            'Ctype' => 'powermail_pi1',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="thx"]/language/field[@index="settings.flexform.thx.redirect"]/value',
                        'table' => 'pages',
                    ],
                    [
                        // tx_news: categories
                        'condition' => [
                            'Ctype' => 'news_newsliststicky',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="sDEF"]/language/field[@index="settings.categories"]/value',
                        'table' => 'sys_category',
                    ],
                    [
                        // tx_news: startingpoint
                        'condition' => [
                            'Ctype' => 'news_newsliststicky',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="sDEF"]/language/field[@index="settings.startingpoint"]/value',
                        'table' => 'pages',
                    ],
                    [
                        // tx_news: detailPid
                        'condition' => [
                            'Ctype' => 'news_newsliststicky',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="additional"]/language/field[@index="settings.detailPid"]/value',
                        'table' => 'pages',
                    ],
                    [
                        // tx_news: listPid
                        'condition' => [
                            'Ctype' => 'news_newsliststicky',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="additional"]/language/field[@index="settings.listPid"]/value',
                        'table' => 'pages',
                    ],
                    [
                        // tx_news: backPid
                        'condition' => [
                            'Ctype' => 'news_newsliststicky',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="additional"]/language/field[@index="settings.backPid"]/value',
                        'table' => 'pages',
                    ],
                    [
                        // tx_news: categories
                        'condition' => [
                            'Ctype' => 'news_newsdetail',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="sDEF"]/language/field[@index="settings.categories"]/value',
                        'table' => 'sys_category',
                    ],
                    [
                        // tx_news: startingpoint
                        'condition' => [
                            'Ctype' => 'news_newsdetail',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="sDEF"]/language/field[@index="settings.startingpoint"]/value',
                        'table' => 'pages',
                    ],
                    [
                        // tx_news: detailPid
                        'condition' => [
                            'Ctype' => 'news_newsdetail',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="additional"]/language/field[@index="settings.detailPid"]/value',
                        'table' => 'pages',
                    ],
                    [
                        // tx_news: listPid
                        'condition' => [
                            'Ctype' => 'news_newsdetail',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="additional"]/language/field[@index="settings.listPid"]/value',
                        'table' => 'pages',
                    ],
                    [
                        // tx_news: backPid
                        'condition' => [
                            'Ctype' => 'news_newsdetail',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="additional"]/language/field[@index="settings.backPid"]/value',
                        'table' => 'pages',
                    ],
                    [
                        // tx_news: categories
                        'condition' => [
                            'Ctype' => 'news_newssearchresult',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="sDEF"]/language/field[@index="settings.categories"]/value',
                        'table' => 'sys_category',
                    ],
                    [
                        // tx_news: startingpoint
                        'condition' => [
                            'Ctype' => 'news_newssearchresult',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="sDEF"]/language/field[@index="settings.startingpoint"]/value',
                        'table' => 'pages',
                    ],
                    [
                        // tx_news: detailPid
                        'condition' => [
                            'Ctype' => 'news_newssearchresult',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="additional"]/language/field[@index="settings.detailPid"]/value',
                        'table' => 'pages',
                    ],
                    [
                        // tx_news: listPid
                        'condition' => [
                            'Ctype' => 'news_newssearchresult',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="additional"]/language/field[@index="settings.listPid"]/value',
                        'table' => 'pages',
                    ],
                    [
                        // tx_news: backPid
                        'condition' => [
                            'Ctype' => 'news_newssearchresult',
                        ],
                        'selection' => '//T3FlexForms/data/sheet[@index="additional"]/language/field[@index="settings.backPid"]/value',
                        'table' => 'pages',
                    ],
                ],
            ],
        ],
    ],

    /**
     * Special relations with MM-tables for ex- and import (tables are ignored if they don't exist in the system,
     * sys_file_reference and sys_file_metadata is handled separately)
     */
    'relations' => [
        'pages' => [
            [
                'table' => 'sys_category_record_mm',
                'uid_local' => 'sys_category',
                'uid_foreign' => 'pages',
                'additional' => [
                    'tablenames' => 'pages',
                    'fieldname' => 'categories',
                ],
            ],
        ],
        'tt_content' => [
            [
                'table' => 'sys_category_record_mm',
                'uid_local' => 'sys_category',
                'uid_foreign' => 'tt_content',
                'additional' => [
                    'tablenames' => 'tt_content',
                    'fieldname' => 'categories',
                ],
            ],
        ],
        'tx_news_domain_model_news' => [
            [
                'table' => 'sys_category_record_mm',
                'uid_local' => 'sys_category',
                'uid_foreign' => 'tx_news_domain_model_news',
                'additional' => [
                    'tablenames' => 'tx_news_domain_model_news',
                    'fieldname' => 'categories',
                ],
            ],
            [
                'table' => 'tx_news_domain_model_news_related_mm',
                'uid_local' => 'tx_news_domain_model_news',
                'uid_foreign' => 'tx_news_domain_model_news',
            ],
            [
                'table' => 'tx_news_domain_model_news_tag_mm',
                'uid_local' => 'tx_news_domain_model_news',
                'uid_foreign' => 'tx_news_domain_model_tag',
            ],
        ],
        'tt_news' => [
            [
                'table' => 'tt_news_cat_mm',
                'uid_local' => 'tt_news',
                'uid_foreign' => 'tt_news_cat',
            ],
            [
                'table' => 'tt_news_related_mm',
                'uid_local' => 'tt_news',
                'uid_foreign' => 'tt_news',
            ],
        ],
        'tx_in2faq_domain_model_question' => [
            [
                'table' => 'tx_in2faq_question_category_mm',
                'uid_local' => 'tx_in2faq_domain_model_question',
                'uid_foreign' => 'tx_in2faq_domain_model_category',
            ],
        ],
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
            'fileadmin/',
        ],
    ],

    /**
     * Check if the file is already existing while importing (compare path and name - no size or date)
     * and decide if it should be overwritten or not
     */
    'overwriteFiles' => false,

    /**
     * Decide if the related files (also linked files) should be added to json file or not. If files are not added,
     * the json will be much smaller because only the absolute URI is added to json. If you import the file on the
     * same system (where a URI like /var/www/domain.org/public/fileadmin/file.pdf) is available, the import will try
     * to get the resource from original URI.
     * This will also help you if you run into a memory limit issue while exporting.
     */
    'addFilesToJson' => false,
];
