# TYPO3 Migration Framework

## Description

This extension (**EXT:migration**) is a helper extension for your TYPO3 updates and migrations based
on CLI commands (to prevent timeouts, use a better performance, etc...).

What can this extension do for you:
* [Migration](Documentation/Migration.md) of table values
* [Import](Documentation/Migration.md) tables values from other tables
* [Port: Export](Documentation/Port.md) of whole page branches with all records and files as json
* [Port: Import](Documentation/Port.md) of whole page branches with all records and files from json into an existing table (and gives new identifiers and relations)
* [Page actions](Documentation/Helper.md) (move, copy and delete) from CLI

This framework extension helped us (in2code) in some really large projects to migrate some stuff - e.g.:
* old backendlayouts to new backendlayouts
* tt_news to tx_news
* templatevoila to backendlayouts and gridelements
* mailform to powermail or mailform to form
* individual stuff to different individual stuff

**Note**:
This extension is not a ready-to-use extension for your (e.g.) tt_news to tx_news migration. 
In my eyes it's nearly not possible to build a one-solves-all migrator extension that automatically fits all needs of 
your installation.
Because TYPO3 instances can be build in such different ways an individual configuration is often needed.

**Note2**:
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

* Update
  * Start with an empty database and a new TYPO3 9.5 and build your functions in it with some testpages
  * Add additional functions that are needed to your small test instance (like news, powermail, own content elements, etc...)
  * Of course I would recommend to store the complete configuration (TypoScript, TSConfig etc...) in an extension (sitepackage)
* Preperation
  * Import your old database into the new instance
  * Make a db compare (I would recommend the package **typo3_console** for this to do this from CLI)
  * Make your update wizard steps (I would also recommend the package **typo3_console** for this to do this from CLI)
* Migration
  * Dump your new database
  * Add an extension (e.g. key `migration_extend`) with a composer.json and require `in2code/migration` in it
  * Install this extension (e.g. in require_dev section)
  * Start with adding your own Migrators and Importers to your extension (Add a configuration file to your extension)
  * And then have fun with migrating, rolling back database, update your scripts, migrate again, and so on
* Finish
  * If you are finished and have a good result, you simply can remove both extensions
  * See also https://www.slideshare.net/einpraegsam/typo3-migration-in-komplexen-upgrade-und-relaunchprojekten-114716116
  
  
  
  
## Example CLI commands

```
# Example migration
./vendor/bin/typo3 migration:migrate --configuration EXT:migration_extend/Configuration/Migration.php

# Example export into json file
./vendor/bin/typo3 migration:export 123 > /home/user/export.json

# Example import from json file
./vendor/bin/typo3 migration:import /home/user/export.json 123
```

See documenation for a detailed description of all CLI commands



## Documenation

* [Migration and Import](Documentation/Migration.md)
* [Port (Import and Export)](Documentation/Port.md) 
* [Helpers](Documentation/Helper.md) 



## Breaking changes

* Update to 7.6.0: This is only a small breaking change because constructors in extended with a configuration array now. If you are using own propertyHelpers and you overwrote __construct(), you have also to pass the new variable now. 


## Changelog

| Version     | Date       | State   | Description                                                                                                                                                                                                   |
|-------------|------------|---------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| 11.2.0      | 2024-06-06 | Feature | Extend default configuration with newer news plugins, container and powermail_cond settings, prevent duplicate mm table entries, support sections in links now, prevent some missing array key errors         |
| 11.1.0      | 2024-06-06 | Task    | sys_file_metadata records should also be importend and exported, move "beforeEvent" really before import data is generated, prevent some missing array key errors, prevent array to string conversion message |
| 11.0.1      | 2024-01-31 | Bugfix  | Fix some smaller issues (e.g. missing array keys, etc...)                                                                                                                                                     |
| 11.0.0      | 2024-01-10 | Task    | Support TYPO3 12 and 11, some bugfixes                                                                                                                                                                        |
| 10.0.0      | 2023-03-17 | Task    | Some smaller PHP 8 related fixes, first code cleanup for TYPO3 12 (e.g. ObjectUtility::getObjectManager() was removed)                                                                                        |
| 9.0.0       | 2022-09-08 | Feature | Add property helpers for gridelements to container migration                                                                                                                                                  |
| 8.0.1       | 2022-05-10 | Bugfix  | Small fix in the import class for merging arrays with excluding tables                                                                                                                                        |
| 8.0.0       | 2022-04-17 | Feature | Support TYPO3 11 and drop older versions                                                                                                                                                                      |
| 7.8.0       | 2022-04-17 | Feature | Add an additional command for complex datahandler stuff on CLI                                                                                                                                                |
| 7.7.0       | 2022-01-21 | Feature | Add helper classes to FlexFormGeneratorPropertyHelper for even more power                                                                                                                                     |
| 7.6.1       | 2021-11-25 | Bugfix  | Replace property helper should also handle array values                                                                                                                                                       |
| 7.6.0 (!!!) | 2021-10-29 | Feature | Make configuration available in propertyHelper classes                                                                                                                                                        |
| 7.5.0       | 2021-10-11 | Feature | Import: Make the old identifiers available after importing                                                                                                                                                    |
| 7.4.0       | 2021-07-05 | Feature | Port: Support inline relations with a parent relation now                                                                                                                                                     |
| 7.3.0       | 2021-03-17 | Task    | Fix command description, add extension key to composer.json, add autodeployment                                                                                                                               |
| 7.2.0       | 2020-09-14 | Feature | Make FileHelper::indexFile a public function                                                                                                                                                                  |
| 7.1.0       | 2020-08-21 | Task    | Small bugfix for writeFileFromBase64Code and add a message when adding slugs                                                                                                                                  |
| 7.0.0       | 2020-05-04 | Task    | Update dependencies for TYPO3 9 and 10                                                                                                                                                                        |
| 6.7.0       | 2020-02-18 | Feature | Port: Add configuration of EXT:news and in2faq, keep identifiers feature                                                                                                                                      |
| 6.6.0       | 2020-02-13 | Feature | Port: Support relations like "tt_content_123,pages_234" now                                                                                                                                                   |
| 6.5.2       | 2020-02-12 | Bugfix  | Fix regression from 6.5.1                                                                                                                                                                                     |
| 6.5.1       | 2020-02-12 | Bugfix  | Migration: Don't use deleted=0 if there is no deleted field                                                                                                                                                   |
| 6.5.0       | 2020-02-11 | Feature | Allow usage of {propertiesOld} when using an importer now                                                                                                                                                     |
| 6.4.0       | 2020-02-03 | Feature | Allow manipulation of values while runtime for importers and migrators                                                                                                                                        |
| 6.3.0       | 2020-01-31 | Task    | Port: Don't stop if file is missing, Fixes in FileHelper class                                                                                                                                                |
| 6.2.0       | 2019-12-19 | Feature | Migration: SlugPropertyHelper creates unique slugs in site now                                                                                                                                                |
| 6.1.0       | 2019-12-05 | Feature | Port: Import can now handle huge files (> 6GB) in fileadmin (if not embedded)                                                                                                                                 |
| 6.0.0       | 2019-11-12 | Feature | Port with file URI instead of embedding, absolute config path is supported now                                                                                                                                |
| 5.4.0       | 2019-11-06 | Feature | Small features: Handle defect links, port config for powermail, etc...                                                                                                                                        |
| 5.3.0       | 2019-10-11 | Task    | Port: Support links to records, some bugfixes, some cleanup                                                                                                                                                   |
| 5.2.0       | 2019-10-02 | Feature | Export: Attach also files that simply linked in RTE + add localized records                                                                                                                                   |
| 5.1.0       | 2019-10-02 | Feature | Bugfix with missing tags, toggle file includes, added SlugPropertyHelper                                                                                                                                      |
| 5.0.0       | 2019-10-01 | Feature | Port and Migration is now based on an extend-extension with a configuration file                                                                                                                              |
| 4.0.1       | 2019-09-16 | Bugfix  | Restore deleted ArrayUtility class                                                                                                                                                                            |
| 4.0.0       | 2019-09-13 | Task    | Complete rewrite for TYPO3 9 with symfony tasks and doctrine, etc...                                                                                                                                          |
| 3.1.0       | 2019-03-19 | Feature | Update RTE images, Export now with files from links                                                                                                                                                           |
| 3.0.0       | 2019-02-08 | Task    | Add a working import and export command controller                                                                                                                                                            |
| 2.0.0       | 2018-09-07 | Task    | Use extkey migration, add ImportExportCommandController, some improvements                                                                                                                                    |
| 1.1.1       | 2018-09-07 | Task    | Add Changelog                                                                                                                                                                                                 |
| 1.1.0       | 2017-07-28 | Task    | Add DataHandler and Help CommandControllers                                                                                                                                                                   |
| 1.0.0       | 2017-07-26 | Task    | Initial release                                                                                                                                                                                               |




## Future Todos

* Migration: Log errors to file
* Migration: Throw error if given key is not defined
* New: Show and remove unused files as CommandController
