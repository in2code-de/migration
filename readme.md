# TYPO3 Migration Framework

## Description

This extension (**EXT:migration**) is a helper extension for your TYPO3 updates and migrations based
on CLI commands (to prevent timeouts, use a better performance, etc...).

What can this extension do for you:
* [Migration](Documentation/Migration.md) of table values
* [Import](Documentation/Migration.md) tables values from other tables
* [Export](Documentation/Port.md) of whole page branches with all records and files as json
* [Import](Documentation/Port.md) of whole page branches with all records and files from json into an existing table (and gives new identifiers and relations)
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
./vendor/bin/typo3cms migration:migrate --configuration EXT:migration_extend/Configuration/Migration.php

# Example export into json file
./vendor/bin/typo3cms migration:export 123 > /home/user/export.json

# Example import from json file
./vendor/bin/typo3cms migration:import /home/user/export.json 123
```

See documenation for a detailed description of all CLI commands



## Documenation

* [Migration and Import](Documentation/Migration.md)
* [Port (Import and Export)](Documentation/Port.md) 
* [Helpers](Documentation/Helper.md) 




## Changelog

| Version    | Date       | State      | Description                                                                      |
| ---------- | ---------- | ---------- | -------------------------------------------------------------------------------- |
| 6.0.0      | 2019-11-12 | Feature    | Port with file URI instead of embedding, absolute config path is supported now   |
| 5.4.0      | 2019-11-06 | Feature    | Small features: Handle defect links, port config for powermail, etc...           |
| 5.3.0      | 2019-10-11 | Task       | Port: Support links to records, some bugfixes, some cleanup                      |
| 5.2.0      | 2019-10-02 | Feature    | Export: Attach also files that simply linked in RTE + add localized records      |
| 5.1.0      | 2019-10-02 | Feature    | Bugfix with missing tags, toggle file includes, added SlugPropertyHelper         |
| 5.0.0      | 2019-10-01 | Feature    | Port and Migration is now based on an extend-extension with a configuration file |
| 4.0.1      | 2019-09-16 | Bugfix     | Restore deleted ArrayUtility class                                               |
| 4.0.0      | 2019-09-13 | Task       | Complete rewrite for TYPO3 9 with symfony tasks and doctrine, etc...             |
| 3.1.0      | 2019-03-19 | Feature    | Update RTE images, Export now with files from links                              |
| 3.0.0      | 2019-02-08 | Task       | Add a working import and export command controller                               |
| 2.0.0      | 2018-09-07 | Task       | Use extkey migration, add ImportExportCommandController, some improvements       |
| 1.1.1      | 2018-09-07 | Task       | Add Changelog                                                                    |
| 1.1.0      | 2017-07-28 | Task       | Add DataHandler and Help CommandControllers                                      |
| 1.0.0      | 2017-07-26 | Task       | Initial release                                                                  |




## Future Todos

* Show and remove unused files as CommandController
* Add some more relations to import and export (e.g. for tx_news) in the default configuration file
* Add a fully functional generic importer - e.g. tt_news to tx_news
