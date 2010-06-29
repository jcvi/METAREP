<?php 
/* SVN FILE: $Id$ */
/* RrnasController Test cases generated on: 2009-03-21 07:03:08 : 1237635548*/
App::import('Controller', 'Rrnas');

class TestRrnas extends RrnasController {
	var $autoRender = false;
}

class RrnasControllerTest extends CakeTestCase {
	var $Rrnas = null;

	function setUp() {
		$this->Rrnas = new TestRrnas();
		$this->Rrnas->constructClasses();
	}

	function testRrnasControllerInstance() {
		$this->assertTrue(is_a($this->Rrnas, 'RrnasController'));
	}

	function tearDown() {
		unset($this->Rrnas);
	}
}
?>