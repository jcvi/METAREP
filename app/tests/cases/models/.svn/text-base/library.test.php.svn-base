<?php 
/* SVN FILE: $Id$ */
/* Library Test cases generated on: 2009-03-21 11:03:13 : 1237650613*/
App::import('Model', 'Library');

class LibraryTestCase extends CakeTestCase {
	var $Library = null;
	var $fixtures = array('app.library', 'app.project', 'app.read');

	function startTest() {
		$this->Library =& ClassRegistry::init('Library');
	}

	function testLibraryInstance() {
		$this->assertTrue(is_a($this->Library, 'Library'));
	}

	function testLibraryFind() {
		$this->Library->recursive = -1;
		$results = $this->Library->find('first');
		$this->assertTrue(!empty($results));

		$expected = array('Library' => array(
			'id'  => 1,
			'name'  => 'Lorem ipsum dolor sit amet',
			'created'  => 'Lorem ipsum dolor sit amet',
			'project_id'  => 1
		));
		$this->assertEqual($results, $expected);
	}
}
?>