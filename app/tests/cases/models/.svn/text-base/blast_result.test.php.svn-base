<?php 
/* SVN FILE: $Id$ */
/* BlastResult Test cases generated on: 2009-03-21 07:03:39 : 1237635399*/
App::import('Model', 'BlastResult');

class BlastResultTestCase extends CakeTestCase {
	var $BlastResult = null;
	var $fixtures = array('app.blast_result', 'app.peptide');

	function startTest() {
		$this->BlastResult =& ClassRegistry::init('BlastResult');
	}

	function testBlastResultInstance() {
		$this->assertTrue(is_a($this->BlastResult, 'BlastResult'));
	}

	function testBlastResultFind() {
		$this->BlastResult->recursive = -1;
		$results = $this->BlastResult->find('first');
		$this->assertTrue(!empty($results));

		$expected = array('BlastResult' => array(
			'id'  => 1,
			'peptide_id'  => 'Lorem ipsum dolor sit amet',
			'query_length'  => 1,
			'blast_database'  => 'Lorem ipsum dolor sit amet',
			'header'  => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida,phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam,vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit,feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'query_start'  => 1,
			'query_stop'  => 1,
			'subject_start'  => 1,
			'subject_stop'  => 1,
			'identity'  => 1,
			'positives'  => 1,
			'tmp1'  => 1,
			'tmp2'  => 1,
			'header2'  => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida,phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam,vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit,feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'tmp3'  => 1,
			'tmp4'  => 1,
			'evalue'  => 1
		));
		$this->assertEqual($results, $expected);
	}
}
?>