<?php 
/* SVN FILE: $Id$ */
/* Project Test cases generated on: 2009-03-21 11:03:42 : 1237650462*/
App::import('Model', 'Project');

class ProjectTestCase extends CakeTestCase {
	var $Project = null;
	var $fixtures = array('app.project', 'app.library');

	function startTest() {
		$this->Project =& ClassRegistry::init('Project');
	}

	function testProjectInstance() {
		$this->assertTrue(is_a($this->Project, 'Project'));
	}

	function testProjectFind() {
		$this->Project->recursive = -1;
		$results = $this->Project->find('first');
		$this->assertTrue(!empty($results));

		$expected = array('Project' => array(
			'id'  => 1,
			'name'  => 'Lorem ipsum dolor sit amet'
		));
		$this->assertEqual($results, $expected);
	}
}
?>