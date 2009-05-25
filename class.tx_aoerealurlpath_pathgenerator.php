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
	-check if internal cache array makes sense
 **/
/**
 *
 * @author	Daniel P�tzinger
 * @package realurl
 * @subpackage aoe_realurlpath
 */
class tx_aoerealurlpath_pathgenerator
{
    var $pidForCache;
    var $conf; //conf from reaulurl configuration (segTitleFieldList...)
    var $extconfArr; //ext_conf_template vars
    function init ($conf)
    {
        $this->conf = $conf;
        $this->extconfArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['aoe_realurlpath']);
    }
    /**
     *	 returns buildPageArray
     **/
    function build ($pid, $langid, $workspace)
    {
        if ($shortCutPid = $this->_checkForShortCutPageAndGetTarget($pid,$langid,$workspace)) {
            $pid = $shortCutPid;
        }
        $this->pidForCache = $pid;
        $rootline = $this->_getRootline($pid, $langid, $workspace);
        $firstPage = $rootline[0];
        $rootPid = $firstPage['uid'];
        $lastPage = $rootline[count($rootline) - 1];
        $overridePath = $this->_stripSlashes($lastPage['tx_aoerealurlpath_overridepath']);
        if ($overridePath) {
            $pathString = $overridePath;
        } else {
            $pathString = $this->_buildPath($this->conf['segTitleFieldList'], $rootline);
        }
        //$pathString='http://www.aoemedia.de';
        return array('path' => $pathString , 'rootPid' => $rootPid);
    }
    function _stripSlashes ($str_org)
    {
        $str = $str_org;
        if (substr($str, - 1) == '/') {
            $str = substr($str, 0, - 1);
        }
        if (substr($str, 0, 1) == '/') {
            $str = substr($str, 1);
        }
        if ($str_org != $str) {
            return $this->_stripSlashes($str);
        } else {
            return $str;
        }
    }
    function getPidForCache ()
    {
        return $this->pidForCache;
    }
    function _checkForShortCutPageAndGetTarget ($id,$langid = 0,$workspace = 0, $reclevel = 0)
    {
    	if ($this->conf['renderShortcuts']) {
			return false;
		} else {
	        if ($reclevel > 20) {
	            return false;
	        }
	        $this->_initSysPage(0,$workspace);  // check defaultlang since overlays should not contain this (usually)
	        $result = $this->sys_page->getPage($id);
	
	        // if overlay for the of shortcuts is requested
	        if($this->extconfArr['localizeShortcuts'] && t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields'],'shortcut') && $langid) {
	            $relevantLangId = $langid;
	            if($this->extconfArr['useLanguagevisibility']) {
	                require_once(t3lib_extMgm::extPath("languagevisibility").'class.tx_languagevisibility_feservices.php');            
	                $relevantLangId = tx_languagevisibility_feservices::getOverlayLanguageIdForElementRecord($id,'pages',$langid);
	            }
	            
	            $resultOverlay = $this->sys_page->getPageOverlay($id,$relevantLangId);
	            if($resultOverlay["shortcut"]) {
	                $result["shortcut"] = $resultOverlay["shortcut"];
	            }
	        }
	            
	        if ($result['doktype'] == 4) {
	            switch ($result['shortcut_mode']) {
	                case '1': //firstsubpage
	                    if ($reclevel > 10) {
	                        return false;
	                    }
	                    $where = "pid=\"" . $id . "\"";
	                    $query = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "pages", $where, '', 'sorting', '0,1');
	                    if ($query)
	                        $resultfirstpage = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($query);
	                    $subpageShortCut = $this->_checkForShortCutPageAndGetTarget($resultfirstpage['uid'],$langid,$workspace , $reclevel ++);
	                    if ($subpageShortCut !== false) {
	                        return $subpageShortCut;
	                    } else {
	                        return $resultfirstpage['uid'];
	                    }
	                    break;
	                case '2': //random
	                    return false;
	                    break;
	                default:
	                    if($result['shortcut'] == $id) {
	                        return false;
	                    }
	
	                    //look recursive:                    
	                    $subpageShortCut = $this->_checkForShortCutPageAndGetTarget($result['shortcut'],$langid,$workspace , $reclevel ++);
	                    if ($subpageShortCut !== false) {
	                        return $subpageShortCut;
	                    } else {
	                        return $result['shortcut'];
	                    }
	                    break;
	            }
	        } else
	            return false;
		}
    }
    /**
     * @param $pid Pageid of the page where the rootline should be retrieved
     * @return array with rootline for pid
     **/
    function _getRootLine ($pid, $langID, $wsId, $mpvar = '')
    {
        // Get rootLine for current site (overlaid with any language overlay records).
        $this->_initSysPage($langID, $wsId);
        $rootLine = $this->sys_page->getRootLine($pid, $mpvar);        
        return $rootLine;
    }
    /**
     * checks if the user is logged in backend
     * @return bool
     **/
    function _isBELogin ()
    {
        if (! is_object($GLOBALS['BE_USER']))
            return false; else
            return true;
    }
    /**
     * builds the path based on the rootline
     * @param $segment configuration wich field from database should use
     * @param $rootline The rootLine  from the actual page
     * @return array with rootLine and path
     **/
    function _buildPath ($segment, $rootline)
    {
        //echo $segment;
        $segment = t3lib_div::trimExplode(",", $segment);
        //$segment[]="title";
        //$segment = array_reverse($segment);
        $path = array();
        $size = count($rootline);
        $rootline = array_reverse($rootline);
        //do not include rootpage itself, except it is only the root and filename is set:
        if ($size > 1 || $rootline[0]['tx_aoerealurlpath_overridesegment'] == '') {
            array_shift($rootline);
            $size = count($rootline);
        }
        $i = 1;
        foreach ($rootline as $key => $value) {
            //check if the page should exlude from path (if not last)
            if ($value['tx_aoerealurlpath_excludefrommiddle'] && $i != $size) {} else //the normal way
            {

                //t3lib_div::debug(array("lang"=>$rootline,"default"=>$defaultLangRootline));
            
                $pathSeg = $this->_getPathSeg($value,$segment);
                if(strcmp($pathSeg,'')===0) {
                    if((strcmp($pathSeg,'')===0)  && $value['_PAGES_OVERLAY']) {
                        $pathSeg = $this->_getPathSeg($this->_getDefaultRecord($value),$segment);
                    }
                    if(strcmp($pathSeg,'')===0) {
                        $pathSeg = 'page_'.$value['uid'];
                    }
                }
                $path[] = $pathSeg;
            }
            $i ++;
        }
        //build the path
        $path = implode("/", $path);
        //debug($path);
        //cleanup path		
        return $path;
    }
    
    function _getPathSeg($pageRec,$segments) {
        $retVal = '';
        foreach ($segments as $segmentName) {
            if ($this->encodeTitle($pageRec[$segmentName]) != '') {
                $retVal = $this->encodeTitle($pageRec[$segmentName]);
                break;
                //$value['uid']
            }
        }
        return $retVal;
    }
    
    function _getDefaultRecord($l10nrec) {
        $lang = $this->sys_page->sys_language_uid;
        $this->sys_page->sys_language_uid = 0;
        $rec = $this->sys_page->getPage($l10nrec['uid']);
        $this->sys_page->sys_language_uid = $lang;
        return $rec;
    }   
    
    /*******************************
     *
     * Helper functions
     *
     ******************************/
    /**
     * Convert a title to something that can be used in an page path:
     * - Convert spaces to underscores
     * - Convert non A-Z characters to ASCII equivalents
     * - Convert some special things like the 'ae'-character
     * - Strip off all other symbols
     * Works with the character set defined as "forceCharset"
     *
     * @param	string		Input title to clean
     * @return	string		Encoded title, passed through rawurlencode() = ready to put in the URL.
     * @see rootLineToPath()
     */
    function encodeTitle ($title)
    {
        // Fetch character set:
        $charset = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] : $GLOBALS['TSFE']->defaultCharSet;
        // Convert to lowercase:
        $processedTitle = $GLOBALS['TSFE']->csConvObj->conv_case($charset, $title, 'toLower');
        // Convert some special tokens to the space character:
        $space = isset($this->conf['spaceCharacter']) ? $this->conf['spaceCharacter'] : '-';
        $processedTitle = preg_replace('/[ +]+/', $space, $processedTitle); // convert spaces
        // Convert extended letters to ascii equivalents:
        $processedTitle = $GLOBALS['TSFE']->csConvObj->specCharsToASCII($charset, $processedTitle);
        // Strip the rest...:
        $processedTitle = ereg_replace('[^a-zA-Z0-9\\_\\' . $space . ']', '', $processedTitle); // strip the rest
        $processedTitle = ereg_replace('\\' . $space . '+', $space, $processedTitle); // Convert multiple 'spaces' to a single one
        $processedTitle = trim($processedTitle, $space);
        if ($this->conf['encodeTitle_userProc']) {
            $params = array('pObj' => &$this , 'title' => $title , 'processedTitle' => $processedTitle);
            $processedTitle = t3lib_div::callUserFunction($this->conf['encodeTitle_userProc'], $params, $this);
        }
        // Return encoded URL:
        return rawurlencode($processedTitle);
    }
    
    function _initSysPage($langID,$workspace) {
        if (! is_object($this->sys_page)) { // Create object if not found before:
            // Initialize the page-select functions.
            $this->sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
        }
        $this->sys_page->sys_language_uid = $langID;
        if ($workspace!= 0 && is_numeric($workspace)) {
            $this->sys_page->versioningWorkspaceId = $workspace;
            $this->sys_page->versioningPreview = 1;
        } else {
            $this->sys_page->versioningWorkspaceId = 0;
            $this->sys_page->versioningPreview = FALSE;
            	
        }
    }
    
}
?>
