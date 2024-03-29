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

	function _checkForExternalPageAndGetTarget($id) {
		
		if (!is_object($GLOBALS ['TSFE']->sys_page)) {
			return false;
		}
		
		static $cache = array();
		
		if (isset($cache[$id])) {
			return $cache[$id];
		}
		
		$returnValue = NULL;
		
		$where = "uid=\"" . intval ( $id ) . "\"";
		$query = $GLOBALS ['TYPO3_DB']->exec_SELECTquery ( "uid,pid,url,doktype,urltype", "pages", $where );
		if (is_object($GLOBALS ['TSFE']->sys_page))
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
		$this->devLog('Entering encodeSpURL for ' . $params['LD']['totalURL']);

		if ($this->isInWorkspace()) {
			$this->devLog('Workspace detected. Not doing anything!');
			return;
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

		$this->devLog('Starting URL encode');

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

		// Look in memory cache first
		$urlData = $this->hostConfigured . ' | ' . $uParts['query'];
		$newUrl = $this->encodeSpURL_encodeCache($urlData, $internalExtras);
		if (!$newUrl) {
			// Encode URL
			$newUrl = $this->encodeSpURL_doEncode($uParts['query'], $this->extConf['init']['enableCHashCache'], $params['LD']['totalURL']);

			// Set new URL in cache
			$this->encodeSpURL_encodeCache($urlData, $internalExtras, $newUrl);
		}
		unset($urlData);

		// Adding any anchor there might be:
		if ($uParts['fragment']) {
			$newUrl .= '#' . $uParts['fragment'];
		}

		// Reapply config.absRefPrefix if necessary
		if ((!isset($this->extConf['init']['reapplyAbsRefPrefix']) || $this->extConf['init']['reapplyAbsRefPrefix']) && $GLOBALS['TSFE']->absRefPrefix) {
			// Prevent // in case of absRefPrefix ending with / and emptyUrlReturnValue=/
			if (substr($GLOBALS['TSFE']->absRefPrefix, -1, 1) == '/' && substr($newUrl, 0, 1) == '/') {
				$newUrl = substr($newUrl, 1);
			}
			$newUrl = $GLOBALS['TSFE']->absRefPrefix . $newUrl;
		}

		// Set prepending of URL (e.g. hostname) which will be processed by typoLink_PostProc hook in tslib_content:
		if (isset($adjustedConfiguration['urlPrepend']) && !isset($this->urlPrepend[$newUrl])) {
			$urlPrepend = $adjustedConfiguration['urlPrepend'];
			if (substr($urlPrepend, -1) == '/') {
				$urlPrepend = substr($urlPrepend, 0, -1);
			}
			$this->urlPrepend[$newUrl] = $urlPrepend;
		}

		// Call hooks
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['encodeSpURL_postProc'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['encodeSpURL_postProc'] as $userFunc) {
				$hookParams = array(
					'pObj' => &$this,
					'params' => $params,
					'URL' => &$newUrl,
				);
				t3lib_div::callUserFunction($userFunc, $hookParams, $this);
			}
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
	protected function encodeSpURL_doEncode($inputQuery, $cHashCache = FALSE, $origUrl = '') {

		$this->cHashParameters = array();
		$this->rebuildCHash = false;

		// Extract all GET parameters into an ARRAY:
		$paramKeyValues = array();
		$additionalVariables = array();
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
	 * Overwriting original method to enable redirection to typolink parameters
	 * 
	*/
	
	/**
	 * Look for redirect configuration.
	 * If the input path is found as key in $this->extConf['redirects'] this method redirects to the URL found as value
	 *
	 * @param	string		Path from SpeakingURL.
	 * @return	void
	 * @see decodeSpURL_doDecode()
	 */
	public function decodeSpURL_checkRedirects($speakingURIpath) {
		$speakingURIpath = trim($speakingURIpath);

		if (isset($this->extConf['redirects'][$speakingURIpath])) {
			$url = $this->extConf['redirects'][$speakingURIpath];
			if (preg_match('/^30[1237];/', $url)) {
				$redirectCode = intval(substr($url, 0, 3));
				$url = substr($url, 4);
				header('HTTP/1.0 ' . $redirectCode . ' Redirect');
			}
			header('Location: ' . t3lib_div::locationHeaderUrl($url));
			exit();
		}

		// Regex redirects:
		if (is_array($this->extConf['redirects_regex'])) {
			foreach ($this->extConf['redirects_regex'] as $regex => $substString) {
				if (preg_match('/' . $regex . '/', $speakingURIpath)) {
					$url = @preg_replace('/' . $regex . '/', $substString, $speakingURIpath);
					if ($url) {
						if (preg_match('/^30[1237];/', $url)) {
							$redirectCode = intval(substr($url, 0, 3));
							header('HTTP/1.0 ' . $redirectCode . ' Redirect');
							$url = substr($url, 4);
						}
						header('Location: ' . t3lib_div::locationHeaderUrl($url));
						exit();
					}
				}
			}
		}

		// DB defined redirects:
		$hash = t3lib_div::md5int($speakingURIpath);
		$url = $GLOBALS['TYPO3_DB']->fullQuoteStr($speakingURIpath, 'tx_realurl_redirects');
		list($redirect_row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'destination,has_moved', 'tx_realurl_redirects',
			'url_hash=' . $hash . ' AND url=' . $url);
		if (is_array($redirect_row)) {
			// Update statistics
			$fields_values = array(
				'counter' => 'counter+1',
				'tstamp' => time(),
				'last_referer' => t3lib_div::getIndpEnv('HTTP_REFERER')
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_realurl_redirects',
				'url_hash=' . $hash . ' AND url=' . $url,
				$fields_values, array('counter'));
				
			/**
			 * This is the part the actually differs from the original method
			 */
			// Convert to realurl url if the path begins with '/id='
			if (t3lib_div::isFirstPartOfStr($redirect_row['destination'], '/id=')) {
				$redirect_row['destination'] = $this->encodeSpURL_doEncode(substr($redirect_row['destination'], 1), $this->extConf['init']['enableCHashCache']);
			}
			/**
			 * This is the part the actually differs from the original method [end]
			 */

			// Redirect
			if ($redirect_row['has_moved']) {
				header('HTTP/1.1 301 Moved Permanently');
			}

			header('Location: ' . t3lib_div::locationHeaderUrl($redirect_row['destination']));
			exit();
		}
	}

	/**
	 * Decodes a speaking URL path into an array of GET parameters and a page id.
	 *
	 * @param	string		Speaking URL path (after the "root" path of the website!) but without query parameters
	 * @param	boolean		If cHash caching is enabled or not.
	 * @return	array		Array with id and GET parameters.
	 * @see decodeSpURL()
	 */
	protected function decodeSpURL_doDecode($speakingURIpath, $cHashCache = FALSE) {

		// Cached info:
		$cachedInfo = array();

		// Convert URL to segments
		$pathParts = explode('/', $speakingURIpath);
		array_walk($pathParts, create_function('&$value', '$value = rawurldecode($value);'));

		// Strip/process file name or extension first
		$file_GET_VARS = $this->decodeSpURL_decodeFileName($pathParts);

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



	/**
	 * Looks up an ID value (integer) in lookup-table based on input alias value.
	 * (The lookup table for id<->alias is meant to contain UNIQUE alias strings for id integers)
	 * In the lookup table 'tx_realurl_uniqalias' the field "value_alias" should be unique (per combination of field_alias+field_id+tablename)! However the "value_id" field doesn't have to; that is a feature which allows more aliases to point to the same id. The alias selected for converting id to alias will be the first inserted at the moment. This might be more intelligent in the future, having an order column which can be controlled from the backend for instance!
	 *
	 * @param	array		Configuration array
	 * @param	string		Alias value to convert to ID
	 * @param	boolean		<code>true</code> if only non-expiring record should be looked up
	 * @return	integer		ID integer. If none is found: false
	 * @see lookUpTranslation(), lookUp_idToUniqAlias()
	 */
	protected function lookUp_uniqAliasToId($cfg, $aliasValue, $onlyNonExpired = FALSE) {
		
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

	/**
	 * Looks up a alias string in lookup-table based on input ID value (integer)
	 * (The lookup table for id<->alias is meant to contain UNIQUE alias strings for id integers)
	 *
	 * @param	array		Configuration array
	 * @param	string		ID value to convert to alias value
	 * @param	integer		sys_language_uid to use for lookup
	 * @param	string		Optional alias value to limit search to
	 * @return	string		Alias string. If none is found: false
	 * @see lookUpTranslation(), lookUp_uniqAliasToId()
	 */
	protected function lookUp_idToUniqAlias($cfg, $idValue, $lang, $aliasValue = '') {
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



	/**
	 * Checks if rootpage_id is set and if not, sets it
	 *
	 * @return	void
	 */
	protected function adjustRootPageId() {
		//~ // we do nothing since auto-detection is shit
	}








}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/aoe_realurl/patch/1.9.4/class.ux_tx_realurl.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/aoe_realurl/patch/1.9.4/class.ux_tx_realurl.php']);
}

?>
