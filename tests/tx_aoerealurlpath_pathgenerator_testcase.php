<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 AOE media GmbH
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
 * *
 * @author  Daniel Pötzinger
 * @author  Tolleiv Nietsch
 */

//TODO: add testdatabase xml
require_once (t3lib_extMgm::extPath("aoe_realurlpath") . 'class.tx_aoerealurlpath_pathgenerator.php');
// require_once (t3lib_extMgm::extPath('phpunit').'class.tx_phpunit_test.php');
require_once (PATH_t3lib . 'class.t3lib_tcemain.php');
class tx_aoerealurlpath_pathgenerator_testcase extends tx_phpunit_database_testcase
{
    /**
     * Enter description here...
     *
     * @var tx_aoerealurlpath_pathgenerator
     */
	private $pathgenerator;

	public function setUp() {
		$GLOBALS['TYPO3_DB']->debugOutput = true;
		$this->createDatabase();
		$db = $this->useTestDatabase();
		$this->importStdDB();
		//create relevant tables:
		$extList = array('cms','realurl','aoe_realurlpath');
		$extOptList = array('templavoila','languagevisibility','aoe_webex_tableextensions','aoe_localizeshortcut');
        foreach($extOptList as $ext) {
            if(t3lib_extMgm::isLoaded($ext)) {
                $extList[] = $ext;
            }
        }
        $this->importExtensions($extList);

		$this->importDataSet(dirname(__FILE__). '/fixtures/page-livews.xml');
		$this->importDataSet(dirname(__FILE__). '/fixtures/overlay-livews.xml');
		$this->importDataSet(dirname(__FILE__). '/fixtures/page-ws.xml');
		$this->importDataSet(dirname(__FILE__). '/fixtures/overlay-ws.xml');

        $this->pathgenerator = new tx_aoerealurlpath_pathgenerator();
        $this->pathgenerator->init($this->fixture_defaultconfig());
	}

    public function tearDown() {
		$this->cleanDatabase();
   		$this->dropDatabase();
		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);
    }

	public function test_canGetCorrectRootline ()
    {
        $result=$this->pathgenerator->_getRootline(87, 0, 0);
        $count = count($result);
        $first = $result[0];
        $this->assertEquals($count, 4, 'rootline should be 3 long');
        $this->assertTrue(isset($first['tx_aoerealurlpath_overridesegment']), 'tx_aoerealurlpath_overridesegment should be set');
        $this->assertTrue(isset($first['tx_aoerealurlpath_excludefrommiddle']), 'tx_aoerealurlpath_excludefrommiddle should be set');
    }
    public function test_canBuildStandardPaths()
    {
       	// 1) Rootpage
        $result = $this->pathgenerator->build(1, 0, 0);
        $this->assertEquals($result['path'], '', 'wrong path build: root should be empty');

        // 2) Normal Level 2 page
        $result = $this->pathgenerator->build(83, 0, 0);
        $this->assertEquals($result['path'], 'excludeofmiddle', 'wrong path build: should be excludeofmiddle');

        // 3) Page without title informations
        $result = $this->pathgenerator->build(94, 0, 0);
        $this->assertEquals($result['path'], 'normal-3rd-level/page_94', 'wrong path build: should be normal-3rd-level/page_94 (last page should have default name)');
    }
	public function test_canBuildPathsWithExcludeFromMiddle()
    {
       	// page root->excludefrommiddle->subpage(with pathsegment)
        $result = $this->pathgenerator->build(85, 0, 0);
        $this->assertEquals($result['path'], 'subpagepathsegment', 'wrong path build: should be subpage');

        // page root->excludefrommiddle->subpage(with pathsegment)
        $result = $this->pathgenerator->build(87, 0, 0);
        $this->assertEquals($result['path'], 'subpagepathsegment/sub-subpage', 'wrong path build: should be subpagepathsegment/sub-subpage');
    }

	public function test_canBuildPathsWithLanguageOverlay()
    {
       	// page root->excludefrommiddle->languagemix (austria)
        $result = $this->pathgenerator->build(86, 2, 0);
        $this->assertEquals($result['path'], 'own/url/for/austria', 'wrong path build: should be own/url/for/austria');

        // page root->excludefrommiddle->subpage(with pathsegment)
        $result = $this->pathgenerator->build(85, 2, 0);
        $this->assertEquals($result['path'], 'subpagepathsegment-austria', 'wrong path build: should be subpagepathsegment-austria');

        // page root->excludefrommiddle->subpage (overlay with exclude middle)->sub-subpage
        $result = $this->pathgenerator->build(87, 2, 0);
        $this->assertEquals($result['path'], 'sub-subpage-austria', 'wrong path build: should be subpagepathsegment-austria');

        //for french (5)
        $result = $this->pathgenerator->build(86, 5, 0);
        $this->assertEquals($result['path'], 'languagemix-segment', 'wrong path build: should be languagemix-segment');

    }
	public function test_canBuildPathsInWorkspace()
    {
       	// page root->excludefrommiddle->subpagepathsegment-ws
        $result = $this->pathgenerator->build(85, 0, 1);
        $this->assertEquals($result['path'], 'subpagepathsegment-ws', 'wrong path build: should be subpage-ws');

        // page
        $result = $this->pathgenerator->build(86, 2, 1);
        $this->assertEquals($result['path'], 'own/url/for/austria/in/ws', 'wrong path build: should be own/url/for/austria/in/ws');

        //page languagemix in deutsch (only translated in ws)
        $result = $this->pathgenerator->build(86, 1, 1);
        $this->assertEquals($result['path'], 'languagemix-de', 'wrong path build: should be own/url/for/austria/in/ws');

        //page languagemix in deutsch (only translated in ws)
        $result = $this->pathgenerator->build(85, 1, 1);
        $this->assertEquals($result['path'], 'subpage-ws-de', 'wrong path build: should be own/url/for/austria/in/ws');
    }

    public function test_canBuildShortcutPaths() {
        // instead of shortcut page the shortcut target should be used within path
        $result = $this->pathgenerator->build(92, 0, 0);
        $this->assertEquals($result['path'], 'normal-3rd-level/subsection', 'wrong path build: shortcut from uid92 to uid91 should be resolved');

        // shortcuts with a reference to themselfs might be a problem
        $result = $this->pathgenerator->build(95, 0, 0);
        $this->assertEquals($result['path'], 'shortcut-page', 'wrong path build: shortcut shouldn\'t be resolved');
    }

    public function test_canBuildPathIfOverlayUsesNonLatinChars() {

        // some non latin characters are replaced
        $result = $this->pathgenerator->build(83, 4, 0);
        $this->assertEquals($result['path'], 'page-exclude', 'wrong path build: should be pages-exclude');

        // overlay has no latin characters therefore the default record is used
        $result = $this->pathgenerator->build(84, 4, 0);
        $this->assertEquals($result['path'], 'normal-3rd-level', 'wrong path build: should be normal-3rd-level (value taken from default record)');

        // overlay has no latin characters therefore the default record is used
        $result = $this->pathgenerator->build(94, 4, 0);
        $this->assertEquals($result['path'], 'normal-3rd-level/page_94', 'wrong path build: should be normal-3rd-level/page_94 (value from default records and auto generated since non of the pages had relevant chars)');
    }

    public function test_canResolvePathFromDeligatedFlexibleURLField() {

        $this->pathgenerator->init($this->fixture_delegationconfig());

        // Test direct delegation
        $result = $this->pathgenerator->build(97, 0, 0);
        $this->assertEquals($result['path'], 'deligation-target', 'wrong path build: deligation should be executed');

        // Test multi-hop delegation
        $result = $this->pathgenerator->build(96, 0, 0);
        $this->assertEquals($result['path'], 'deligation-target', 'wrong path build: deligation should be executed');

    }

    public function test_canResolveURLFromDeligatedFlexibleURLField() {

        $this->pathgenerator->init($this->fixture_delegationconfig());

        $result = $this->pathgenerator->build(99, 0, 0);
        $this->assertEquals($result['path'], 'www.aoemedia.de', 'wrong path build: deligation should be executed');

    }

    public function fixture_defaultconfig ()
    {
        $conf = array('type' => 'user' , 'userFunc' => 'EXT:aoe_realurlpath/class.tx_aoerealurlpath_pagepath.php:&tx_aoerealurlpath_pagepath->main' , 'spaceCharacter' => '-' , 'cacheTimeOut' => '100' , 'languageGetVar' => 'L' , 'rootpage_id' => '1' , 'segTitleFieldList' => 'alias,tx_aoerealurlpath_overridesegment,nav_title,title,subtitle');
        return $conf;
    }
    public function fixture_delegationconfig ()
    {
        $conf = array('type' => 'user' , 'userFunc' => 'EXT:aoe_realurlpath/class.tx_aoerealurlpath_pagepath.php:&tx_aoerealurlpath_pagepath->main' , 'spaceCharacter' => '-' , 'cacheTimeOut' => '100' , 'languageGetVar' => 'L' , 'rootpage_id' => '1' , 'segTitleFieldList' => 'alias,tx_aoerealurlpath_overridesegment,nav_title,title,subtitle', 'delegation'=>array( 77 => 'url' ) );
        return $conf;
    }

	/**
	 * Changes current database to test database
	 *
	 * @param string $databaseName	Overwrite test database name
	 * @return object
	 */
	protected function useTestDatabase($databaseName = null) {
		$db = $GLOBALS['TYPO3_DB'];

		if ($databaseName) {
			$database = $databaseName;
		} else {
			$database = $this->testDatabase;
		}

		if(!$db->sql_select_db($database)) {
            die("Test Database not available");
        }

		return $db;
	}
}
?>
