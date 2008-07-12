<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007 Kasper Ligaard (ligaard@daimi.au.dk)
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
/**
 * Test case for checking the PHPUnit 3.1.9
 *
 * WARNING: Never ever run a unit test like this on a live site!
 *
 *
 * @author	Daniel Pötzinger
 */

//TODO: add testdatabase xml
require_once (t3lib_extMgm::extPath("aoe_realurlpath") . 'class.tx_aoerealurlpath_cachemgmt.php');
// require_once (t3lib_extMgm::extPath('phpunit').'class.tx_phpunit_test.php');
require_once (PATH_t3lib . 'class.t3lib_tcemain.php');
class tx_aoerealurlpath_pathgenerator_testcase extends tx_phpunit_database_testcase
{
    public function _test_rootline_DB ()
    {
        $pathg = $this->getPathGenerator();
        $result = $pathg->_getRootline(25, 0, 0);
        $count = count($result);
        $first = $result[0];
        $this->assertEquals($count, 3, 'rootline should be 3 long');
        $this->assertTrue(isset($first['tx_aoerealurlpath_overridesegment']), 'tx_aoerealurlpath_overridesegment should be set');
        $this->assertTrue(isset($first['tx_aoerealurlpath_excludefrommiddle']), 'tx_aoerealurlpath_excludefrommiddle should be set');
    }
    public function _test_build_DB ()
    {
        $pathg = $this->getPathGenerator();
        $result = $pathg->build(25, 0, 0);
        $root = $result['rootPid'];
        $path = $result['path'];
        $this->assertEquals($path, 'serviceinfos/headlines', 'wrong path build');
    }
    public function _test_buildLanguageAndWorkspace_DB ()
    {
        $pathg = $this->getPathGenerator();
        //languageone
        $result = $pathg->build(25, 1, 0);
        $root = $result['rootPid'];
        $path = $result['path'];
        $this->assertEquals($path, 'service/ueberscrift', 'wrong path build');
        // ws
        $result = $pathg->build(25, 0, 1);
        $root = $result['rootPid'];
        $path = $result['path'];
        $this->assertEquals($path, 'serviceinfosws/headlines', 'wrong path build for ws');
        //languageone ws
        $result = $pathg->build(25, 1, 1);
        $root = $result['rootPid'];
        $path = $result['path'];
        $this->assertEquals($path, 'newpath/in/ws', 'wrong path build for ws');
    }
    public function getPathGenerator ()
    {
        $conf = $this->fixture_config();
        $pathg = new tx_aoerealurlpath_pathgenerator();
        $pathg->init($conf);
        return $pathg;
    }
    public function fixture_config ()
    {
        $conf = array('type' => 'user' , 'userFunc' => 'EXT:aoe_realurlpath/class.tx_aoerealurlpath_pagepath.php:&tx_aoerealurlpath_pagepath->main' , 'spaceCharacter' => '-' , 'cacheTimeOut' => '100' , 'languageGetVar' => 'L' , 'rootpage_id' => '1' , 'segTitleFieldList' => 'alias,tx_aoerealurlpath_overridesegment,nav_title,title,subtitle');
        return $conf;
    }
}
?>