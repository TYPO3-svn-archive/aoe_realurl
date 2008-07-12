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
    //private
    var $useUnstrictCacheWhere = FALSE; //flag which indicades that the cache should be checked without using workspace and language id
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
        $this->pObj = &$ref;
        $this->conf = $params['conf'];
        srand(); //init rand for cache
        $this->generator = t3lib_div::makeInstance('tx_aoerealurlpath_pathgenerator');
        $this->generator->init($this->conf);
        $cachemgmtClassName = t3lib_div::makeInstanceClassName('tx_aoerealurlpath_cachemgmt');
        #debug($this->_getLanguageVar());
        $this->cachemgmt = new $cachemgmtClassName($this->_getWorkspaceId(), $this->_getLanguageVar());
        $this->cachemgmt->setCacheTimeout($this->conf['cacheTimeOut']);
        $this->cachemgmt->setRootPid($this->_getRootPid());
        $this->cachemgmt->doCacheClearOnCheck(); //activate automatic clear if expire		
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
        if ($cached = $this->cachemgmt->isInCache($pageId)) {
            $buildedPath = $cached;
        } else {
            $buildPageArray = $this->generator->build($pageId, $this->_getLanguageVar(), $this->_getWorkspaceId());
            $buildedPath = $buildPageArray['path'];
            $buildedPath = $this->cachemgmt->storeUniqueInCache($this->generator->getPidForCache(), $buildedPath);
        }
        if ($buildedPath) {
            $pagePath_exploded = explode('/', $buildedPath);
            //$pagePath_exploded=array();
            //$pagePath_exploded[]='http://www.aoemedia.de';
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
        if ($pageId == false) {
            $this->cachemgmt->useUnstrictCacheWhere();
            $keepPath = array();
            $pageId = $this->cachemgmt->checkCacheWithDecreasingPath($pagePathOrigin, $keepPath);
            $this->cachemgmt->doNotUseUnstrictCacheWhere();
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
        } else //if not defined in realUrlConfig get 0
{
            $pid = 0;
        }
        return $pid;
    }
    /**
     * Gets the value of current language
     *
     * @return	integer		Current language or 0
     */
    function _getLanguageVar ()
    {
        $lang = 0;
        // Setting the language variable based on GETvar in URL which has been configured to carry the language uid:
        if ($this->conf['languageGetVar']) {
            $lang = intval($this->pObj->orig_paramKeyValues[$this->conf['languageGetVar']]);
            // Might be excepted (like you should for CJK cases which does not translate to ASCII equivalents)
            if (t3lib_div::inList($this->conf['languageExceptionUids'], $lang)) {
                $lang = 0;
            }
        }
        if ($lang == 0) {
            $lang = intval(t3lib_div::_GP('L'));
            if ($lang == 0) {
                $lang = intval($this->pObj->getRetrievedPreGetVar('L'));
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
}
?>
