<?php 
/* SVN FILE: $Id$ */
/* Peptide Test cases generated on: 2009-03-21 07:03:59 : 1237635359*/
App::import('Model', 'Peptide');

class PeptideTestCase extends CakeTestCase {
	var $Peptide = null;
	var $fixtures = array('app.peptide', 'app.read', 'app.annotation', 'app.blast_result');

	function startTest() {
		$this->Peptide =& ClassRegistry::init('Peptide');
	}

	function testPeptideInstance() {
		$this->assertTrue(is_a($this->Peptide, 'Peptide'));
	}

	function testPeptideFind() {
		$this->Peptide->recursive = -1;
		$results = $this->Peptide->find('first');
		$this->assertTrue(!empty($results));

		$expected = array('Peptide' => array(
			'id'  => 'Lorem ipsum dolor sit amet',
			'read_id'  => 'Lorem ipsum dolor sit amet',
			'checksum'  => 'Lorem ipsum do',
			'sequence'  => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida,phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam,vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit,feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.'
		));
		$this->assertEqual($results, $expected);
	}
}
?>