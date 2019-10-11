## Additional useful symfony commands and helpers

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

### HelpCommand

Simple show a commaseparated list of subpages to a page (helpful for further database commands)

Example CLI call

```
# Show a commaseparated list of a page with pid 123 and its subpages
./vendor/bin/typo3cms migration:help 123
```
