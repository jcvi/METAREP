<?php 
/* SVN FILE: $Id$ */
/* ReadsController Test cases generated on: 2009-03-21 07:03:43 : 1237635523*/
App::import('Controller', 'Reads');

class TestReads extends ReadsController {
	var $autoRender = false;
}

class ReadsControllerTest extends CakeTestCase {
	var $Reads = null;

	function setUp() {
		$this->Reads = new TestReads();
		$this->Reads->constructClasses();
	}

	function testReadsControllerInstance() {
		$this->assertTrue(is_a($this->Reads, 'ReadsController'));
	}

	function tearDown() {
		unset($this->Reads);
	}
}
?>