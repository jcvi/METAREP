<?php 
/* SVN FILE: $Id$ */
/* Trna Test cases generated on: 2009-03-21 07:03:26 : 1237635326*/
App::import('Model', 'Trna');

class TrnaTestCase extends CakeTestCase {
	var $Trna = null;
	var $fixtures = array('app.trna', 'app.read');

	function startTest() {
		$this->Trna =& ClassRegistry::init('Trna');
	}

	function testTrnaInstance() {
		$this->assertTrue(is_a($this->Trna, 'Trna'));
	}

	function testTrnaFind() {
		$this->Trna->recursive = -1;
		$results = $this->Trna->find('first');
		$this->assertTrue(!empty($results));

		$expected = array('Trna' => array(
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