<?php
/***********************************************************
* File: taxonomy.php
* Description: Taxonomy Model
*
* PHP versions 4 and 5
*
* METAREP : High-Performance Comparative Metagenomics Framework (http://www.jcvi.org/metarep)
* Copyright(c)  J. Craig Venter Institute (http://www.jcvi.org)
*
* Licensed under The MIT License
* Redistributions of files must retain the above copyright notice.
*
* @link http://www.jcvi.org/metarep METAREP Project
* @package metarep
* @version METAREP v 1.3.0
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

class Taxonomy extends AppModel {

	var $name 		= 'taxonomy';
	var $primaryKey = 'taxon_id';
	var $useTable 	= 'taxonomy';

	public function findTopLevelTaxons() {
		return $this->find('all', array('conditions' => array('Taxonomy.taxon_id' => array(2157,2,2759,10239, 28384)),'fields' => array('taxon_id','name')));
	}
	
	public function getTreeQueryByName($name,$field,$rank = '') {		
		$query = "SELECT count(*) as hits,GROUP_CONCAT(DISTINCT CONCAT('$field:',taxon_id) separator ' OR ') as query,  GROUP_CONCAT(DISTINCT concat(taxon_id,' ',name,' ',rank) separator '@') as suggestions FROM taxonomy WHERE name like '%$name%' AND is_shown = 1";
		if(!empty($rank)) {
			$query.=" AND rank='$rank'"; 
		}		
		$results = $this->query($query);
		
		//debug($results);
		$search['hits']  =  $results[0][0]['hits'];
		$search['query'] =  $results[0][0]['query'];
		$search['suggestions'] =  explode('@',$results[0][0]['suggestions']);		
		return $search;		
	}
	
//	public function getSpeciesIdQueryByName($name) {
//		$results = $this->query("SELECT GROUP_CONCAT(DISTINCT CONCAT('blast_species:','\"',name,'\"') separator ' OR ') as query,  GROUP_CONCAT(DISTINCT concat(taxon_id,' ',name) separator '@') as suggestions FROM taxonomy WHERE name like '%$name%' AND rank = 'species' AND is_shown = 1");	
//		$search['query'] =  $results[0][0]['query'];
//		$search['suggestions'] =  explode('@',$results[0][0]['suggestions']);		
//		return $search;			
//	}
}
?>