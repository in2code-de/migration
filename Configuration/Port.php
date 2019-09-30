<?php
return [
    // Exclude tables from export and import
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

    // Special relations with MM-tables for ex- and import (tables are ignored if they don't exist in the system)
    'relations' => [
        'pages' => [
            [
                'table' => 'sys_category_record_mm',
                'relation' => 'pages.uid = sys_category_record_mm.uid_foreign and tablename="pages" and '
                    . 'fieldname="categories"',
                'targetTable' => 'sys_category',
                'targetRelation' => 'sys_category.uid = sys_category_record_mm.uid_local'
            ]
        ],
        'tt_news' => [
            [
                'table' => 'tt_news_cat_mm',
                'relation' => 'tt_news.uid = tt_news_cat_mm.uid_local'
            ],
            [
                'table' => 'tt_news_related_mm',
                'relation' => 'tt_news.uid = tt_news_related_mm.uid_local'
            ]
        ]
    ],

    /**
     * Check if the file is already existing while importing (compare path and name - no size or date)
     * and decide if it should be overwritten or not
     */
    'overwriteFiles' => false
];
