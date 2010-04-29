<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008  <>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
include_once (t3lib_extMgm::extPath('aoe_realurlpath') . 'class.tx_aoerealurlpath_cachemgmt.php');
require_once (PATH_t3lib . 'class.t3lib_extobjbase.php');
/**
 * Module extension (addition to function menu) 'Language Visibility Overview' for the 'testtt' extension.
 *
 * @author     <Daniel P�tzinger>
 * @package    TYPO3
 * @subpackage    tx_languagevisibility
 */
class tx_aoerealurlpath_modfunc1 extends t3lib_extobjbase
{
    /**
     * Returns the menu array
     *
     * @return	array
     */
    function modMenu ()
    {
        global $LANG;
        $menuArray = array(
					'depth' => array(
						0 => $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.depth_0'),
						1 => $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.depth_1'),
						2 => $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.depth_2'),
						3 => $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.depth_3'),
						4 => $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.depth_4'),
						99 => $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.depth_infi'),
					)
				);
        return $menuArray;
    }
    /**
     * MAIN function for page information of localization
     *
     * @return	string		Output HTML for the module.
     */
    function main ()
    {
        global $BACK_PATH, $LANG, $SOBE;
        if ($this->pObj->id) {
            $theOutput = '';
		if (version_compare(TYPO3_version,'4.3.0','<')) {
			$cachemgmtClassName = t3lib_div::makeInstanceClassName('tx_aoerealurlpath_cachemgmt');
			$this->cachemgmt = new $cachemgmtClassName($GLOBALS['BE_USER']->workspace, 0, 1);
		} else {
			$this->cachemgmt = t3lib_div::makeInstance('tx_aoerealurlpath_cachemgmt', $GLOBALS['BE_USER']->workspace, 0, 1);
		}
            $this->pathgen = t3lib_div::makeInstance('tx_aoerealurlpath_pathgenerator');
            $this->pathgen->init(array());

            // Depth selector:
            $h_func = t3lib_BEfunc::getFuncMenu($this->pObj->id, 'SET[depth]', $this->pObj->MOD_SETTINGS['depth'], $this->pObj->MOD_MENU['depth'], 'index.php');
            //$h_func.= t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[lang]',$this->pObj->MOD_SETTINGS['lang'],$this->pObj->MOD_MENU['lang'],'index.php');
            $theOutput .= $h_func;
            // Add CSH:
            $theOutput .= t3lib_BEfunc::cshItem('_MOD_web_aoerealurlpath', 'lang', $GLOBALS['BACK_PATH'], '|<br/>');
            //Add action buttons:
            $theOutput .= '<br /><input name="id" value="' . $this->pObj->id . '" type="hidden"><input type="submit" value="clear all (complete cache and history)" name="_action_clearall">';
            $theOutput .= '<br /><input type="submit" value="clear visible tree" name="_action_clearvisible">';
            $theOutput .= '<br /><input type="submit" value="mark visible tree as dirty" name="_action_dirtyvisible">';
            $theOutput.='<br /><input type="submit" value="clear complete history cache" name="_action_clearallhistory">';
            $theOutput.='<br /><input type="submit" value="regenerate (FE-calls)" name="_action_regenerate">';
            //$theOutput.='<input type="submit" value="regenerate!" name="_action_clearall">';
            //check actions:
            if (t3lib_div::_GP('_action_clearall') != '') {
                $this->cachemgmt->clearAllCache();
            }
        	if (t3lib_div::_GP('_action_clearallhistory') != '') {
                $this->cachemgmt->clearAllCacheHistory();
            }
            // Showing the tree:
            // Initialize starting point of page tree:
            $treeStartingPoint = intval($this->pObj->id);
            $treeStartingRecord = t3lib_BEfunc::getRecordWSOL('pages', $treeStartingPoint);
            $depth = $this->pObj->MOD_SETTINGS['depth'];
            // Initialize tree object:
            $tree = t3lib_div::makeInstance('t3lib_pageTree');
            $tree->init('AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1));
            $tree->addField('l18n_cfg');
            // Creating top icon; the current page
            $HTML = t3lib_iconWorks::getIconImage('pages', $treeStartingRecord, $GLOBALS['BACK_PATH'], 'align="top"');
            $tree->tree[] = array('row' => $treeStartingRecord , 'HTML' => $HTML);
            // Create the tree from starting point:
            if ($depth)
                $tree->getTree($treeStartingPoint, $depth, '');
                #debug($tree->tree);
            // Add CSS needed:
            $css_content = '
				TABLE#langTable {
					margin-top: 10px;
				}
				TABLE#langTable TR TD {
					padding-left : 2px;
					padding-right : 2px;
					white-space: nowrap;
				}

				TR.odd { background-color:#ddd; }

				TD.c-ok { background-color: #A8E95C; }
				TD.c-ok-expired { background-color: #B8C95C; }
				TD.c-shortcut { background-color: #B8E95C; font-weight: 200}
				TD.c-delegation { background-color: #EE0; }
				/*TD.c-nok { background-color: #E9CD5C; }*/
				TD.c-leftLine {border-left: 2px solid black; }
				TD.bgColor5 { font-weight: bold; }
			';
            $marker = '/*###POSTCSSMARKER###*/';
			if(!stristr($this->pObj->content,$marker)) {
				$theOutput = '<style type="text/css">'.$css_content.'</style>' . chr(10).$theOutput;
			} else {
            	$this->pObj->content = str_replace($marker, $css_content . chr(10) . $marker, $this->pObj->content);
			}
            $theOutput .= '<hr />AOE realurl path cache for workspace -' . $GLOBALS['BE_USER']->workspace;
            // Render information table:
            $theOutput .= $this->renderTable($tree);
        }
        return $theOutput;
    }
    /**
     * Rendering the  information table.
     *
     * @param	array		The Page tree data
     * @return	string		HTML for the information table.
     */
    function renderTable (&$tree)
    {
        global $LANG;
        // Title length:
        $titleLen = $GLOBALS['BE_USER']->uc['titleLen'];
        // Put together the TREE:
        $output = '';
        $newOL_js = array();
        $langRecUids = array();
        $languageList = $this->getSystemLanguages();
        //print_r($languageList);
        //traverse Tree:
		$rows = 0;
        foreach ($tree->tree as $data) {
            $tCells = array();
            $editUid = $data['row']['uid'];
            //check actions:
            if (t3lib_div::_GP('_action_clearvisible') != '') {
                $this->cachemgmt->delCacheForCompletePid($editUid);
            }
        	if (t3lib_div::_GP('_action_dirtyvisible') != '') {
                $this->cachemgmt->markAsDirtyCompletePid($editUid);
            }

            //first cell (tree):
            // Page icons / titles etc.
            $tCells[] = '<td' . ($data['row']['_CSSCLASS'] ? ' class="' . $data['row']['_CSSCLASS'] . '"' : '') . '>' . $data['HTML'] . htmlspecialchars(t3lib_div::fixed_lgd_cs($data['row']['title'], $titleLen)) . (strcmp($data['row']['nav_title'], '') ? ' [Nav: <em>' . htmlspecialchars(t3lib_div::fixed_lgd_cs($data['row']['nav_title'], $titleLen)) . '</em>]' : '') . '</td>';
            //language cells:
            foreach ($languageList as $language) {
            	$langId = $language['uid'];
	            if (t3lib_div::_GP('_action_regenerate') != '') {
	               $url=t3lib_div::getIndpEnv('TYPO3_SITE_URL').'index.php?id='.$editUid.'&no_cache=1&L='.$langId;
	               fopen($url,'r');
	            }
                $info = '';
                $params = '&edit[pages][' . $editUid . ']=edit';

                $this->cachemgmt->setLanguageId($langId);
                $cacheRow=$this->cachemgmt->getCacheRowForPid($editUid);
                $cacheHistoryRows=$this->cachemgmt->getCacheHistoryRowsForPid($editUid);
            	$isValidCache=$this->cachemgmt->_isCacheRowStillValid($cacheRow);
            	$hasEntry=FALSE;
            	$path='';
            	if (is_array($cacheRow)) {
            		$hasEntry=TRUE;
                	$path = $cacheRow['path'].' <small style="color: #555"><i>'.($cacheRow['dirty']?'X':'').'('.$cacheRow['rootpid'].')</i></small>';
            	}
				if ($this->pathgen->isDelegationDoktype($data['row']['doktype'])) {
                    $path.=' [Delegation]';
                } 				
            	if (count($cacheHistoryRows)>0) {
            		$path.='[History:'.count($cacheHistoryRows).']';
            	}
                if ($isValidCache) {
                    $status = 'c-ok';
                } elseif ($hasEntry) {
                    $status = 'c-ok-expired';
                } elseif ($data['row']['doktype'] == 4) {
                    $path = '--- [shortcut]';
                    $status = 'c-shortcut';
                } elseif ($this->pathgen->isDelegationDoktype($data['row']['doktype'])) {
                    $status = 'c-delegation';
                } else {
                    $status = 'c-nok';
                }
                $viewPageLink = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::viewOnClick($data['row']['uid'], $GLOBALS['BACK_PATH'], '', '', '', '&L=###LANG_UID###')) . '">' . '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/zoom.gif', 'width="12" height="12"') . ' title="' . $LANG->getLL('lang_viewPage', '1') . '" border="0" alt="" />' . '</a>';
                $viewPageLink=str_replace('###LANG_UID###',$langId,$viewPageLink);
                if ($langId == 0) {
                    //Default
                    //"View page" link is created:
                    $viewPageLink = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::viewOnClick($data['row']['uid'], $GLOBALS['BACK_PATH'], '', '', '', '&L=###LANG_UID###')) . '">' . '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/zoom.gif', 'width="12" height="12"') . ' title="' . $LANG->getLL('lang_viewPage', '1') . '" border="0" alt="" />' . '</a>';
                    $info .= '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $GLOBALS['BACK_PATH'])) . '">' . '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/edit2.gif', 'width="11" height="12"') . ' title="' . $LANG->getLL('lang_editDefaultLanguagePage', '1') . '" border="0" alt="" />' . '</a>';
                    /*	$info.= '<a href="#" onclick="'.htmlspecialchars('top.loadEditId('.intval($data['row']['uid']).',"&SET[language]=0"); return false;').'">'.
							'<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/edit_page.gif','width="12" height="12"').' title="'.$LANG->getLL('lang_editPage','1').'" border="0" alt="" />'.
							'</a>';
							*/
                    $info .= str_replace('###LANG_UID###', '0', $viewPageLink);
                    $info .= $path;
                    // Put into cell:
                    $tCells[] = '<td class="' . $status . ' c-leftLine">' . $info . '</td>';
                } else {

                    //Normal Languages:
                    $tCells[] = '<td class="' . $status . ' c-leftLine">' .$viewPageLink. $path . '</td>';
                }
            }
			$rows++;
            $output .= '
			<tr'.( ( $rows%2 ) ? ' class="odd"' : '' ).'>
				' . implode('
				', $tCells) . '
			</tr>';
        }
        //first ROW:
        //****************
        $firstRowCells[] = '<td style="min-width:300px">' . $LANG->getLL('lang_renderl10n_page', '1') . ':</td>';
        foreach ($languageList as $language) {
            $langId = $language['uid'];
            $firstRowCells[] = '<td class="c-leftLine">' . $language['title'] . ' [' . $language['uid'] . ']</td>';
        }
        $output = '
			<tr class="bgColor2">
				' . implode('
				', $firstRowCells) . '
			</tr>' . $output;
        $output = '

		<table border="0" cellspacing="0" cellpadding="0" id="langTable">' . $output . '
		</table>';
        return $output;
    }
    /**
     * Selects all system languages (from sys_language)
     *
     * @return	array		System language records in an array.
     */
    function getSystemLanguages ()
    {
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_language', '1=1' . t3lib_BEfunc::deleteClause('sys_language'));
        $outputArray = array();
        $outputArray[] = array('uid' => 0 , 'title' => 'Default');
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $outputArray[] = $row;
        }
        return $outputArray;
    }

}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/languagevisibility/modfunc1/class.tx_languagevisibility_modfunc1.php']) {
    include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/languagevisibility/modfunc1/class.tx_languagevisibility_modfunc1.php']);
}
?>
