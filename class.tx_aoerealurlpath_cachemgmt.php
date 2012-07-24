<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2008 AOE media
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
 * @author	Daniel Poetzinger
 * @author	Tolleiv Nietsch
 */

/**
 * TODO:
 - check if internal cache array can improve speed
 - move oldlinks to redirects
 - check last updatetime of pages
 **/
include_once (t3lib_extMgm::extPath('aoe_realurlpath') . 'class.tx_aoerealurlpath_pathgenerator.php');

/**
 *
 * @author	Daniel Poetzinger
 * @package realurl
 * @subpackage aoe_realurlpath
 */
class tx_aoerealurlpath_cachemgmt {
	
	//cahce key values
	var $workspaceId;
	var $languageId;
	//unique path check
	var $rootPid;
	var $cacheTimeOut = 1000; //timeout in seconds for cache entries
	var $useUnstrictCacheWhere = FALSE;
	
	/**
	 * @var t3lib_DB
	 */
	protected $dbObj;
	
	static $cache = array();

	/**
	 * Class constructor (PHP4 style)
	 * 
	 * @param int $workspace
	 * @param int $languageid
	 */
	function tx_aoerealurlpath_cachemgmt($workspace, $languageid) {
		$this->workspaceId = $workspace;
		$this->languageId = $languageid;
		$this->useUnstrictCacheWhere = FALSE;
		$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['aoe_realurlpath']);
		if (isset($confArr['defaultCacheTimeOut'])) {
			$this->setCacheTimeOut($confArr['defaultCacheTimeOut']);
		}
		$this->dbObj = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * important function: checks the path in the cache: if not found the check against cache is repeted without the last pathpart
	 * @param array  $pagePathOrigin  the path which should be searched in cache
	 * @param &$keepPath  -> passed by reference -> array with the n last pathparts which could not retrieved from cache -> they are propably preVars from translated parameters (like tt_news is etc...)
	 *
	 * @return pagid or false
	 **/
	function checkCacheWithDecreasingPath($pagePathOrigin, &$keepPath) {
		return $this->checkACacheTableWithDecreasingPath($pagePathOrigin, $keepPath, FALSE);
	}
	/**
	 * important function: checks the path in the cache: if not found the check against cache is repeated without the last pathpart
	 *
	 * @param array  $pagePathOrigin  the path which should be searched in cache
	 * @param &$keepPath  -> passed by reference -> array with the n last pathparts which could not retrieved from cache -> they are propably preVars from translated parameters (like tt_news is etc...)	 *
	 * @return pagid or false
	 **/
	function checkHistoryCacheWithDecreasingPath($pagePathOrigin, &$keepPath) {
		return $this->checkACacheTableWithDecreasingPath($pagePathOrigin, $keepPath, TRUE);
	}
	/**
	 *
	 * @return void
	 */
	function clearAllCache() {
		$this->dbObj->exec_DELETEquery("tx_aoerealurlpath_cache", '1=1');
		$this->dbObj->exec_DELETEquery("tx_aoerealurlpath_cachehistory", '1=1');
	}
	/**
	 *
	 * @return void
	 */
	function clearAllCacheHistory() {
		$this->dbObj->exec_DELETEquery("tx_aoerealurlpath_cachehistory", '1=1');
	}

	/**
	 *
	 * @param int $pid
	 * @return void
	 */
	function delCacheForCompletePid($pid) {
		$where = "pageid=" . intval($pid) . ' AND workspace=' . intval($this->getWorkspaceId());
		$this->dbObj->exec_DELETEquery("tx_aoerealurlpath_cache", $where);
	}
	/**
	 *
	 * @param int $pid
	 * @return void
	 */
	function _delCacheForPid($pid) {
		$this->cache[$this->getCacheKey($pid)] = false;
		$where = "pageid=" . intval($pid) . $this->getAddCacheWhere();
		$this->dbObj->exec_DELETEquery("tx_aoerealurlpath_cache", $where);
	}
	/**
	 * @return void
	 */
	function doNotUseUnstrictCacheWhere() {
		$this->useUnstrictCacheWhere = FALSE;
	}

	/**
	 *
	 * @param int $pid
	 * @return array
	 */
	function getCacheRowForPid($pid) {
		$cacheKey = $this->getCacheKey($pid);
		if (isset($this->cache[$cacheKey]) && is_array($this->cache[$cacheKey])) {
			return $this->cache[$cacheKey];
		}
		
		$row = false;
		$where = 'pageid=' . intval($pid) . $this->getAddCacheWhere();

		$res = $this->getResultForSelectQuery('*', 'tx_aoerealurlpath_cache', $where);
		if ($res) {
			$row = $this->dbObj->sql_fetch_assoc($res);
		}	
		if (is_array($row)) {
			$this->cache[$cacheKey] = $row;
		}
		
		return $row;
	}

	/**
	 *
	 * @param int $pid
	 * @return array
	 */
	function getCacheHistoryRowsForPid($pid) {
		$rows = array();
		$where = "pageid=" . intval($pid) . $this->getAddCacheWhere();
		$query = $this->dbObj->exec_SELECTquery("*", "tx_aoerealurlpath_cachehistory", $where);
		while ( $row = $this->dbObj->sql_fetch_assoc($query) ) {
			$rows[] = $row;
		}
		return $rows;
	}
	/**
	 * @return int
	 */
	function getLanguageId() {
		return $this->languageId;
	}
	/**
	 * @return int
	 */
	function getRootPid() {
		return $this->rootPid;
	}
	/**
	 * @return int
	 */
	function getWorkspaceId() {
		return $this->workspaceId;
	}

	/**
	 * check if a pid has allready a builded path in cache (for workspace,language, rootpid)
	 *
	 * @param int $pid
	 * @return mixed - false or pagepath
	 */
	function isInCache($pid) {
		$row = $this->getCacheRowForPid($pid);
		if (!is_array($row)) {
			return false;
		} else {
			if ($this->_isCacheRowStillValid($row)) {
				return $row['path'];
			} else {
				return false;
			}
		}
	}
	/**
	 *
	 * @param array $row
	 * @return boolean
	 */
	function _isCacheRowStillValid($row) {
		if ($row['dirty'] == 1) {
			return false;
		}
		if (($this->cacheTimeOut > 0) && (($row['tstamp'] + $this->cacheTimeOut) < $this->getTimestamp())) {
			return false;
		}
		return true;
	}
	/**
	 *
	 * @param int $pid
	 * @return void
	 */
	function markAsDirtyCompletePid($pid) {
		$where = "pageid=" . intval($pid) . ' AND workspace=' . intval($this->getWorkspaceId());
		$this->dbObj->exec_UPDATEquery("tx_aoerealurlpath_cache", $where, array('dirty' => 1));
	}

	/**
	 *
	 * @param int $time - in secounds
	 */
	function setCacheTimeOut($time) {
		$this->cacheTimeOut = intval($time);
	}
	/**
	 *
	 * @param int $languageid
	 */
	function setLanguageId($languageid) {
		$this->languageId = $languageid;
	}
	/**
	 *
	 * @param int $rootpid
	 */
	function setRootPid($rootpid) {
		$this->rootPid = $rootpid;
	}
	/**
	 * Stores the path in cache and checks if that path is unique, if not this function makes the path unique by adding some numbers
	 * (throws error if caching fails)
	 *
	 * @param integer $pid
	 * @param string $buildedPath
	 * @return string unique path in cache
	 */
	function storeUniqueInCache($pid, $buildedPath, $disableCollisionDetection = false) {
		$this->dbObj->sql_query('BEGIN');
		if ($this->isInCache($pid) === false) {
			$ignoreUid = $this->getPageIdOfWorkspaceVersion($pid);

			//do cleanup of old cache entries:
			$this->checkForCleanupCache($pid, $buildedPath);
			if ($this->readCacheForPath($buildedPath, $ignoreUid) && !$disableCollisionDetection) {
				$buildedPath .= '_' . $pid;
			}

			$this->insertInCache($pid, $buildedPath);
		}
		$this->dbObj->sql_query('COMMIT');
		return $buildedPath;
	}
	/**
	 * @return void
	 */
	function useUnstrictCacheWhere() {
		$this->useUnstrictCacheWhere = TRUE;
	}

	/**
	 * Get cache key
	 * 
	 * @param int $pid
	 */
	protected function getCacheKey($pid) {
		return implode('-', array($pid, $this->getRootPid(), $this->getWorkspaceId(), $this->getLanguageId()));
	}
	/**
	 * @return integer
	 */
	protected function getTimestamp() {
		return $GLOBALS['EXEC_TIME'];
	}

	/**
	 *
	 * @see checkHistoryCacheWithDecreasingPath
	 * @param array $pagePathOrigin
	 * @param array $keepPath
	 * @param boolean $inHistoryTable
	 * @return int
	 */
	private function checkACacheTableWithDecreasingPath($pagePathOrigin, &$keepPath, $inHistoryTable = FALSE) {
		$sizeOfPath = count($pagePathOrigin);
		$pageId = false;
		for($i = $sizeOfPath; $i > 0; $i--) {
			if (!$inHistoryTable) {
				$pageId = $this->readCacheForPath(implode("/", $pagePathOrigin));
			} else {
				$pageId = $this->readHistoryCacheForPath(implode("/", $pagePathOrigin));
			}
			if ($pageId !== false) {
				//found something => break;
				break;
			} else {
				array_unshift($keepPath, array_pop($pagePathOrigin));
			}
		}
		return $pageId;
	}
	/**
	 *
	 * @param integer $pid
	 * @param string $newPath
	 * @return void
	 */
	private function checkForCleanupCache($pid, $newPath) {
		$row = $this->getCacheRowForPid($pid);
		if (!is_array($row)) {
			return false;
		} elseif (!$this->_isCacheRowStillValid($row)) {
			if ($newPath != $row['path']) {
				$this->insertInCacheHistory($row);
			}
			$this->_delCacheForPid($row['pageid']);
		}
	}

	/**
	 * get where for cache table selects based on internal vars
	 *
	 * @param boolean $withRootPidCheck - is required when selecting for paths -> which should be unique for RootPid
	 * @return string -where clause
	 */
	private function getAddCacheWhere($withRootPidCheck = FALSE) {
		if ($this->useUnstrictCacheWhere) {
			//without the additional keys, thats for compatibility reasons
			$where = '';
		} else {
			$where = ' AND workspace IN (0,' . intval($this->getWorkspaceId()) . ') AND languageid=' . intval($this->getLanguageId());
		}
		if ($withRootPidCheck) {
			$where .= ' AND rootpid=' . intval($this->getRootPid());
		}
		return $where;
	}
	/**
	 * get array of pageIds from cache for a given path
	 * 
	 * @param string $pagePath
	 * @param null|integer $ignoreUid
	 * @return array
	 */
	private function getPageIdsFromCacheForPath($pagePath, $ignoreUid = null) {
		$where = 'path=' . $this->dbObj->fullQuoteStr($pagePath, 'tx_aoerealurlpath_cache');
		if (is_numeric($ignoreUid)) {
			$where .= ' AND pageid != "' . intval($ignoreUid) . '" ';
		}
		$where .= $this->getAddCacheWhere(TRUE);

		$res = $this->getResultForSelectQuery('pageid', 'tx_aoerealurlpath_cache', $where);
		$pageIds = array();
		if ($res) {
			while ($row = $this->dbObj->sql_fetch_assoc($res)) {
				$pageIds[] = $row['pageid'];
			}
		}
		return $pageIds;
	}
	/**
	 * @param integer $pageId
	 * @return integer
	 */
	private function getPageIdOfWorkspaceVersion($pageId) {
		$wsPageId = $pageId;
		if ($this->getWorkspaceId() > 0) {
			$record = t3lib_BEfunc::getLiveVersionOfRecord('pages', $pageId, 'uid');
			if (is_array($record) === FALSE) {
				$record = t3lib_BEfunc::getWorkspaceVersionOfRecord($this->getWorkspaceId(), 'pages', $pageId, '*');
			}
			if (is_array($record)) {
				$wsPageId = $record['uid'];
			}
		}
		return $wsPageId;
	}
	/**
	 * @param string $select
	 * @param string $from
	 * @param string $where
	 * @return pointer MySQL result pointer / DBAL object
	 */
	private function getResultForSelectQuery($select, $from, $where) {
		if (method_exists($this->dbObj, 'exec_SELECTquery_master')) {
			// Force select to use master server in t3p_scalable
			return $this->dbObj->exec_SELECTquery_master($select, $from, $where);
		}
		return $this->dbObj->exec_SELECTquery($select, $from, $where);
	}
	/**
	 * Insert record in cache
	 * 
	 * @param integer $pageid
	 * @param string $buildedPath
	 */
	private function insertInCache($pageid, $buildedPath) {
		$data = array();
		$data['tstamp'] = $this->getTimestamp();
		$data['path'] = $buildedPath;
		$data['mpvar'] = "";
		$data['workspace'] = $this->getWorkspaceId();
		$data['languageid'] = $this->getLanguageId();
		$data['rootpid'] = $this->getRootPid();
		$data['pageid'] = $pageid;

		if ($this->dbObj->exec_INSERTquery("tx_aoerealurlpath_cache", $data)) {
			//TODO ... yeah we saved something in the database - any further problems?
		} else {
			//TODO ... d'oh database didn't like use - what's next?
		}
	}
	/**
	 *
	 * @param array $row
	 * @return void
	 */
	private function insertInCacheHistory($row) {
		unset($row['dirty']);
		$row['tstamp'] = $GLOBALS['EXEC_TIME'];
		$this->dbObj->exec_INSERTquery("tx_aoerealurlpath_cachehistory", $row);
	}
	/**
	 * checks cache and looks if a path exist (in workspace, rootpid, language)
	 *
	 * @param string Path
	 * @return string unique path in cache
	 **/
	private function readCacheForPath($pagePath, $ignoreUid = null) {
		$pageIds = $this->getPageIdsFromCacheForPath($pagePath, $ignoreUid);
		if(count($pageIds) > 0) {
			return $pageIds[0];
		}
		return false;
	}
	/**
	 * checks cache and looks if a path exist (in workspace, rootpid, language)
	 *
	 * @param string Path
	 * @return string unique path in cache
	 **/
	private function readHistoryCacheForPath($pagePath) {
		$where = "path=" . $this->dbObj->fullQuoteStr($pagePath, 'tx_aoerealurlpath_cachehistory') . $this->getAddCacheWhere(TRUE);
		$res = $this->dbObj->exec_SELECTquery("*", "tx_aoerealurlpath_cachehistory", $where);
		if ($res)
			$result = $this->dbObj->sql_fetch_assoc($res);
		if ($result['pageid']) {
			return $result['pageid'];
		} else {
			return false;
		}
	}
}
?>