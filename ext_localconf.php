<?php
if (! defined('TYPO3_MODE'))
    die('Access denied.');
include_once (t3lib_extMgm::extPath('aoe_realurlpath') . 'class.tx_aoerealurlpath_pagepath.php');
$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['aoe_realurlpath']);
if ($confArr['applyPatch'] == 1) {
    $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realurl/class.tx_realurl.php'] = t3lib_extMgm::extPath('aoe_realurlpath') . 'patch/class.ux_tx_realurl.php';
}
if ($confArr['addpageOverlayFields'] == 1) {
    $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ',tx_aoerealurlpath_overridesegment,tx_aoerealurlpath_overridepath,tx_aoerealurlpath_excludefrommiddle';
}
?>