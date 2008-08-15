<?php
if (! defined('TYPO3_MODE'))
    die('Access denied.');
$tempColumns = Array("tx_aoerealurlpath_overridepath" => Array("exclude" => 1 , "label" => "LLL:EXT:aoe_realurlpath/locallang_db.xml:pages.tx_aoerealurlpath_overridepath" , "config" => Array("type" => "input" , "size" => "255")) , "tx_aoerealurlpath_excludefrommiddle" => Array("exclude" => 1 , "label" => "LLL:EXT:aoe_realurlpath/locallang_db.xml:pages.tx_aoerealurlpath_excludefrommiddle" , "config" => Array("type" => "check")) , "tx_aoerealurlpath_overridesegment" => Array("exclude" => 1 , "label" => "LLL:EXT:aoe_realurlpath/locallang_db.xml:pages.tx_aoerealurlpath_overridesegment" , "config" => Array("type" => "input" , "size" => "50")));
t3lib_div::loadTCA("pages_language_overlay");
t3lib_extMgm::addTCAcolumns('pages_language_overlay', $tempColumns, 1);
t3lib_extMgm::addToAllTCAtypes('pages_language_overlay', 'tx_aoerealurlpath_overridepath;;;;1-1-1, tx_aoerealurlpath_excludefrommiddle,tx_aoerealurlpath_overridesegment', '');
t3lib_div::loadTCA("pages");
t3lib_extMgm::addTCAcolumns("pages", $tempColumns, 1);
t3lib_extMgm::addToAllTCAtypes("pages", "tx_aoerealurlpath_overridepath;;;;1-1-1, tx_aoerealurlpath_excludefrommiddle,tx_aoerealurlpath_overridesegment");

if (TYPO3_MODE == "BE") {
    t3lib_extMgm::insertModuleFunction("web_info", "tx_aoerealurlpath_modfunc1", t3lib_extMgm::extPath($_EXTKEY) . "modfunc1/class.tx_aoerealurlpath_modfunc1.php", "LLL:EXT:aoe_realurlpath/locallang_db.xml:moduleFunction.tx_aoerealurlpath_modfunc1");
}
?>