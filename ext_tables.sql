CREATE TABLE tx_powermail_domain_model_form (
	_migrated tinyint(4) unsigned DEFAULT '0' NOT NULL,
	_migrated_uid int(11) unsigned DEFAULT '0' NOT NULL
);
CREATE TABLE tx_powermail_domain_model_page (
	_migrated tinyint(4) unsigned DEFAULT '0' NOT NULL
);
CREATE TABLE tx_powermail_domain_model_field (
	_migrated tinyint(4) unsigned DEFAULT '0' NOT NULL
);
CREATE TABLE tx_news_domain_model_news (
	_migrated tinyint(4) unsigned DEFAULT '0' NOT NULL,
	_migrated_uid int(11) unsigned DEFAULT '0' NOT NULL,
	_migrated_table varchar(255) DEFAULT '' NOT NULL
);
