<?php
/***********************************************************
*  File: pathway.php
*  Description:
*
*  Author: jgoll
*  Date:   Apr 30, 2010
************************************************************/

class Pathway extends AppModel {
	
	public function getEnzymeFacetQueries($parentId,$level,$ecId=null) {
		$facetQueries = array() ;
		
		if($level === 'level 1') {
			$enzymeResults = $this->query("select distinct ec_id from pathways where parent_id in(select id from pathways where parent_id in (SELECT id FROM pathways where parent_id = $parentId))");
		}
		elseif($level === 'level 2') {
			$enzymeResults = $this->query("select distinct ec_id from pathways where parent_id in (SELECT id FROM pathways where parent_id = $parentId)");
		}
		elseif($level === 'level 3') {
			$enzymeResults = $this->query("select distinct ec_id from pathways where parent_id = $parentId");
		}
		elseif($level === 'enzyme') {	
			$enzymeResult['pathways']['ec_id']=$ecId;		
			$enzymeResults = array($enzymeResult);			
		}
	
		foreach($enzymeResults as $enzymeResult) {
			//parse solr compatible ec id
			$ecId = $enzymeResult['pathways']['ec_id'];

			//add fuzzy matching to handle higher level enzyme classifications, 
			//e.g. 1.3.-.- becomes 1.3.*.*
			$solrEcId = str_replace("-","*",$ecId);
			array_push($facetQueries,"ec_id:$solrEcId");	
		}

		unset($enzymeResults);	
	
		return $facetQueries;
	}	
}
?>