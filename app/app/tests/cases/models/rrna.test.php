<?php 
/* SVN FILE: $Id$ */
/* Rrna Test cases generated on: 2009-03-21 07:03:01 : 1237635301*/
App::import('Model', 'Rrna');

class RrnaTestCase extends CakeTestCase {
	var $Rrna = null;
	var $fixtures = array('app.rrna', 'app.read');

	function startTest() {
		$this->Rrna =& ClassRegistry::init('Rrna');
	}

	function testRrnaInstance() {
		$this->assertTrue(is_a($this->Rrna, 'Rrna'));
	}

	function testRrnaFind() {
		$this->Rrna->recursive = -1;
		$results = $this->Rrna->find('first');
		$this->assertTrue(!empty($results));

		$expected = array('Rrna' => array(
			'id'  => 1,
			'read_id'  => 'Lorem ipsum dolor sit amet',
			'sequence'  => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida,phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam,vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit,feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'checksum'  => 'Lorem ipsum do',
			'type'  => 'Lorem ipsum dolor sit amet'
		));
		$this->assertEqual($results, $expected);
	}
}
?>