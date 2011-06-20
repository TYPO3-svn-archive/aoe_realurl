<?php
if (! defined ( 'TYPO3_MODE' ))
	die ( 'Access denied.' );

$tempColumns = array (
	"tx_aoerealurlpath_overridepath" => array (
		"exclude" => 1,
		"label" => "LLL:EXT:aoe_realurlpath/locallang_db.xml:pages.tx_aoerealurlpath_overridepath",
		"config" => array (
			"type" => "input",
			"size" => "255"
		)
	),
	"tx_aoerealurlpath_excludefrommiddle" => array (
		"exclude" => 1,
		"label" => "LLL:EXT:aoe_realurlpath/locallang_db.xml:pages.tx_aoerealurlpath_excludefrommiddle",
		"config" => array (
			"type" => "check"
		)
	),
	"tx_aoerealurlpath_overridesegment" => array (
		"exclude" => 1,
		"label" => "LLL:EXT:aoe_realurlpath/locallang_db.xml:pages.tx_aoerealurlpath_overridesegment",
		"config" => array (
			"type" => "input",
			"size" => "50"
		)
	),
);

t3lib_div::loadTCA("pages_language_overlay");
t3lib_extMgm::addTCAcolumns('pages_language_overlay', $tempColumns, 1);
t3lib_extMgm::addToAllTCAtypes('pages_language_overlay', '--div--;Url Settings,tx_aoerealurlpath_overridepath;;;;1-1-1, tx_aoerealurlpath_excludefrommiddle,tx_aoerealurlpath_overridesegment,tx_aoerealurlpath_info', '', 'after:tx_realurl_pathsegment');
t3lib_extMgm::addToAllTCAtypes('pages_language_overlay', '--div--;RealUrl', '', 'before:tx_realurl_pathsegment');

t3lib_div::loadTCA("pages");
t3lib_extMgm::addTCAcolumns("pages", $tempColumns, 1 );
t3lib_extMgm::addToAllTCAtypes("pages", "--div--;Url Settings,tx_aoerealurlpath_overridepath;;;;1-1-1, tx_aoerealurlpath_excludefrommiddle,tx_aoerealurlpath_overridesegment,tx_aoerealurlpath_info", '', 'after:tx_realurl_pathsegment');
t3lib_extMgm::addToAllTCAtypes('pages', '--div--;RealUrl', '', 'before:tx_realurl_pathsegment');

$GLOBALS['TCA']['pages']['columns']['tx_realurl_pathsegment'] = array(
	 'displayCond' => 'EXT:aoe_realurlpath:LOADED:false',
	 'config' => array('type'=>'input')
);

$confArr = unserialize ( $GLOBALS ['TYPO3_CONF_VARS'] ['EXT'] ['extConf'] ['aoe_realurlpath'] );
if ($confArr ['enableLiveInfo'] == 1) {

	$tempColumns = 	array(
		"tx_aoerealurlpath_info" => array(
			"label" => "LLL:EXT:aoe_realurlpath/locallang_db.xml:pages.tx_aoerealurlpath_info",
			"config" => array (
				"type" => "user",
				"userFunc" => "EXT:aoe_realurlpath/class.tx_aoerealurlpath_tceforms.php:tx_aoerealurlpath_tceforms->render_infoField"
			)
		)
	);
	t3lib_extMgm::addTCAcolumns('pages_language_overlay', $tempColumns, 1);
	t3lib_extMgm::addToAllTCAtypes('pages_language_overlay', 'tx_aoerealurlpath_info', '', 'after:tx_aoerealurlpath_overridesegment');
	t3lib_extMgm::addTCAcolumns("pages", $tempColumns, 1 );
	t3lib_extMgm::addToAllTCAtypes("pages", "tx_aoerealurlpath_info", '', 'after:tx_aoerealurlpath_overridesegment');
}

if (TYPO3_MODE == "BE") {
	t3lib_extMgm::insertModuleFunction ( "web_info", "tx_aoerealurlpath_modfunc1", t3lib_extMgm::extPath ( $_EXTKEY ) . "modfunc1/class.tx_aoerealurlpath_modfunc1.php", "LLL:EXT:aoe_realurlpath/locallang_db.xml:moduleFunction.tx_aoerealurlpath_modfunc1" );
}
?>
