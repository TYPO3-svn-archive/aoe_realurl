<?php

########################################################################
# Extension Manager/Repository config file for ext "aoe_realurlpath".
#
# Auto generated 21-12-2009 05:32
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Alternative Real URL Path',
	'description' => 'Powerful realurl path generation. Allows flexible paths. Support for overridepath, workspaces and exclude from middle feature',
	'category' => 'fe',
	'shy' => 0,
	'version' => '0.5.dev',
	'dependencies' => '',
	'conflicts' => 'cooluri',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'Daniel Poetzinger, Tolleiv Nietsch',
	'author_email' => '',
	'author_company' => 'AOE media GmbH',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'realurl' => '0.0.0-1.9.4'
		),
		'conflicts' => array(
				// these versions might work but will propably show errors
			'realurl' => '1.7.0-1.9.3'
		),
		'suggests' => array(
			'realurl' => '1.5.3'
		),
	),
	'_md5_values_when_last_written' => 'a:33:{s:9:"ChangeLog";s:4:"0d70";s:10:"README.txt";s:4:"939d";s:37:"class.tx_aoerealurlpath_cachemgmt.php";s:4:"d65d";s:36:"class.tx_aoerealurlpath_pagepath.php";s:4:"9a60";s:41:"class.tx_aoerealurlpath_pathgenerator.php";s:4:"5201";s:36:"class.tx_aoerealurlpath_tceforms.php";s:4:"8c09";s:36:"class.tx_aoerealurlpath_typo3env.php";s:4:"7846";s:16:"ext_autoload.php";s:4:"4ebc";s:21:"ext_conf_template.txt";s:4:"4c3e";s:12:"ext_icon.gif";s:4:"f19a";s:17:"ext_localconf.php";s:4:"5530";s:14:"ext_tables.php";s:4:"ac66";s:14:"ext_tables.sql";s:4:"3349";s:32:"icon_tx_aoerealurlpath_cache.gif";s:4:"475a";s:16:"locallang_db.xml";s:4:"4f5b";s:7:"md5.php";s:4:"8429";s:14:"doc/manual.sxw";s:4:"101a";s:19:"doc/wizard_form.dat";s:4:"0ec6";s:20:"doc/wizard_form.html";s:4:"0677";s:47:"hooks/class.tx_aoerealurlpath_hooks_crawler.php";s:4:"553a";s:54:"hooks/class.tx_aoerealurlpath_hooks_processdatamap.php";s:4:"44f4";s:45:"modfunc1/class.tx_aoerealurlpath_modfunc1.php";s:4:"467b";s:22:"modfunc1/locallang.xml";s:4:"bc16";s:35:"patch/1.5.3/class.ux_tx_realurl.php";s:4:"f729";s:35:"patch/1.9.4/class.ux_tx_realurl.php";s:4:"1114";s:46:"tests/tx_aoerealurlpath_cachemgmt_testcase.php";s:4:"e7f7";s:45:"tests/tx_aoerealurlpath_pagepath_testcase.php";s:4:"c256";s:50:"tests/tx_aoerealurlpath_pathgenerator_testcase.php";s:4:"5224";s:33:"tests/tx_environment_testcase.php";s:4:"40a1";s:33:"tests/fixtures/overlay-livews.xml";s:4:"8f82";s:29:"tests/fixtures/overlay-ws.xml";s:4:"565a";s:30:"tests/fixtures/page-livews.xml";s:4:"cd01";s:26:"tests/fixtures/page-ws.xml";s:4:"1269";}',
	'suggests' => array(
	),
);

?>
