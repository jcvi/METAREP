<?php 
/* SVN FILE: $Id$ */
/* Read Test cases generated on: 2009-03-21 07:03:51 : 1237635231*/
App::import('Model', 'Read');

class ReadTestCase extends CakeTestCase {
	var $Read = null;
	var $fixtures = array('app.read', 'app.orf', 'app.peptide', 'app.rrna', 'app.trna');

	function startTest() {
		$this->Read =& ClassRegistry::init('Read');
	}

	function testReadInstance() {
		$this->assertTrue(is_a($this->Read, 'Read'));
	}

	function testReadFind() {
		$this->Read->recursive = -1;
		$results = $this->Read->find('first');
		$this->assertTrue(!empty($results));

		$expected = array('Read' => array(
			'id'  => 'Lorem ipsum dolor sit amet',
			'sequence'  => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida,phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam,vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit,feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'checksum'  => 'Lorem ipsum do'
		));
		$this->assertEqual($results, $expected);
	}
}
?>