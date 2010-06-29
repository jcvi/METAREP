<?php 
/* SVN FILE: $Id$ */
/* AnnotationsController Test cases generated on: 2009-03-21 09:03:17 : 1237641497*/
App::import('Controller', 'Annotations');

class TestAnnotations extends AnnotationsController {
	var $autoRender = false;
}

class AnnotationsControllerTest extends CakeTestCase {
	var $Annotations = null;

	function setUp() {
		$this->Annotations = new TestAnnotations();
		$this->Annotations->constructClasses();
	}

	function testAnnotationsControllerInstance() {
		$this->assertTrue(is_a($this->Annotations, 'AnnotationsController'));
	}

	function tearDown() {
		unset($this->Annotations);
	}
}
?>