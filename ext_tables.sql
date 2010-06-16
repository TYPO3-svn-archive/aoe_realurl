#
# Table structure for table 'pages'
#
CREATE TABLE pages (
	tx_aoerealurlpath_overridepath tinytext NOT NULL,
	tx_aoerealurlpath_overridesegment tinytext NOT NULL,
	tx_aoerealurlpath_excludefrommiddle tinyint(3) DEFAULT '0' NOT NULL
);


#
# Table structure for table 'pages_language_overlay'
#
CREATE TABLE pages_language_overlay (
	tx_aoerealurlpath_overridepath tinytext NOT NULL,
	tx_aoerealurlpath_overridesegment tinytext NOT NULL,
	tx_aoerealurlpath_excludefrommiddle tinyint(3) DEFAULT '0' NOT NULL
);



#
# Table structure for table 'tx_aoerealurlpath_cache'
#
CREATE TABLE tx_aoerealurlpath_cache (
	tstamp int(11) DEFAULT '0' NOT NULL,	
	mpvar tinytext NOT NULL,	
	workspace int(11) DEFAULT '0' NOT NULL,
	rootpid int(11) DEFAULT '0' NOT NULL,
	languageid int(11) DEFAULT '0' NOT NULL,	
	pageid int(11) DEFAULT '0' NOT NULL,
	path text NOT NULL,	
	dirty tinyint(3) DEFAULT '0' NOT NULL
	
	PRIMARY KEY (pageid,workspace,rootpid,languageid),
	KEY `path_k` (path(100)),
	KEY `path_branch_k` (rootpid,path(100)),
	KEY `ws_lang_k` (workspace,languageid)
) ENGINE=InnoDB;

#
# Table structure for table 'tx_aoerealurlpath_cachehistory'
#
CREATE TABLE tx_aoerealurlpath_cachehistory (
	uid int(11) NOT NULL auto_increment,	
	tstamp int(11) DEFAULT '0' NOT NULL,	
	mpvar tinytext NOT NULL,	
	workspace int(11) DEFAULT '0' NOT NULL,
	rootpid int(11) DEFAULT '0' NOT NULL,
	languageid int(11) DEFAULT '0' NOT NULL,	
	pageid int(11) DEFAULT '0' NOT NULL,
	path text NOT NULL,	
	
	PRIMARY KEY (uid),
	KEY `path_k` (path(100)),
	KEY `path_branch_k` (rootpid,path(100)),
	KEY `ws_lang_k` (workspace,languageid)
) ENGINE=InnoDB;

