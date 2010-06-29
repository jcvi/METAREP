<?php 
/* SVN FILE: $Id$ */
/* LibrariesController Test cases generated on: 2009-03-21 11:03:56 : 1237650656*/
App::import('Controller', 'Libraries');

class TestLibraries extends LibrariesController {
	var $autoRender = false;
}

class LibrariesControllerTest extends CakeTestCase {
	var $Libraries = null;

	function setUp() {
		$this->Libraries = new TestLibraries();
		$this->Libraries->constructClasses();
	}

	function testLibrariesControllerInstance() {
		$this->assertTrue(is_a($this->Libraries, 'LibrariesController'));
	}

	function tearDown() {
		unset($this->Libraries);
	}
}
?>