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
 * @author	Daniel Poetzinger
 */

include_once (t3lib_extMgm::extPath('aoe_realurlpath') . 'class.tx_aoerealurlpath_cachemgmt.php');
/**
 *
 * @author	Daniel Poetzinger
 * @package realurl
 * @subpackage aoe_realurlpath
 */
class tx_aoerealurlpath_processcmdmaphook
{
   function processDatamap_afterDatabaseOperations ($status, $table, $id, $fieldArray, &$reference) {  
   	                    
    	if ($table=='pages') {
    		 $cache = new tx_aoerealurlpath_cachemgmt($GLOBALS['BE_USER']->workspace, 0);
	         $cache->markAsDirtyCompletePid($id);
	    }
       if ($table=='pages_language_overlay') {
          
           $pid=$reference->checkValue_currentRecord['pid'];
           if ($pid) {
    		 $cache = new tx_aoerealurlpath_cachemgmt($GLOBALS['BE_USER']->workspace, 0);
	         $cache->markAsDirtyCompletePid($pid);
           }
	    }
	}	
}
?>
