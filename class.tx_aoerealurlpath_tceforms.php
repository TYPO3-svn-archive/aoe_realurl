<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010 AOE media
 * All rights reserved
 *
 * This script is part of the Typo3 project. The Typo3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 *
 * @author	Tolleiv Nietsch
 */

require_once(t3lib_extMgm::extPath('aoe_realurlpath') . 'class.tx_aoerealurlpath_typo3env.php');

class tx_aoerealurlpath_tceforms {

	/**
	 *
	 * @param array $PA
	 * @param t3lib_TCEforms $fobj
	 */
	public function render_infoField($PA, t3lib_TCEforms $fobj) {

		if(substr($PA['table'],0,5) != 'pages') return;


			//TODO workspace handling
		$pid = $PA['table'] == 'pages' ? $PA['row']['uid'] : $PA['row']['pid'];
		$lang = $PA['table'] == 'pages' ? 0 : $PA['row'][$GLOBALS['TCA'][$PA['table']]['ctrl']['languageField']];

		$rootline = t3lib_beFunc::BEgetRootLine($pid);
		$domain = t3lib_beFunc::firstDomainRecord($rootline, $lang);

		$t3env = t3lib_div::makeInstance('tx_aoerealurlpath_typo3env');
		if(!$t3env->initTSFE($pid,$GLOBALS['BE_USER']->workspace,$GLOBALS['BE_USER']->user['uid'])) {
			return 'Can\'t render image since TYPO3 Environment is not ready.<br/>Error was:'.$t3env->get_lastError();
		}

		$t3env->pushEnv();
		$t3env->setEnv(PATH_site);

			// pt 1 - building the path with typolink and see what's happening ...
		$typolinkConf = array(
			'parameter' => $pid,
			'additionalParams' => '&L='.$lang,
			'useCacheHash' => false,
		);

		$urlp1 = $GLOBALS['TSFE']->cObj->typoLink_URL($typolinkConf);


			// pt 2 - building it again with our internal API and check if we find conflicts ...
		$parts = array();
		$params = array();
		$params['mode'] = 'encode';
		$params['paramKeyValues']['id'] = $pid;
		$params['pathParts'] = &$parts; // API expects a reference to an array here ...

		$dummy = (object) array('orig_paramKeyValues'=>array('L'=>$lang)); // another stupid assumption or API makes - usually the realurl class is what we're looking for

		if(isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'][$domain]['pagePath'])) {
			$params['conf'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'][$domain]['pagePath'];
		} else {
			$params['conf'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT']['pagePath'];
		}
		$params['conf']['languageGetVar'] = 'L';

			/* @var $pagepath tx_aoerealurlpath_pagepath */
		$pagepath = t3lib_div::makeInstance('tx_aoerealurlpath_pagepath');
		$pagepath->main($params, $dummy);

		$urlp2 = implode('/', $params ['pathParts']) . '.html'; /*TODO realurl has a more complicated way to apply the .html  postfix ... */
		$t3env->popEnv();

		if(strcmp($urlp1, $urlp2)!== 0 || stristr($urlp2, '_'.$pid)) {
			$icon = t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/icon_warning2.gif');
			$iconAlt = 'Error';
			$msg = $GLOBALS['LANG']->sl('LLL:EXT:aoe_realurlpath/locallang_db.xml:pages.tx_aoerealurlpath_info.err');
		} else {
			$icon = t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/icon_ok.gif');
			$iconAlt = 'OK';
			$msg = $GLOBALS['LANG']->sl('LLL:EXT:aoe_realurlpath/locallang_db.xml:pages.tx_aoerealurlpath_info.ok');
		}
		return $urlp1 . ' <img '.$icon. ' alt="' . $iconAlt . '" title="' . $msg . '" />';
	}
}

?>