# TYPO3 Migration and Importer Boilerplate

## Description
This extension is a kickstarter extension (boilerplate) to import or migrate TYPO3 stuff. 
E.g: 
* Import tt_news to news
* Migration tt_content (TemplaVoila to Gridelements)

## Some notes
* Migration: This means migrate existing records in an existing table
* Import: This menas to import values with some logic from table A to table B

## Example CLI call
`./vendor/typo3cms mainmigration:migratenews --dryrun=1 --limittopage=1 --recursive=false`
