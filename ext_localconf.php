<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

include_once (t3lib_extMgm::extPath ('aoe_realurlpath') . 'class.tx_aoerealurlpath_pagepath.php');
$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['aoe_realurlpath']);
if ($confArr['applyPatch'] == 1) {
	$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realurl/class.tx_realurl.php'] = t3lib_extMgm::extPath('aoe_realurlpath') . 'patch/class.ux_tx_realurl.php';
}
if ($confArr['addpageOverlayFields'] == 1) {
	$GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields'] .= ',tx_aoerealurlpath_overridesegment,tx_aoerealurlpath_overridepath,tx_aoerealurlpath_excludefrommiddle';
}
	//force more fields in the rootline:
$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ',tx_aoerealurlpath_overridesegment,tx_aoerealurlpath_overridepath,tx_aoerealurlpath_excludefrommiddle';

	//register hook to mark cache as dirty
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['aoe_realurlpath'] = 'EXT:aoe_realurlpath/hooks/class.tx_aoerealurlpath_hooks_processdatamap.php:&tx_aoerealurlpath_hooks_processdatamap';

	// register hook to add the excludemiddle field into the list of fields for new localization records
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamap_preProcessFieldArray']['aoe_realurlpath'] = 'EXT:aoe_realurlpath/hooks/class.tx_aoerealurlpath_hooks_processdatamap.php:&tx_aoerealurlpath_hooks_processdatamap';

	//hook to force regeneration if crawler is active:
if (TYPO3_MODE == 'FE') {
	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['insertPageIncache']['tx_aoerealurlpath'] = 'EXT:aoe_realurlpath/hooks/class.tx_aoerealurlpath_hooks_crawler.php:tx_aoerealurlpath_hooks_crawler';
	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['headerNoCache']['tx_aoerealurlpath'] = 'EXT:aoe_realurlpath/hooks/class.tx_aoerealurlpath_hooks_crawler.php:tx_aoerealurlpath_hooks_crawler->headerNoCache';
}
        // Register processing instruction on tx_crawler
$TYPO3_CONF_VARS['EXTCONF']['crawler']['procInstructions']['tx_aoerealurlpath_rebuild'] = 'Force page link regeneration';

?>