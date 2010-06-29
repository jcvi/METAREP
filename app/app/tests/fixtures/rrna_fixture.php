<?php 
/* SVN FILE: $Id$ */
/* Rrna Fixture generated on: 2009-03-21 07:03:01 : 1237635301*/

class RrnaFixture extends CakeTestFixture {
	var $name = 'Rrna';
	var $table = 'rrnas';
	var $fields = array(
		'id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'length' => 10, 'key' => 'primary'),
		'read_id' => array('type'=>'string', 'null' => false, 'default' => NULL, 'length' => 200),
		'sequence' => array('type'=>'text', 'null' => false, 'default' => NULL),
		'checksum' => array('type'=>'string', 'null' => false, 'default' => NULL, 'length' => 16),
		'type' => array('type'=>'string', 'null' => false, 'default' => NULL, 'length' => 45),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1))
	);
	var $records = array(array(
		'id'  => 1,
		'read_id'  => 'Lorem ipsum dolor sit amet',
		'sequence'  => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida,phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam,vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit,feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
		'checksum'  => 'Lorem ipsum do',
		'type'  => 'Lorem ipsum dolor sit amet'
	));
}
?>