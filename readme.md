# TYPO3 Migration and Importer Boilerplate

## Description
This extension (with example key in2template) is a kickstarter extension (boilerplate) 
to import or migrate TYPO3 stuff. 
Boilerplate means in this case, take the extension and change it to your needs.

E.g: 
* Import tt_news to news
* Migration tt_content (TemplaVoila to Gridelements)

## Introduction
CommandController commands can be added with a defined key - in this case "faq":

```
    /**
     * Migrate existing faq.
     *
     * @param bool $dryrun Test how many records could be imported (with "--dryrun=0")
     * @param string $limitToRecord 0=disable, 12=enable(all tables), table:123(only table.uid=123)
     * @param int $limitToPage 0=disable, 12=enable(all records with pid=12)
     * @param bool $recursive true has only an effect if limitToPage is set
     * @return void
     */
    public function migrateFaqCommand($dryrun = true, $limitToRecord = '0', $limitToPage = 0, $recursive = false)
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $starter = $this->objectManager->get(Starter::class, $this->output, 'faq');
        $starter->start($dryrun, $limitToRecord, $limitToPage, $recursive);
    }
    
```

In the starter class some importers or migrators are related to the key "faq":
```
protected $migrationClasses = [
    [
        'className' => FaqImporter::class,
        'configuration' => [
            'migrationClassKey' => 'faq'
        ]
    ],
    [
        'className' => FaqCategoriesProductImporter::class,
        'configuration' => [
            'migrationClassKey' => 'faq'
        ]
    ],
    [
        'className' => FaqCategoriesSysCategoryImporter::class,
        'configuration' => [
            'migrationClassKey' => 'faq'
        ]
    ],
    [
        'className' => CreateRelationsFromProductsImporter::class,
        'configuration' => [
            'migrationClassKey' => 'faq'
        ]
    ],
    [
        'className' => DeleteFaqSysCategoriesDatabaseScript::class,
        'configuration' => [
            'migrationClassKey' => 'faq'
        ]
    ]
];
```

Example Importer class:
```
<?php
namespace In2code\In2template\Migration\Import;

use In2code\In2template\Migration\Import\PropertyHelper\ReplaceCssClassesInHtmlStringPropertyHelper;

/**
 * Class FaqImporter
 */
class FaqImporter extends AbstractImporter implements ImporterInterface
{

    /**
     * Table name where to migrate to
     *
     * @var string
     */
    protected $tableName = 'tx_in2faq_domain_model_question';

    /**
     * Table name from migrate to
     *
     * @var string
     */
    protected $tableNameOld = 'tx_udgmvfaq_domain_model_question';

    /**
     * @var array
     */
    protected $mapping = [
        'question' => 'question',
        'answer' => 'answer',
        'crdate' => 'crdate'
    ];

    /**
     * PropertyHelpers are called after initial build via mapping
     *
     *      "newProperty" => [
     *          [
     *              "className" => class1::class,
     *              "configuration => ["red"]
     *          ],
     *          [
     *              "className" => class2::class
     *          ]
     *      ]
     *
     * @var array
     */
    protected $propertyHelpers = [
        'answer' => [
            [
                'className' => ReplaceCssClassesInHtmlStringPropertyHelper::class,
                'configuration' => [
                    'search' => [
                        'btn',
                        'btn-neg',
                        'btn-green',
                        'btn-blue',
                        'contenttable-2',
                        'contenttable-3',
                        'ul-check'
                    ],
                    'replace' => [
                        'c-button',
                        'c-button--white',
                        'c-button--green',
                        '',
                        'u-table-transparent',
                        'u-table-blue',
                        'u-list-check'
                    ]
                ]
            ]
        ]
    ];
}
```

Example for an individual PropertyHelper class:
```
<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

use In2code\In2template\Migration\Utility\StringUtility;

/**
 * Class ReplaceCssClassesInHtmlStringPropertyHelper
 * to replace css classes in a HTML-string - e.g. RTE fields like tt_content.bodytext
 *
 *  Configuration example:
 *      'configuration' => [
 *          'search' => [
 *              'class1'
 *          ],
 *          'replace' => [
 *              'class2'
 *          ]
 *      ]
 */
class ReplaceCssClassesInHtmlStringPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     * @throws \Exception
     */
    public function initialize()
    {
        if (!is_array($this->getConfigurationByKey('search')) || !is_array($this->getConfigurationByKey('replace'))) {
            throw new \Exception('configuration search and replace is missing');
        }
    }

    /**
     * @return void
     */
    public function manipulate()
    {
        $string = $this->getProperty();
        $replacements = $this->getConfigurationByKey('replace');
        foreach ($this->getConfigurationByKey('search') as $key => $searchterm) {
            $replace = $replacements[$key];
            $string = StringUtility::replaceCssClassInString($searchterm, $replace, $string);
        }
        $this->setProperty($string);
    }

    /**
     * @return bool
     */
    public function shouldImport(): bool
    {
        foreach ($this->getConfigurationByKey('search') as $searchterm) {
            if (stristr($this->getProperty(), $searchterm)) {
                return true;
            }
        }
        return false;
    }
}
```

## Some notes
* Migration: This means migrate existing records in an existing table
* Import: This menas to import values with some logic from table A to table B

In your Migrator or Importer class you can define which record should be changed in which way.
Normally you can choose via class properties:
* if tables should be truncated or not
* if the where clause should be extended to find old records
* change ordering
* if uid should be kept
* etc...

If you extend your new tables with fields like _migrated, _migrated_uid and _migrated_table, they will
be filled automaticly

## Example CLI calls
```
./vendor/bin/typo3cms mainmigration:migratefaq --dryrun=0
./vendor/bin/typo3cms mainmigration:migratenews --dryrun=1 --limittopage=1 --recursive=false
./vendor/bin/typo3cms mainmigration:migratenews --dryrun=0 --limittorecord=123
```
