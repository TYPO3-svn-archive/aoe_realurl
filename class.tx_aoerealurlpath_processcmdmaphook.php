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

include_once (t3lib_extMgm::extPath ( 'aoe_realurlpath' ) . 'class.tx_aoerealurlpath_cachemgmt.php');
/**
 *
 * @author	Daniel Poetzinger
 * @package realurl
 * @subpackage aoe_realurlpath
 */
class tx_aoerealurlpath_processcmdmaphook {
	function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, &$reference) {

		if ($table == 'pages') {
			$cache = new tx_aoerealurlpath_cachemgmt ( $GLOBALS ['BE_USER']->workspace, 0 );
			$cache->markAsDirtyCompletePid ( $id );
		}
		if ($table == 'pages_language_overlay') {

			$pid = $reference->checkValue_currentRecord ['pid'];
			if ($pid) {
				$cache = new tx_aoerealurlpath_cachemgmt ( $GLOBALS ['BE_USER']->workspace, 0 );
				$cache->markAsDirtyCompletePid ( $pid );
			}
		}
	}


	/**
	 * In case an page-overlay is created automatically the excludeFromMiddle value needs to be copied
	 * See issue #12007
	 *
	 * @author	Tolleiv Nietsch
	 * @package realurl
	 * @subpackage aoe_realurlpath
	 * @param array $incomingFieldArray
	 * @param string $table
	 * @param string $id
	 * @param object $ref
	 * @return void
	 */
	public function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, &$ref) {

		if($table != 'pages_language_overlay' || $id != 'NEW') {
			return;
		}

		if(intval($incomingFieldArray['pid'])) {
			$parent = t3lib_BEfunc::getWorkspaceVersionOfRecord($ref->BE_USER->workspace, $table, intval($incomingFieldArray['pid']), 'uid,tx_aoerealurlpath_excludefrommiddle');
			if(!$parent) {
				$parent = t3lib_BEfunc::getRecord('pages', intval($incomingFieldArray['pid']), 'uid,tx_aoerealurlpath_excludefrommiddle');
			}
			if($parent['tx_aoerealurlpath_excludefrommiddle']) {
				$incomingFieldArray['tx_aoerealurlpath_excludefrommiddle'] = $parent['tx_aoerealurlpath_excludefrommiddle'];
			}
		}
	}

}
?>
