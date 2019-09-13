# TYPO3 Migration and Importer Boilerplate

## Description

This extension (**EXT:migration**) is a helper extension for your TYPO3 updates and migrations based
on CLI commands (to prevent timeouts, use a better performance, etc...).

What can this extension do for you:
* migrates table values
* import tables values from other tables
* exports whole page branches with all records and files as json
* imports whole page branches with all records and files from json into an existing table (and gives new identifiers and relations)
* does page actions (move, copy and delete) from CLI

This boilerplate extension (similar to a framework: take it, adjust it to your needs, make your migration and delete it at the end) helped
in2code in some really large projects to migrate some stuff - e.g.:
* tt_news to tx_news
* templatevoila to backendlayouts and gridelements
* mailform to powermail or mailform to form
* individual stuff to different individual stuff

**Note**:
If you want to use this extension for your migrations, you need a basic understanding of the database structure
of your TYPO3 instance. Because you have to set up the migrators and importers by yourself
(e.g. you have to know that tt_news.title will be migrated to tx_news_domain_model_news.title for your news
migration, etc...).


Some naming conventions:
* **Import** means here: Import stuff from an old to a new table (like from tt_news to tx_news_domain_model_news)
* **Migrate** means here: Migrate existing records in an existing table (like in tt_content from TemplaVoila to Gridelements)

## Introduction

### Possible roadmap for TYPO3 update and migration projects

If your migration comes along with a TYPO3 update (like from 6.2 to 9.5 or so), you should go this way:

* Start with a clean database and a new TYPO3 and build your functions in it with some testpages
* Add additional functions you need to your small test instance (like news, powermail, own content elements, etc...)
* Of course I would recommend to store the complete configuration (TypoScript, TSConfig etc...) in an extension 
* Import your old database
* Make a db compare (I would recommend the package **typo3_console** for this to do this from CLI)
* Make your update wizard steps (I would also recommend the package **typo3_console** for this to do this from CLI)
* Dump your new database
* Add a fork of this extension with key **migration** to your project or install it via composer `composer req in2code/migration`
* Start with adding your own Migrators and Importers to your extension
* And then have fun with migrating, rolling back database, update your scripts, migrate again, and so on
* If you are finished and have a good result, you simply can remove the extension
* See also https://www.slideshare.net/einpraegsam/typo3-migration-in-komplexen-upgrade-und-relaunchprojekten-114716116



## Hands on

### First migration

Let's say we want only a very small migration. CSS classes in tt_content.bodytext should be changed with some new
classes. 
Add a configuration php file anywhere to your extension (e.g. EXT:sitepackage/Configuration/Migration.php). Here
you define which migrators or importers should be run by your CLI commands. In this example there is only one migration.

```
<?php
return [
    // Default values if not given from CLI
    'configuration' => [
        'key' => '',
        'dryrun' => true,
        'limitToRecord' => null,
        'limitToPage' => null,
        'recursive' => false
    ],

    // Define your migrations
    'migrations' => [
        [
            'className' => \In2code\Migration\Migration\Migrator\ContentMigrator::class,
            'keys' => [
                'content'
            ]
        ]
    ]
];

```

Example Content Migrator class:
```
declare(strict_types=1);
namespace In2code\Migration\Migration\Migrator;

use In2code\Migration\Migration\PropertyHelpers\ReplaceCssClassesInHtmlStringPropertyHelper;

/**
 * Class ContentMigrator
 */
class ContentMigrator extends AbstractMigrator implements MigratorInterface
{
    /**
     * @var string
     */
    protected $tableName = 'tt_content';

    /**
     * @var array
     */
    protected $values = [
        'editlock' => '0'
    ];

    /**
     * @var array
     */
    protected $propertyHelpers = [
        'bodytext' => [
            [
                'className' => ReplaceCssClassesInHtmlStringPropertyHelper::class,
                'configuration' => [
                    'search' => [
                        'btn-green',
                        'btn-blue',
                        'btn'
                    ],
                    'replace' => [
                        'c-button--green',
                        'c-button--blue'
                        'c-button'
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
declare(strict_types=1);
namespace In2code\Migration\Migration\PropertyHelpers;

use In2code\Migration\Utility\StringUtility;

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
     * Check if this configuration keys are given
     *
     * @var array
     */
    protected $checkForConfiguration = [
        'search',
        'replace'
    ];

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
```
# Test it before on content elements on page 123
./vendor/bin/typo3cms migration:migrate --configuration typo3conf/ext/sitepackage/Configuration/Migration.php --dryrun 1 --key content --limitToPage 123

# Go for it
./vendor/bin/typo3cms migration:migrate --configuration typo3conf/ext/sitepackage/Configuration/Migration.php --dryrun 0 --key content
```



### First import

Let's say we want to simply copy some values from an old table to a new one with an individual mapping. In this
example I use tt_news and tx_news_domain_model_news. Go into your configuration file (see above) and enter your
Importer class name.


```
<?php
return [
    // Default values if not given from CLI
    'configuration' => [
        'key' => '',
        'dryrun' => true,
        'limitToRecord' => null,
        'limitToPage' => null,
        'recursive' => false
    ],

    // Define your migrations
    'migrations' => [
        [
            'className' => \In2code\Migration\Migration\Importer\NewsImporter::class,
            'keys' => [
                'news'
            ]
        ]
    ]
];
```

Example News Importer class:
```
<?php
declare(strict_types=1);
namespace In2code\Migration\Migration\Importer;

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
```
# Test it before on one news element with uid 123
./vendor/bin/typo3cms migration:migrate --configuration typo3conf/ext/sitepackage/Configuration/Migration.php --dryrun 1 --key news --limitToRecord 123

# Go for it
./vendor/bin/typo3cms migration:migrate --configuration typo3conf/ext/sitepackage/Configuration/Migration.php --dryrun 0 --key news
```




## Some notes
* Migration: This means migrate existing records in an existing table
* Import: This means to import values with some logic from table A to table B

In your Migrator or Importer class you can define which record should be changed in which way.
Normally you can choose via class properties:
* if tables should be truncated or not
* if the where clause should be extended to find old records
* change orderings
* if uid should be kept
* etc...

If you extend your new tables with fields like `_migrated`, `_migrated_uid` and `_migrated_table`, they will
be filled automatically with useful values - just test




## Example CLI calls
```
# Migrate and import everything which is tagged to "content" (Configuration from EXT:migration/Configuration/Migration.php is used)
./vendor/bin/typo3cms migration:migrate --dryrun 0 --key content

# Migrate and import with your configuration
./vendor/bin/typo3cms migration:migrate --configuration typo3conf/ext/sitepackage/Configuration/Migration.php

# Migrate and import everything which is tagged to "page" but test it (dryrun) and do it only for page with uid=1 and no subpages
./vendor/bin/typo3cms migration:migrate --key page --dryrun 1 --limitToPage 1 --recursive 0

# Migrate and import everything which is tagged to "content". Use only page with uid=123 and all subpages
./vendor/bin/typo3cms migration:migrate --key content --dryrun 0 --limitToPage 123 --recursive 1

# Migrate and import everything which is tagged to "news" but only for the record uid=123
./vendor/bin/typo3cms migration:migrate --key news --dryrun 0 --limitToRecord 123

# Use short wrtings instead of long names (with configuration, limit to record, dryrun and key)
./vendor/bin/typo3cms migration:migrate -c typo3conf/ext/migration/Configuration/Migration.php -l 23 -d 0 -k content
```




## Additional useful symfony commands in this extension

### DataHandlerCommand

Do TYPO3 pageactions (normally known from backend) via console. Move, delete, copy complete pages and trees without runtimelimit from CLI

Example CLI call

```
# Copy tree with beginning pid 123 into page with pid 234
./vendor/bin/typo3cms migration:datahandler 123 copy 234

# Move tree with beginning pid 123 into page with pid 234
./vendor/bin/typo3cms migration:datahandler 123 move 234

# Delete complete tree with beginning pid 123
./vendor/bin/typo3cms migration:datahandler 123 delete 0 99

```

### ExportCommand

Export a page branch into an json export file (with all files and relations)

Example CLI call

```
# Export page with pid123 and its subpages into a json file
./vendor/bin/typo3cms migration:export 123 > /home/user/export.json
```

### ImportCommand

Import a json file with exported data (e.g. a page branch) into an existing TYPO3 installation

Example CLI call

```
# Import page branch with subpages and files into page with uid 123
./vendor/bin/typo3cms migration:import /home/user/export.json 123
```

### HelpCommand

Simple show a commaseparated list of subpages to a page (helpful for further database commands)

Example CLI call

```
# Show a commaseparated list of a page with pid 123 and its subpages
./vendor/bin/typo3cms migration:help 123
```




## Changelog

| Version    | Date       | State      | Description                                                                  |
| ---------- | ---------- | ---------- | ---------------------------------------------------------------------------- |
| 4.0.0      | 2019-09-13 | Task       | Complete rewrite for TYPO3 9 with symfony tasks and doctrine, etc...         |
| 3.1.0      | 2019-03-19 | Feature    | Update RTE images, Export now with files from links                          |
| 3.0.0      | 2019-02-08 | Task       | Add a working import and export command controller                           |
| 2.0.0      | 2018-09-07 | Task       | Use extkey migration, add ImportExportCommandController, some improvements   |
| 1.1.1      | 2018-09-07 | Task       | Add Changelog                                                                |
| 1.1.0      | 2017-07-28 | Task       | Add DataHandler and Help CommandControllers                                  |
| 1.0.0      | 2017-07-26 | Task       | Initial release                                                              |




## Future Todos

* Add a fully functional generic importer - e.g. tt_news to tx_news
