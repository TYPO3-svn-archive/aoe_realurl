<?php

########################################################################
# Extension Manager/Repository config file for ext: "aoe_realurlpath"
#
# Auto generated 14-07-2009 17:37
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Alternative Real URL Path',
	'description' => 'Powerful realurl path generation. Allows flexible paths. Support for overridepath, workspaces and exclude from middle feature',
	'category' => 'fe',
	'shy' => 0,
	'version' => '0.2.4',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Daniel Poetzinger, Tolleiv Nietsch',
	'author_email' => '',
	'author_company' => 'AOE media GmbH',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.3.0-'
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:30:{s:9:"ChangeLog";s:4:"0b7f";s:10:"README.txt";s:4:"939d";s:9:"Thumbs.db";s:4:"827e";s:37:"class.tx_aoerealurlpath_cachemgmt.php";s:4:"2e07";s:39:"class.tx_aoerealurlpath_crawlerhook.php";s:4:"6040";s:36:"class.tx_aoerealurlpath_pagepath.php";s:4:"561e";s:41:"class.tx_aoerealurlpath_pathgenerator.php";s:4:"1340";s:45:"class.tx_aoerealurlpath_processcmdmaphook.php";s:4:"8bca";s:21:"ext_conf_template.txt";s:4:"eca2";s:12:"ext_icon.gif";s:4:"f19a";s:17:"ext_localconf.php";s:4:"8eb3";s:14:"ext_tables.php";s:4:"f464";s:14:"ext_tables.sql";s:4:"c0b0";s:32:"icon_tx_aoerealurlpath_cache.gif";s:4:"475a";s:16:"locallang_db.xml";s:4:"9d11";s:7:"md5.php";s:4:"8429";s:29:"patch/class.ux_tx_realurl.php";s:4:"4055";s:13:"doc/Thumbs.db";s:4:"f94c";s:14:"doc/manual.sxw";s:4:"101a";s:19:"doc/wizard_form.dat";s:4:"0ec6";s:20:"doc/wizard_form.html";s:4:"0677";s:45:"modfunc1/class.tx_aoerealurlpath_modfunc1.php";s:4:"fd19";s:22:"modfunc1/locallang.xml";s:4:"bc16";s:46:"tests/tx_aoerealurlpath_cachemgmt_testcase.php";s:4:"ce9b";s:45:"tests/tx_aoerealurlpath_pagepath_testcase.php";s:4:"4d47";s:50:"tests/tx_aoerealurlpath_pathgenerator_testcase.php";s:4:"8b13";s:33:"tests/fixtures/overlay-livews.xml";s:4:"d337";s:29:"tests/fixtures/overlay-ws.xml";s:4:"4456";s:30:"tests/fixtures/page-livews.xml";s:4:"15b2";s:26:"tests/fixtures/page-ws.xml";s:4:"ac91";}',
	'suggests' => array(
	),
);

?>