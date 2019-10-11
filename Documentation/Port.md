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

**Note**: In your configuration file it is defined which relations, which mm-tables and some more should be used. See EXT:migration/Configuration/Port.php for an example.

### ImportCommand

Import a json file with exported data (e.g. a page branch) into an existing TYPO3 installation

Example CLI call

```
# Import page branch with subpages and files into page with uid 123
./vendor/bin/typo3cms migration:import /home/user/export.json 123

# Import page branch with subpages and files into page with uid 123 and use own configuration file
./vendor/bin/typo3cms migration:import /home/user/export.json 123 EXT:migration_extend/Configuration/Port.php
```

**Note**: In your configuration file it is defined which relations, which mm-tables and some more should be used. See EXT:migration/Configuration/Port.php for an example.
