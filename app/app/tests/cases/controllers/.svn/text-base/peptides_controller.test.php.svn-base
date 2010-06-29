<?php 
/* SVN FILE: $Id$ */
/* PeptidesController Test cases generated on: 2009-03-21 09:03:29 : 1237641449*/
App::import('Controller', 'Peptides');

class TestPeptides extends PeptidesController {
	var $autoRender = false;
}

class PeptidesControllerTest extends CakeTestCase {
	var $Peptides = null;

	function setUp() {
		$this->Peptides = new TestPeptides();
		$this->Peptides->constructClasses();
	}

	function testPeptidesControllerInstance() {
		$this->assertTrue(is_a($this->Peptides, 'PeptidesController'));
	}

	function tearDown() {
		unset($this->Peptides);
	}
}
?>