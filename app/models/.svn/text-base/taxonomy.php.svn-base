<?php
class Taxonomy extends AppModel {

	var $name = 'taxonomy';
	var $primaryKey = 'taxon_id';
	var $useTable = 'taxonomy';

	public function findTopLevelTaxons() {
		return $this->find('all', array('conditions' => array('Taxonomy.taxon_id' => array(2157,2,2759,10239, 28384)),'fields' => array('taxon_id','name')));
	}
}
?>