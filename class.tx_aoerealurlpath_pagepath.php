<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 Kasper Sk�rh�j
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
 *
 * @author	Daniel P�tzinger
 */
/*** TODO:
	-check if internal cache array can improve speed
	- move oldlinks to redirects
	- check last updatetime of pages
 **/
include_once (t3lib_extMgm::extPath('aoe_realurlpath') . 'class.tx_aoerealurlpath_pathgenerator.php');
include_once (t3lib_extMgm::extPath('aoe_realurlpath') . 'class.tx_aoerealurlpath_cachemgmt.php');
/**
 *
 * @author	Daniel P�tzinger
 * @package realurl
 * @subpackage aoe_realurlpath
 */
class tx_aoerealurlpath_pagepath
{
    var $generator; //help object for generating paths
    var $insert = false;
    var $pObj;
    var $conf;

	/** Main function -> is called from real_url
     * parameters and results are in $params (some by reference)
     *
     * @param	array		Parameters passed from parent object, "tx_realurl". Some values are passed by reference! (paramKeyValues, pathParts and pObj)
     * @param	tx_realurl		Copy of parent object.
     * @return	mixed		Depends on branching.
     */
    function main ($params, $ref)
    {
        // Setting internal variables:
        //debug($params);
        $this->_setParent($ref);
        $this->_setConf($params['conf']);
        srand(); //init rand for cache
        $this->generator = t3lib_div::makeInstance('tx_aoerealurlpath_pathgenerator');
        $this->generator->init($this->conf);
        $cachemgmtClassName = t3lib_div::makeInstanceClassName('tx_aoerealurlpath_cachemgmt');
        #debug($this->_getLanguageVar());
        $this->cachemgmt = new $cachemgmtClassName($this->_getWorkspaceId(), $this->_getLanguageVar());
        $this->cachemgmt->setCacheTimeout($this->conf['cacheTimeOut']);
        $this->cachemgmt->setRootPid($this->_getRootPid());

        switch ((string) $params['mode']) {
            case 'encode':
                $path = $this->_id2alias($params['paramKeyValues']);
                $params['pathParts'] = array_merge($params['pathParts'], $path);
                unset($params['paramKeyValues']['id']);
                return;
                break;
            case 'decode':
                $id = $this->_alias2id($params['pathParts']);
                return array($id , array());
                break;
        }
    }
    /**
     * gets the path for a pageid, must store and check the generated path in cache
     * (should be aware of workspace)
     *
     * @param array $paramKeyValues from real_url
     * @param array $pathParts from real_url ??
     *
     *	@return string with path
     **/
    function _id2alias ($paramKeyValues)
    {
        $pageId = $paramKeyValues['id'];
        if (! is_numeric($pageId)) {
            $pageId = $GLOBALS['TSFE']->sys_page->getPageIdFromAlias($pageId);
        }
        if ($this->_isCrawlerRun() && $GLOBALS['TSFE']->id==$pageId ) {
            $GLOBALS['TSFE']->applicationData['tx_crawler']['log'][]='aoe_realurlpath: _id2alias '.$pageId.'/'.$this->_getLanguageVar().'/'.$this->_getWorkspaceId();
		    //clear this page cache:
		    $this->cachemgmt->markAsDirtyCompletePid($pageId);
        }

        if ($this->cachemgmt->isInCache($pageId) ) { //&& !$this->_isCrawlerRun()
            $buildedPath = $this->cachemgmt->isInCache($pageId);
        } else {
            $buildPageArray = $this->generator->build($pageId, $this->_getLanguageVar(), $this->_getWorkspaceId());
            $buildedPath = $buildPageArray['path'];
            $buildedPath = $this->cachemgmt->storeUniqueInCache($this->generator->getPidForCache(), $buildedPath);
            if ($this->_isCrawlerRun() && $GLOBALS['TSFE']->id==$pageId ) {
                $GLOBALS['TSFE']->applicationData['tx_crawler']['log'][]='created: '.$buildedPath.' pid:'.$pageId.'/'.$this->generator->getPidForCache();
            }
        }
        if ($buildedPath) {
            $pagePath_exploded = explode('/', $buildedPath);
            return $pagePath_exploded;
        } else {
            return array();
        }
    }
    /**
     * gets the pageid from a pagepath, needs to check the cache
     * @param	array		Array of segments from virtual path
     * @return	integer		Page ID
     **/
    function _alias2id (&$pagePath)
    {
        $pagePathOrigin = $pagePath;
        $keepPath = array();
        //Check for redirect
        $this->_checkAndDoRedirect($pagePathOrigin);
        //read cache with the path you get, decrease path if nothing is found
        $pageId = $this->cachemgmt->checkCacheWithDecreasingPath($pagePathOrigin, $keepPath);
        //fallback 1 - use unstrict cache where
        if ($pageId == false) {
            $this->cachemgmt->useUnstrictCacheWhere();
            $keepPath = array();
            $pageId = $this->cachemgmt->checkCacheWithDecreasingPath($pagePathOrigin, $keepPath);
            $this->cachemgmt->doNotUseUnstrictCacheWhere();
        }
        //fallback 2 - look in history
     	if ($pageId == false) {
            $keepPath = array();
            $pageId = $this->cachemgmt->checkHistoryCacheWithDecreasingPath($pagePathOrigin, $keepPath);
         }

        $pagePath = $keepPath;
        return $pageId;
    }
    //TODO: redirect
    function _checkAndDoRedirect ($path)
    {
        if (t3lib_extMgm::isLoaded('aoe_redirects')) {
	        require_once(t3lib_extMgm::extPath('aoe_redirects').'api/class.redirectmanager.php');

			$redirectmanager = new redirectmanager();
			$redirectmanager->init();
			$redirectmanager->checkAndDoRedirect();
        }
    }
    function _getRootPid ()
    {
        // Find the PID where to begin the resolve:
        if ($this->conf['rootpage_id']) { // Take PID from rootpage_id if any:
            $pid = intval($this->conf['rootpage_id']);
        } else {
            //if not defined in realUrlConfig get 0
            $pid = 0;
        }
        return $pid;
    }
    /**
     * Gets the value of current language
     * first it tries to recieve it from the get-parameters directly
     *
     * @return	integer		Current language or 0
     */
    function _getLanguageVar ()
    {
        $lang = FALSE;
        $getVarName = $this->conf['languageGetVar']?$this->conf['languageGetVar']:'L';

        // Setting the language variable based on GETvar in URL which has been configured to carry the language uid:
        if ($getVarName && array_key_exists($getVarName,$this->pObj->orig_paramKeyValues)) {
            $lang = intval($this->pObj->orig_paramKeyValues[$getVarName]);
            // Might be excepted (like you should for CJK cases which does not translate to ASCII equivalents)
            if (t3lib_div::inList($this->conf['languageExceptionUids'], $lang)) {
                $lang = 0;
            }
        }
        if ($lang === FALSE) {
        	//TODO next line is not covered by a test
            $lang = intval(t3lib_div::_GP($getVarName));
            if ($lang == 0) {
                $lang = intval($this->pObj->getRetrievedPreGetVar($getVarName));
            }
        }
        return $lang;
    }
    //*********************************************************
    //*********************************************************
    function _isBELogin ()
    {
        if (! is_object($GLOBALS['BE_USER']))
            return false; else
            return true;
    }
    /** if workspace preview in FE return that workspace
     **/
    function _getWorkspaceId ()
    {
        if (is_object($GLOBALS['BE_USER']) && t3lib_div::_GP('ADMCMD_noBeUser') != 1) {
            if (is_object($GLOBALS['TSFE']->sys_page)) {
                if ($GLOBALS['TSFE']->sys_page->versioningPreview == 1) {
                    return $GLOBALS['TSFE']->sys_page->versioningWorkspaceId;
                }
            } else {
                if ($GLOBALS['BE_USER']->user['workspace_preview'] == 1) {
                    return $GLOBALS['BE_USER']->workspace;
                }
            }
        }
        return 0;
    }

    /**
     * returns true/false if the current context is within a crawler call (procInstr. tx_cachemgm_recache)
     * This is used for some logging. The status is cached for performance reasons
     *
     * @return boolean
     */
    function _isCrawlerRun() {
        if (t3lib_extMgm::isLoaded('crawler')
				&& $GLOBALS['TSFE']->applicationData['tx_crawler']['running']
				&& in_array('tx_cachemgm_recache', $GLOBALS['TSFE']->applicationData['tx_crawler']['parameters']['procInstructions']))	{
           return true;
		}
		else {
		    return false;
		}
    }

    /**
     * assigns the configuration
     *
     * @param $conf
     * @return void
     */
    function _setConf($conf) {
    	//TODO: validate the incoming conf
        $this->conf = $conf;
    }

    /**
     * assigns the parent object
     *
     * @param tx_realurl    $ref: the parent object
     * @return void
     */
    function _setParent($ref) {
    	$this->pObj = &$ref;
    }
}
?>
