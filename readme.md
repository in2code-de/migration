# TYPO3 Migration and Importer Boilerplate

## Description
This extension (with extension key **migration**) is a kickstarter extension (boilerplate)
to import or migrate TYPO3 stuff within the same database.
Boilerplate means in this case, take the extension and change it to your needs.
The main task is to create easy migrations with just a few lines of code.

E.g: 
* **Import** from an old to a new table (like from tt_news to tx_news_domain_model_news)
* **Migrate** existing records in an existing table (like in tt_content from TemplaVoila to Gridelements)

## Introduction

### Possible roadmap for TYPO3 update and migration projects

If your migration comes along with a TYPO3 update (like from 6.2 to 8.7 or so), you should go this way:

* Start with a clean database and a new TYPO3 and build your functions in it with some testpages
* Add additional functions you need to your small test instance (like news, powermail, own content elements, etc...)
* Of course I would recommend to store the complete configuration (TypoScript, TSConfig etc...) in an extension 
* Import your old database
* Make a db compare (I would recommend the package **typo3_console** for this to do this from CLI)
* Make your update wizard steps (I would also recommend the package **typo3_console** for this to do this from CLI)
* Dump your new database
* Add a fork of this extension with key **migration** to your project (require-dev e.g. via composer)
* Start with adding your own Migrators and Importers
* And then have fun with migrating, rolling back database, update your scripts, migrate again, and so on
* If you are finished and have a good result, you simply can remove the extension
* See also https://www.slideshare.net/einpraegsam/typo3-migration-in-komplexen-upgrade-und-relaunchprojekten-113908250



## Hands on

### First migration

Let's say we want only a very small migration. CSS classes in tt_content.bodytext should be changed with some new
classes. Go into the file `\In2code\Migration\Migration\Starter` and clean the property $migrationClasses. In the first
step we only want a migration (because we want to manipulate existing values in an existing table - in this case
tt_content).

```
protected $migrationClasses = [
    [
        'className' => ContentMigrator::class,
        'configuration' => [
            'migrationClassKey' => 'content'
        ]
    ]
];
```

Example Content Migration class:
```
<?php
namespace In2code\Migration\Migration\Migrate;

use In2code\Migration\Migration\Migrate\PropertyHelper\ReplaceCssClassesInHtmlStringPropertyHelper;

/**
 * Class ContentMigrator
 */
class ContentMigrator extends AbstractMigrator implements MigratorInterface
{

    /**
     * Table to migrate
     *
     * @var string
     */
    protected $tableName = 'tt_content';

    /**
     * Hardcode some values in tt_content
     *
     * @var array
     */
    protected $values = [
        'linkToTop' => 0, // reset linkToTop
        'date' => 0
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
        'bodytext' => [
            [
                'className' => ReplaceCssClassesInHtmlStringPropertyHelper::class,
                'configuration' => [
                    'search' => [
                        'btn',
                        'btn-green',
                        'btn-blue'
                    ],
                    'replace' => [
                        'c-button',
                        'c-button--green',
                        'c-button--blue'
                    ]
                ]
            ]
        ]
    ];
}
```

Example for an individual PropertyHelper class. Function `shouldImport()` decices if the current record should be
manipulated or not. Function `manipulate()` is the main method which contains the magic.
A call of `$this-setProperty($newValue)` within manipulate the value of the current field.
An `initialize()` is always called before `manipulate()` for your very first tasks.

```
<?php
namespace In2code\Migration\Migration\Import\PropertyHelper;

use In2code\Migration\Migration\Utility\StringUtility;

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
            throw new \Exception('configuration search and replace is missing', 1525355698);
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

Start migration from CLI:

`./vendor/bin/typo3cms migrate:start --key=content --dryrun=0`



### First import

Let's say we want to simply copy some values from an old table to a new one with an individual mapping. In this
example I use tt_news and tx_news_domain_model_news. Go into the file `\In2code\Migration\Migration\Starter`
and add the importer to the property $migrationClasses.


```
protected $migrationClasses = [
    [
        'className' => NewsImporter::class,
        'configuration' => [
            'migrationClassKey' => 'news'
        ]
    ]
];
```

Example News Importer class:
```
<?php
namespace In2code\Migration\Migration\Import;

/**
 * Class NewsImporter
 */
class NewsImporter extends AbstractImporter implements ImporterInterface
{

    /**
     * New table should be truncated before each importer run
     *
     * @var bool
     */
    protected $truncate = true;

    /**
     * Use new values for .uid property
     *
     * @var bool
     */
    protected $keepIdentifiers = false;

    /**
     * Table to import to
     *
     * @var string
     */
    protected $tableName = 'tx_news_domain_model_news';

    /**
     * Table to import from
     *
     * @var string
     */
    protected $tableNameOld = 'tt_news';

    /**
     * Copy from old.fieldname to new.fieldname
     *
     * @var array
     */
    protected $mapping = [
        'title' => 'title',
        'short' => 'teaser',
        'bodytext' => 'bodytext'
    ];

    /**
     * Hardcode some properties
     *
     * @var array
     */
    protected $values = [
        'pid' => 123 // store news into this page
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
        // your own magic to manipulate values with your own classes
    ];
}
```

Start import from CLI:

`./vendor/bin/typo3cms migrate:start --key=news --dryrun=0`




## Some notes
* Migration: This means migrate existing records in an existing table
* Import: This menas to import values with some logic from table A to table B

In your Migrator or Importer class you can define which record should be changed in which way.
Normally you can choose via class properties:
* if tables should be truncated or not
* if the where clause should be extended to find old records
* change orderings
* if uid should be kept
* etc...

If you extend your new tables with fields like `_migrated`, `_migrated_uid` and `_migrated_table`, they will
be filled automaticly with useful values - just test

## Example CLI calls
```
# Migrate and import everything which is tagged to "content"
./vendor/bin/typo3cms migrate:start --key=content --dryrun=0

# Migrate and import everything which is tagged to "page" but test it (dryrun) and do it only for page with uid=1 and no subpages
./vendor/bin/typo3cms migrate:start --key=page --dryrun=1 --limit-to-page=1 --recursive=0

# Migrate and import everything which is tagged to "content". Use only page with uid=123 and all subpages
./vendor/bin/typo3cms migrate:start --key=content --dryrun=0 --limit-to-page=123 --recursive=99

# Migrate and import everything which is tagged to "news" but only for the record uid=123
./vendor/bin/typo3cms migrate:start --key=news --dryrun=0 --limit-to-record=123
```

## Additional useful CommandControllers in this extension

### DataHandlerCommandController

List of Commands:

- handleCommand(): Do TYPO3 pageactions (normally known from backend) via console. Move, delete, copy complete pages and trees without runtimelimit from CLI

Example CLI call

```
# Copy tree with beginning pid 123 into page with pid 234
./vendor/bin/typo3cms datahandler:handle --start-pid=123 --action=copy --target-pid=234

# Move tree with beginning pid 123 into page with pid 234
./vendor/bin/typo3cms datahandler:handle --start-pid=123 --action=move --target-pid=234

# Delete complete tree with beginning pid 123
./vendor/bin/typo3cms datahandler:handle --start-pid=123 --action=delete --recursion=99

```

### PortCommandController

List of Commands:

- exportCommand(): Export a page branch into an json export file (with files and relations)
- importCommand(): Import a json file with exported data (e.g. a page branch) into an existing TYPO3 installation

Example CLI call

```
# Export page with pid123 and its subpages into a json file
./vendor/bin/typo3cms port:export 123 > /home/user/export.json

# Import page branch with subpages and files into page with uid 123
./vendor/bin/typo3cms port:import /home/user/export.json 123
```

### HelpCommandController

List of Commands:

- getListsOfSubPagesCommand(): Simple show a commaseparated list of subpages to a page (helpful for further database commands)

Example CLI call

```
# Show a commaseparated list of a page with pid 123 and its subpages
./vendor/bin/typo3cms help:getlistsofsubpages --start-pid=123
```


## Changelog

| Version    | Date       | State      | Description                                                                  |
| ---------- | ---------- | ---------- | ---------------------------------------------------------------------------- |
| 2.0.0      | 2018-09-07 | Task       | Use extkey migration, add ImportExportCommandController, some improvements   |
| 1.1.1      | 2018-09-07 | Task       | Add Changelog                                                                |
| 1.1.0      | 2017-07-28 | Task       | Add DataHandler and Help CommandControllers                                  |
| 1.0.0      | 2017-07-26 | Task       | Initial release                                                              |

## Future Todos

* Rewrite all database queries with doctrine methods to enable migration extension also for TYPO3 9.x
* Add a fully functional generic importer - e.g. tt_news to tx_news
