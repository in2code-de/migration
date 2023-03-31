## Additional useful symfony commands and helpers

### DataHandlerCommand

Do TYPO3 pageactions (normally known from backend) via console. Move, delete, copy complete pages and trees without runtimelimit from CLI

Example CLI call

```
# Copy tree with beginning pid 123 into page with pid 234
./vendor/bin/typo3 migration:datahandler 123 copy 234

# Move tree with beginning pid 123 into page with pid 234
./vendor/bin/typo3 migration:datahandler 123 move 234

# Delete complete tree with beginning pid 123
./vendor/bin/typo3 migration:datahandler 123 delete 0 99

```

### ComplexDataHandlerCommand

For more and complex operations, we have the complex datahandlercommand.
Please consult the dokumentation before doing anything:

[TCE (TYPO3 Core engine) & DataHandler](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Typo3CoreEngine/Index.html)

[Database: DataHandler basics (Formerly Known as TCEmain)](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Typo3CoreEngine/Database/Index.html#database-datahandler-basics-formerly-known-as-tcemain)

Example CLI call

```
# synchronize the media of a page to its language-child
./vendor/bin/typo3 migration:complexdatahandler page 347 inlineLocalizeSynchronize '{"field":"media", "action":"synchronize", "language: 1}'

```

### HelpCommand

Simple show a commaseparated list of subpages to a page (helpful for further database commands)

Example CLI call

```
# Show a commaseparated list of a page with pid 123 and its subpages
./vendor/bin/typo3 migration:help 123
```
