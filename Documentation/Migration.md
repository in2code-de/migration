## Migration and Imports

### First migration

First of all, add a new extension `migration_extend` to your system

A composer.json file could look like:

```
{
  "name": "in2code/migration_extend",
  "type": "typo3-cms-extension",
  "license": "GPL-2.0+",
  "require": {
    "in2code/migration": "^10.0"
  },
  "autoload": {
    "psr-4": {
      "In2code\\MigrationExtend\\": "Classes"
    }
  },
  "extra": {
    "typo3/cms": {
      "extension-key": "migration_extend"
    }
  }
}

```

A small `ext_emconf.php` could look like:

```
<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'migration_extend',
    'description' => 'Migration configuration',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'migration' => '*',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
```

Let's say we want only a very small migration. CSS classes in tt_content.bodytext should be changed with some new
classes. 
Add a configuration php file in EXT:migration_extend/Configuration/Migration.php. Here
you define which migrators or importers should be run by your CLI commands:

```
<?php
return [
    // Default values if not given from CLI
    'configuration' => [
        'key' => '',
        'dryrun' => true,
        'limitToRecord' => null,
        'limitToPage' => 1,
        'recursive' => true
    ],

    // Define your migrations
    'migrations' => [
        [
            'className' => \Vendor\MigrationExtend\Migration\Migrator\ContentMigrator::class,
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
namespace Vendor\MigrationExtend\Migration\Migrator;

use In2code\Migration\Migration\Migrator\AbstractMigrator;
use In2code\Migration\Migration\Migrator\MigratorInterface;
use In2code\Migration\Migration\PropertyHelpers\ReplaceCssClassesInHtmlStringPropertyHelper;

/**
 * Class ContentMigrator
 */
class ContentMigrator extends AbstractMigrator implements MigratorInterface
{
    /**
     * This table should be migrated
     *
     * @var string
     */
    protected $tableName = 'tt_content';

    /**
     * Set some hardcoded values in your tt_content.* fields
     *
     * @var array
     */
    protected $values = [
        'cruser_id' => 123,
        'subheader' => 'Subheader of {properties.header}',
        'header_layout' => '{f:if(condition:properties.header,then:"2")}'
    ];

    /**
     * Add a bit more magic to the properties with PropertyHelper classes
     * You can use existing property helpers (in EXT:migration/Classes/Migration/PropertyHelpers) or simply write new
     * ones in your extension
     *
     * @var array
     */
    protected $propertyHelpers = [
        'bodytext' => [
            [
                // Replace some old classnames with new classnames im tt_content.bodytext
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

    /**
     * Optional: This SQL statements should be fired at the begin or at the end of this migration
     *
     * @var array
     */
    protected $sql = [
        'start' => [],
        'end' => [
            'delete from sys_file_reference where tablenames="pages" and fieldname="headerslider"'
        ]
    ];
}
```

Example for an individual PropertyHelper class. Function `shouldMigrate()` decides if the current record should be
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
    public function shouldMigrate(): bool
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
./vendor/bin/typo3 migration:migrate --configuration EXT:migration_extend/Configuration/Migration.php --dryrun 1 --key content --limitToPage 123

# Go for it
./vendor/bin/typo3 migration:migrate --configuration EXT:migration_extend/Configuration/Migration.php --dryrun 0 --key content
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
            'className' => \Vendor\MigrationExtend\Migration\Importer\NewsImporter::class,
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
namespace Vendor\MigrationExtend\Migration\Importer;

use In2code\Migration\Migration\Importer\AbstractImporter;
use In2code\Migration\Migration\Importer\ImporterInterface;

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
./vendor/bin/typo3 migration:migrate --configuration EXT:migration_extend/Configuration/Migration.php --dryrun 1 --key news --limitToRecord 123

# Go for it
./vendor/bin/typo3 migration:migrate --configuration EXT:migration_extend/Configuration/Migration.php --dryrun 0 --key news
```




## Some hints

### Change behaviour of your migrators/importers

In your Migrator or Importer class you can overide properties from the parent class to change the basic behaviour.
Examples:
* $additionalWhere: Extend the where clause for your import/migration (e.g. "and pid>0")
* $groupBy: Group by your records
* $orderBy: Change default sorting (pid,uid) of your records
* $truncate: Define if tables should be truncated or not before importing
* $sql: Fire some sql statements at the beginning or at the end of your migration/imports
* $keepIdentifiers: Define if new identifiers should be set while importing
* $enforce: Enforce a second migration (even if records are tagged with field _migrated as migrated)

### Tag migrated records

If you extend your new tables with fields with this names: `_migrated`, `_migrated_uid` and `_migrated_table`, they will
be filled automatically with useful values - just test

Example ext_tables.sql file to extend a table with new fields:

```
CREATE TABLE tt_content
(
	_migrated       tinyint(4) unsigned DEFAULT '0' NOT NULL,
	_migrated_uid   int(11) unsigned DEFAULT '0' NOT NULL,
	_migrated_table varchar(255) DEFAULT '' NOT NULL,
);
```


## Example CLI calls
```
# Migrate and import everything which is tagged to "content" (Configuration from EXT:migration/Configuration/Migration.php is used)
./vendor/bin/typo3 migration:migrate --dryrun 0 --key content

# Migrate and import with your configuration
./vendor/bin/typo3 migration:migrate --configuration EXT:migration_extend/Configuration/Migration.php

# Migrate and import everything which is tagged to "page" but test it (dryrun) and do it only for page with uid=1 and no subpages
./vendor/bin/typo3 migration:migrate --key page --dryrun 1 --limitToPage 1 --recursive 0

# Migrate and import everything which is tagged to "content". Use only page with uid=123 and all subpages
./vendor/bin/typo3 migration:migrate --key content --dryrun 0 --limitToPage 123 --recursive 1

# Migrate and import everything which is tagged to "news" but only for the record uid=123
./vendor/bin/typo3 migration:migrate --key news --dryrun 0 --limitToRecord 123

# Use short wrtings instead of long names (with configuration, limit to record, dryrun and key)
./vendor/bin/typo3 migration:migrate -c typo3conf/ext/migration/Configuration/Migration.php -l 23 -d 0 -k content
```

Parameters of the migration:migrate command (overwrite your settins of your Migration.php):

| Parameter name  | Parameter short name | Values | Description                                                                    |
|-----------------|----------------------|--------|--------------------------------------------------------------------------------|
| --configuration | -c                   | string | Path to your configuration file. If not given, default config file is used     |
| --key           | -k                   | string | Define which migrators/importers should run. Empty=all will run.               |
| --dryrun        | -d                   | 0/1    | Test migration before migrating/importing                                      |
| --limitToRecord | -l                   | int    | Do only a migration/import for record with this uid                            |
| --limitToPage   | -p                   | int    | Do only a migration/import for records on this page uid                        |
| --recursive     | -r                   | 0/1    | Can combined with limitToPage to also migrate/import records on children pages |
