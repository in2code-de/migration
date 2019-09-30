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
         * Define in which fields there are one or more links and probably a wrapping text (normally a RTE) that should
         * be replaced with a newer mapping.
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
         * Define simple fields that only hold relations
         *
         * Example content (like pages.shortcut or tt_content.header_link) with relations/links:
         *  - "123" (link to page 123)
         *  - "123,124" (link to two pages)
         *  - "t3://page?uid=123" (link to page 123)
         */
        'propertiesWithRelations' => [
            'pages' => [
                'shortcut'
            ],
            'tt_content' => [
                'header_link',
                'records',
                'pages',
                'tx_gridelements_container'
            ],
            'sys_file_reference' => [
                'link'
            ]
        ]
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
     * Check if the file is already existing while importing (compare path and name - no size or date)
     * and decide if it should be overwritten or not
     */
    'overwriteFiles' => false
];
