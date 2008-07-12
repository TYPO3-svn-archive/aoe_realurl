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
/**
 *
 * @author	Daniel P�tzinger
 * @package realurl
 * @subpackage aoe_realurlpath
 */
class tx_aoerealurlpath_cachemgmt
{
    //cahce key values
    var $workspaceId;
    var $languageId;
    //unique path check
    var $rootPid;
    var $doCacheClear = FALSE;
    var $cacheTimeOut = 0; //timeout in seconds for cache entries
    var $useUnstrictCacheWhere = FALSE;
    function tx_aoerealurlpath_cachemgmt ($workspace, $languageid)
    {
        $this->workspaceId = $workspace;
        $this->languageId = $languageid;
        $this->cacheTimeOut = 0;
        $this->useUnstrictCacheWhere = FALSE;
    }
    function setRootPid ($rootpid)
    {
        $this->rootPid = $rootpid;
    }
    function setLanguageId ($languageid)
    {
        $this->languageId = $languageid;
    }
    function setCacheTimeOut ($time)
    {
        $this->cacheTimeOut = intval($time);
    }
    function useUnstrictCacheWhere ()
    {
        $this->useUnstrictCacheWhere = TRUE;
    }
    function doNotUseUnstrictCacheWhere ()
    {
        $this->useUnstrictCacheWhere = FALSE;
    }
    function doCacheClearOnCheck ()
    {
        $this->doCacheClearOnCheck = TRUE;
    }
    function getWorkspaceId ()
    {
        return $this->workspaceId;
    }
    function getLanguageId ()
    {
        return $this->languageId;
    }
    function getRootPid ()
    {
        return $this->rootPid;
    }
    /**
     * important function: checks the path in the cache: if not found the check against cache is repeted without the last pathpart
     * @param array  $pagePathOrigin  the path which should be searched in cache
     * @param &$keepPath  -> passed by reference -> array with the n last pathparts which could not retrieved from cache -> they are propably preVars from translated parameters (like tt_news is etc...)
     *
     * @return pagid or false
     **/
    function checkCacheWithDecreasingPath ($pagePathOrigin, &$keepPath)
    {
        $sizeOfPath = count($pagePathOrigin);
        $pageId = false;
        for ($i = $sizeOfPath; $i > 0; $i --) {
            $pageId = $this->_readCacheForPath(implode("/", $pagePathOrigin));
            if ($pageId !== false) {
                //found something => break;
                break;
            } else {
                array_unshift($keepPath, array_pop($pagePathOrigin));
            }
        }
        return $pageId;
    }
    //*********************************************************
    /**
     * stores the path in cache and checks if that path is unique, if not this function makes the path unique by adding some numbers
     * (hrows error if caching fails)
     *
     * @param string Path
     * @return string unique path in cache
     **/
    function storeUniqueInCache ($pid, $buildedPath)
    {
        #echo '<hr> request to store:'.$pid.' '.$buildedPath;
        if ($this->isInCache($pid) === false) {
            if ($this->_readCacheForPath($buildedPath)) {
                //$buildedPath.='_'.rand();
                $buildedPath .= '_' . $pid;
            }
            //do insert
            $data['tstamp'] = time();
            $data['path'] = $buildedPath;
            $data['mpvar'] = "";
            $data['workspace'] = $this->getWorkspaceId();
            $data['languageid'] = $this->getLanguageId();
            $data['rootpid'] = $this->getRootPid();
            $data['pageid'] = $pid;
            #echo '-'.$GLOBALS['TYPO3_DB']->INSERTquery("tx_aoerealurlpath_cache",$data);
            if ($GLOBALS['TYPO3_DB']->exec_INSERTquery("tx_aoerealurlpath_cache", $data)) {} else {    #echo 'error';
            }
        }
        return $buildedPath;
    }
    /**
     * checks cache and looks if a path exist (in workspace, rootpid, language)
     * @param string Path
     * @return string unique path in cache
     **/
    function _readCacheForPath ($pagePath)
    {
        $where = "path=\"" . $pagePath . '"' . $this->_getAddCacheWhere(TRUE);
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("*", "tx_aoerealurlpath_cache", $where);
        #$query = $GLOBALS['TYPO3_DB']->SELECTquery("*","tx_aoerealurlpath_cache",$where);
        #debug($query);
        if ($res)
            $result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        if ($result['pageid']) {
            return $result['pageid'];
        } else {
            return false;
        }
    }
    /* check if a pid has allready a builded path in cache (for workspace,language, rootpid)
	return false or pagepath
	*/
    function isInCache ($pid)
    {
        $where = "pageid=" . intval($pid) . $this->_getAddCacheWhere();
        $query = $GLOBALS['TYPO3_DB']->exec_SELECTquery("*", "tx_aoerealurlpath_cache", $where);
        if ($query)
            $result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($query);
        if (! is_array($result)) {
            return false;
        } else {
            if ($this->doCacheClearOnCheck && ($result['tstamp'] + $this->cacheTimeOut) < time()) {
                $this->delCacheForPid($pid);
                return false;
            } else {
                return $result['path'];
            }
        }
    }
    function delCacheForPid ($pid)
    {
        $where = "pageid=" . intval($pid) . $this->_getAddCacheWhere();
        $GLOBALS['TYPO3_DB']->exec_DELETEquery("tx_aoerealurlpath_cache", $where);
    }
    function clearAllCache ()
    {
        $GLOBALS['TYPO3_DB']->exec_DELETEquery("tx_aoerealurlpath_cache", '1=1');
    }
    /**
     * get where for cache table selects based on internal vars
     * $withRootPidCheck is required when selecting for paths -> which should be unique for RootPid
     **/
    function _getAddCacheWhere ($withRootPidCheck = FALSE)
    {
        if ($this->useUnstrictCacheWhere) {
            //without the additional keys, thats for compatibility reasons
            $where = '';
        } else {
            $where = ' AND workspace=' . intval($this->getWorkspaceId()) . ' AND languageid=' . intval($this->getLanguageId());
        }
        if ($withRootPidCheck) {
            $where .= ' AND rootpid=' . intval($this->getRootPid());
        }
        return $where;
    }
}
?>
