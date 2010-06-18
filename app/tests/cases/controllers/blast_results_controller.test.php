<?php 
/* SVN FILE: $Id$ */
/* BlastResultsController Test cases generated on: 2009-03-21 09:03:10 : 1237642510*/
App::import('Controller', 'BlastResults');

class TestBlastResults extends BlastResultsController {
	var $autoRender = false;
}

class BlastResultsControllerTest extends CakeTestCase {
	var $BlastResults = null;

	function setUp() {
		$this->BlastResults = new TestBlastResults();
		$this->BlastResults->constructClasses();
	}

	function testBlastResultsControllerInstance() {
		$this->assertTrue(is_a($this->BlastResults, 'BlastResultsController'));
	}

	function tearDown() {
		unset($this->BlastResults);
	}
}
?>