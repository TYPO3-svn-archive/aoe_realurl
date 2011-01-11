<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004 Kasper Skaarhoj (kasper@typo3.com)
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * XClass for creating and parsing Speaking Urls
 *
 *
 * @author	Daniel P�tzinger
 * @package TYPO3
 * @subpackage tx_realurl
 */
class ux_tx_realurl extends tx_realurl {
	private $pre_GET_VARS; //function decodeSpURL_doDecode stores the calculated pre_GET_VARS, so clients of this class can access this information


	function getRetrievedPreGetVar($key) {
		return $this->pre_GET_VARS [$key];
	}
	
	function lookUp_uniqAliasToId($cfg, $aliasValue, $onlyNonExpired = FALSE) {
		
		static $cache = array();
		$paramhash = md5(serialize($cfg).serialize($aliasValue).serialize($onlyNonExpired));
		
		if (isset($cache[$paramhash])) {
			return $cache[$paramhash];
		}
		
		$returnValue = parent::lookUp_uniqAliasToId($cfg, $aliasValue, $onlyNonExpired);

		if ($returnValue) {
			$cache[$paramhash] = $returnValue;
		}
		return $returnValue;
	}
	
	function lookUp_idToUniqAlias($cfg, $idValue, $lang, $aliasValue = '') {
		static $cache = array();
		$paramhash = md5(serialize($cfg).serialize($idValue).serialize($lang).serialize($aliasValue));
		
		if (isset($cache[$paramhash])) {
			return $cache[$paramhash];
		}
		
		$returnValue = parent::lookUp_idToUniqAlias($cfg, $idValue, $lang, $aliasValue);
			//@todo check if we should cache anthing if $returnValue is "false"
		$cache[$paramhash] = $returnValue;
		return $returnValue;

	}
	
	function _checkForExternalPageAndGetTarget($id) {
		
		static $cache = array();
		
		if (isset($cache[$id])) {
			return $cache[$id];
		}
		
		$returnValue = NULL;
		
		$where = "uid=\"" . intval ( $id ) . "\"";
		$query = $GLOBALS ['TYPO3_DB']->exec_SELECTquery ( "uid,pid,url,doktype,urltype", "pages", $where );
		if ($query) {
			$result = $GLOBALS ['TYPO3_DB']->sql_fetch_assoc ( $query );
			$GLOBALS ['TSFE']->sys_page->versionOL ( "pages", $result );
		}
		$result = $GLOBALS ['TSFE']->sys_page->getPageOverlay ( $result );
		if (count ( $result )) {
			if ($result ['doktype'] == 3) {
				$url = $result ['url'];
				switch ($result ['urltype']) {
					case '1' :
						$returnValue = 'http://' . $url;
						break;
					case '4' :
						$returnValue = 'https://' . $url;
						break;
					case '2' :
						$returnValue = 'ftp://' . $url;
						break;
					case '3' :
						$returnValue = 'mailto:' . $url;
						break;
					default :
						$returnValue = $url;
						break;

				}
			} else {
				$returnValue = false;
			}
		} else {
			$returnValue = false;
		}
		$cache[$id] = $returnValue;
		return $returnValue;
	}

	/**
	 * Translates a URL with query string (GET parameters) into Speaking URL.
	 * Called from t3lib_tstemplate::linkData
	 *
	 * @param	array		Array of parameters from t3lib_tstemplate::linkData - the function creating all links inside TYPO3
	 * @param	object		Copy of parent caller. Not used.
	 * @return	void
	 */
	function encodeSpURL(&$params, &$ref) {
		if ($this->enableDevLog) {
			t3lib_div::devLog('Entering encodeSpURL for ' . $params['LD']['totalURL'], 'realurl');
		}

		if (!$params['TCEmainHook']) {
			// Return directly, if simulateStaticDocuments is set:
			if ($GLOBALS['TSFE']->config['config']['simulateStaticDocuments']) {
				$GLOBALS['TT']->setTSlogMessage('SimulateStaticDocuments is enabled. RealURL disables itself.', 2);
				return;
			}

			// Return directly, if realurl is not enabled:
			if (!$GLOBALS['TSFE']->config['config']['tx_realurl_enable']) {
				$GLOBALS['TT']->setTSlogMessage('RealURL is not enabled in TS setup. Finished.');
				return;
			}
		}

		// Checking prefix:
		$prefix = $GLOBALS['TSFE']->absRefPrefix . $this->prefixEnablingSpURL;
		if (substr($params['LD']['totalURL'], 0, strlen($prefix)) != $prefix) {
			return;
		}

		if ($this->enableDevLog) {
			t3lib_div::devLog('Starting URL encode', 'realurl', -1);
		}

		// Initializing config / request URL:
		$this->setConfig();
		$internalExtras = array();

			//danielp: add workspace to internal vars for caching purposes
		if ($GLOBALS ['BE_USER']->workspace) {
			$internalExtras ['workspace'] = $GLOBALS ['BE_USER']->workspace;
		}
		// Init "Admin Jump"; If frontend edit was enabled by the current URL of the page, set it again in the generated URL (and disable caching!)
		if (!$params['TCEmainHook']) {
			if ($GLOBALS['TSFE']->applicationData['tx_realurl']['adminJumpActive']) {
				$GLOBALS['TSFE']->set_no_cache();
				$this->adminJumpSet = TRUE;
				$internalExtras['adminJump'] = 1;
			}

			// If there is a frontend user logged in, set fe_user_prefix
			if (is_array($GLOBALS['TSFE']->fe_user->user)) {
				$this->fe_user_prefix_set = TRUE;
				$internalExtras['feLogin'] = 1;
			}
		}

		// Parse current URL into main parts:
		$uParts = parse_url($params['LD']['totalURL']);

		// Look in memory cache first:
		$newUrl = $this->encodeSpURL_encodeCache($uParts['query'], $internalExtras);
		if (!$newUrl) {

			// Encode URL:
			$newUrl = $this->encodeSpURL_doEncode($uParts['query'], $this->extConf['init']['enableCHashCache'], $params['LD']['totalURL']);

			// Set new URL in cache:
			$this->encodeSpURL_encodeCache($uParts['query'], $internalExtras, $newUrl);
		}

		// Adding any anchor there might be:
		if ($uParts['fragment']) {
			$newUrl .= '#' . $uParts['fragment'];
		}
		// Setting the encoded URL in the LD key of the params array - that value is passed by reference and thus returned to the linkData function!
		$params['LD']['totalURL'] = $newUrl;
	}

	/**
	 * Transforms a query string into a speaking URL according to the configuration in ->extConf
	 *
	 * @param	string		Input query string
	 * @param	boolean		If set, the cHashCache table is used for "&cHash"
	 * @param	string		Original URL
	 * @return	string		Output Speaking URL (with as many GET parameters encoded into the URL as possible).
	 * @see encodeSpURL()
	 */
	function encodeSpURL_doEncode($inputQuery, $cHashCache = FALSE, $origUrl = '') {

		// Extract all GET parameters into an ARRAY:
		$paramKeyValues = array();
		$GETparams = explode('&', $inputQuery);
		foreach ($GETparams as $paramAndValue) {
			list($p, $v) = explode('=', $paramAndValue, 2);
			if (strlen ( $p )) {
				$paramKeyValues[rawurldecode($p)] = rawurldecode ($v);
			}
		}
		$externamURL = $this->_checkForExternalPageAndGetTarget ( $paramKeyValues ['id'] );
		if ($externamURL !== false) {
			return $externamURL;
		}
		// Return new, Speaking URL encoded URL:
		return parent::encodeSpURL_doEncode ( $inputQuery, $cHashCache, $origUrl );
	}

	/**
	 * Decodes a speaking URL path into an array of GET parameters and a page id.
	 *
	 * @param	string		Speaking URL path (after the "root" path of the website!) but without query parameters
	 * @param	boolean		If cHash caching is enabled or not.
	 * @return	array		Array with id and GET parameters.
	 * @see decodeSpURL()
	 */
	function decodeSpURL_doDecode($speakingURIpath, $cHashCache = FALSE) {

		// Cached info:
		$cachedInfo = array();

		// Split URL + resolve parts of path:
		$pathParts = explode('/', $speakingURIpath);

			//clear former replaced empty values
		if ($this->extConf ['init'] ['postReplaceEmptyValues'] == 1) {
			$emptyPathSegmentReplaceValue = ($this->extConf ['init'] ['emptyValuesReplacer']) ? $this->extConf ['init'] ['emptyValuesReplacer'] : $this->emptyReplacerDefaultValue;
			foreach ( $pathParts as $k => $v ) {
				if ($v == $emptyPathSegmentReplaceValue) {
					$pathParts [$k] = '';
				}
			}
		}

		$this->filePart = array_pop($pathParts);

		// Checking default HTML name:
		if (strlen($this->filePart) && ($this->extConf['fileName']['defaultToHTMLsuffixOnPrev'] || $this->extConf['fileName']['acceptHTMLsuffix']) && !isset($this->extConf['fileName']['index'][$this->filePart])) {
			$suffix = preg_quote($this->isString($this->extConf['fileName']['defaultToHTMLsuffixOnPrev'], 'defaultToHTMLsuffixOnPrev') ? $this->extConf['fileName']['defaultToHTMLsuffixOnPrev'] : '.html', '/');
			if ($this->isString($this->extConf['fileName']['acceptHTMLsuffix'], 'acceptHTMLsuffix')) {
				$suffix = '(' . $suffix . '|' . preg_quote($this->extConf['fileName']['acceptHTMLsuffix'], '/') . ')';
			}
			$pathParts[] = preg_replace('/' . $suffix . '$/', '', $this->filePart);
			$this->filePart = '';
		}

		// Setting original dir-parts:
		$this->dirParts = $pathParts;

		// Setting "preVars":
		$pre_GET_VARS = $this->decodeSpURL_settingPreVars($pathParts, $this->extConf['preVars']);

			// danielp: make preVars accessible
		$this->pre_GET_VARS = $pre_GET_VARS;

		// Setting page id:
		list($cachedInfo['id'], $id_GET_VARS, $cachedInfo['rootpage_id']) = $this->decodeSpURL_idFromPath($pathParts);

		// Fixed Post-vars:
		$fixedPostVarSetCfg = $this->getPostVarSetConfig($cachedInfo['id'], 'fixedPostVars');
		$fixedPost_GET_VARS = $this->decodeSpURL_settingPreVars($pathParts, $fixedPostVarSetCfg);

		// Setting "postVarSets":
		$postVarSetCfg = $this->getPostVarSetConfig($cachedInfo['id']);
		$post_GET_VARS = $this->decodeSpURL_settingPostVarSets($pathParts, $postVarSetCfg);

		// Looking for remaining parts:
		if (count($pathParts)) {
			$this->decodeSpURL_throw404('"' . $speakingURIpath . '" could not be found, closest page matching is ' . substr(implode('/', $this->dirParts), 0, -strlen(implode('/', $pathParts))) . '');
		}

		// Setting filename:
		$file_GET_VARS = $this->decodeSpURL_fileName($this->filePart);

		// Merge Get vars together:
		$cachedInfo['GET_VARS'] = array();
		if (is_array($pre_GET_VARS))
			$cachedInfo['GET_VARS'] = t3lib_div::array_merge_recursive_overrule($cachedInfo['GET_VARS'], $pre_GET_VARS);
		if (is_array($id_GET_VARS))
			$cachedInfo['GET_VARS'] = t3lib_div::array_merge_recursive_overrule($cachedInfo['GET_VARS'], $id_GET_VARS);
		if (is_array($fixedPost_GET_VARS))
			$cachedInfo['GET_VARS'] = t3lib_div::array_merge_recursive_overrule($cachedInfo['GET_VARS'], $fixedPost_GET_VARS);
		if (is_array($post_GET_VARS))
			$cachedInfo['GET_VARS'] = t3lib_div::array_merge_recursive_overrule($cachedInfo['GET_VARS'], $post_GET_VARS);
		if (is_array($file_GET_VARS))
			$cachedInfo['GET_VARS'] = t3lib_div::array_merge_recursive_overrule($cachedInfo['GET_VARS'], $file_GET_VARS);

		// cHash handling:
		if ($cHashCache) {
			$cHash_value = $this->decodeSpURL_cHashCache($speakingURIpath);
			if ($cHash_value) {
				$cachedInfo['GET_VARS']['cHash'] = $cHash_value;
			}
		}

		// Return information found:
		return $cachedInfo;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realurl/class.ux_tx_realurl.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realurl/class.ux_tx_realurl.php']);
}

?>