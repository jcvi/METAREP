<?php
/***********************************************************
* File: pathway.php
* Description: Pathway Model
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
* @version METAREP v 1.0.1
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

class Pathway extends AppModel {

	/**
	 * Returns array of enzyme ids formatted for Solr facet queries
	 * 
	 * @param int $parentId kegg parent id
	 * @param String $level hierarchical KEGG pathway [level 1-3 or enzyme]
	 * @param String $ecId
	 * @return Array containing lists of enzymes used for Solr facet queries
	 * @access public
	 */
	public function getEnzymeFacetQueries($parentId,$level,$ecId=null) {
		$facetQueries = array() ;
		
		if($level === 'level 1') {
			#debug("select distinct ec_id from pathways where parent_id in(select id from pathways where parent_id in (SELECT id FROM pathways where parent_id = $parentId))");
			$enzymeResults = $this->query("select distinct ec_id from pathways where parent_id in(select id from pathways where parent_id in (SELECT id FROM pathways where parent_id = $parentId))");
		}
		elseif($level === 'level 2') {
			#debug("select distinct ec_id from pathways where parent_id in (SELECT id FROM pathways where parent_id = $parentId)");
			$enzymeResults = $this->query("select distinct ec_id from pathways where parent_id in (SELECT id FROM pathways where parent_id = $parentId)");
		}
		elseif($level === 'level 3') {
			#debug("select distinct ec_id from pathways where parent_id = $parentId");
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
	
	public function getEnzymeQueryByKeggPathwayId($pathwayId) {
		$enzymeResults = $this->query("select replace(group_concat(distinct concat('ec_id:',ec_id) separator ' OR '),'-','*') as enzymes from pathways where parent_id in (SELECT id FROM pathways where kegg_id = '$pathwayId')");
		return $enzymeResults[0][0]['enzymes'];
	}
}
?>