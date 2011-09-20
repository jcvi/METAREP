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
 * @version METAREP v 1.3.0
 * @author Johannes Goll
 * @lastmodified 2010-07-09
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 **/

class Pathway extends AppModel {

	var $useTable = false;
	var $keggBaseUrl 	= 'http://www.genome.jp/kegg-bin/show_pathway?';
	var $metacycBaseUrl = 'http://biocyc.org/META/NEW-IMAGE?type=PATHWAY&object=';

	
	public function getCategories($level,$pathwayModel) {
		$pathways = $this->getByLevel($level,$pathwayModel);
		foreach($pathways as $pathway) {
		
			//init pathway information
			$pathwayId 			= $pathway[$pathwayModel]['id'];
			$pathwayExternalId  = $pathway[$pathwayModel]['external_id'];
			$pathwayName		= $pathway[$pathwayModel]['name'];
	
			if(empty($pathwayExternalId)) {
					$pathwayExternalId = $pathwayId;
			}
			
			//string pad in database ?
			if($pathwayModel === KEGG_PATHWAYS || $pathwayModel === KEGG_PATHWAYS_KO) {
				$pathwayExternalId 	= str_pad($pathwayExternalId,5,0,STR_PAD_LEFT);
			}
					
			$counts[$pathwayExternalId]['sum']  = 0;	
			$counts[$pathwayExternalId]['name'] = $pathwayName;	
			$counts[$pathwayExternalId]['query'] = $this->getQuery($pathwayId,$pathwayModel);
		}	
		return $counts;		
	}	
	
	private function getQuery($parentId,$pathwayModel) {		
		
		if($pathwayModel === KEGG_PATHWAYS || $pathwayModel === METACYC_PATHWAYS) {
			//cache high level pathway results		
			if($this->isHighLevelPathwayCategory($pathwayModel,$parentId)) {
				if(($pathwayEnzymeIds = Cache::read($pathwayModel."_".$parentId.'_ec_fq')) === false) {
					$pathwayEnzymeIds = $this->getEnzymeIds($parentId,$pathwayModel);		
					Cache::write($pathwayModel."_".$parentId.'_ec_fq',$pathwayEnzymeIds);
				}
			}
			else {
				$pathwayEnzymeIds = $this->getEnzymeIds($parentId,$pathwayModel);		
			}
			
			if(count($pathwayEnzymeIds) == 0) {
				return null;
			}
			else {
				return $filterQuery = "ec_id:(".implode(' OR ',array_unique($pathwayEnzymeIds)).")";
			}
		}
		else if($pathwayModel === KEGG_PATHWAYS_KO){			
			$node = $this->getById($parentId,$pathwayModel);
			
			if($node['level'] === 'kegg-ortholog') {
				$koId 		= str_pad($node['ko_id'],5,0,STR_PAD_LEFT);
				return "ko_id:$koId";	
			}
			else {
				return "kegg_tree:$parentId";	
			}
		}
	}
	
	public function getTwoLevelSumary($numPeptidesDataset,&$solr,$dataset,$query,$pathwayModel) {
		$twoLevelSummary = array();
		
		$superPathways = $this->getByLevel('super-pathway',$pathwayModel);
			
		foreach($superPathways as $superPathway) {
			$pathwayResults		= array();
			$superPathwayId 	= $superPathway[$pathwayModel]['id'];
			$superPathwayName 	= $superPathway[$pathwayModel]['name'];
			
			$pathways	= $this->query("select id,external_id,name,child_count,level FROM $pathwayModel
			 where parent_id = '$superPathwayId' and level='pathway'" );
			
			foreach($pathways as $pathway) {

				#for each pathway in level 2 determine the number of enzymes in the dataset
				$numFoundEnzymes 	= 0;
									
				$pathwayId 			= $pathway[$pathwayModel]['id'];
				$pathwayExternalId  = $pathway[$pathwayModel]['external_id'];
				$pathwayName		= $pathway[$pathwayModel]['name'];
				$numPathwayEnzymes	= $pathway[$pathwayModel]['child_count'];
				

				#$pathwayLevel		= $pathway[$pathwayModel]['level'];

				//string pad in database ?
				if($pathwayModel == KEGG_PATHWAYS) {
					$pathwayExternalId 	= str_pad($pathwayExternalId,5,0,STR_PAD_LEFT);
				}
					
				$result 	= $this->getEnzymeCounts($pathwayId,$solr,$dataset,$query,$pathwayModel);
				$numResults = $result['numResults'];
				$enzymes = $result['enzymes'];
				
				$numPeptidesPathway = $result['numResults'];
				$numFoundEnzymes	= count($enzymes);
				
				$ecUrlString 		= '+'.implode('+',array_keys($enzymes));
				$pathwayUrl 		= $this->getUrl($pathwayExternalId,$pathwayModel,$ecUrlString);
				$percFoundEnzymes 	= round($numFoundEnzymes/$numPathwayEnzymes,4)*100;					
				$percPeptidesPathway= round($numPeptidesPathway/$numPeptidesDataset,4)*100;
					
				array_push($pathwayResults,compact('pathwayExternalId','pathwayName','pathwayUrl','numPathwayEnzymes','numFoundEnzymes',
						'percFoundEnzymes','percFoundEnzymes','numPeptidesPathway','percPeptidesPathway'));
					
			}
			$twoLevelSummary[$superPathwayName]=$pathwayResults;

		}
		
		return $twoLevelSummary;
	}
	
	/**
	 * Returns array that contains facet counts for several data types.
	 *
	 *
	 * @param String $filter Lucene filter query
	 * @param String $dataset dataset
	 * @param String $level hierarchical KEGG pathway [level 1-3 or enzyme]
	 * @param String $nodeId parent pathway node id
	 * @param String $children parent pathway node id
	 * @return void
	 * @access public
	 */
	function getFacets($parentId,&$solr,$dataset,$query,$facetFields,$pathwayModel) {
		if($pathwayModel === KEGG_PATHWAYS || $pathwayModel === METACYC_PATHWAYS) {
			$pathwayEnzymeIds = $this->getEnzymeIds($parentId,$pathwayModel);
			
			$filterQuery = "ec_id:(".implode(' OR ',array_unique($pathwayEnzymeIds)).")";
	
			$solrArguments = array(
									"facet" => "true",
									'facet.field' 	=> array_keys($facetFields),
									'fq' 			=> $filterQuery,
									'facet.mincount'=> 1,
									"facet.limit" 	=> NUM_TOP_FACET_COUNTS
			);
	
			try	{
				$result = $solr->search($dataset,$query,0,0,$solrArguments,true);
			}
			catch(Exception $e){
				
				//rethrow exception
				throw new Exception($e);
			}
			return $result->facet_counts;
		}
		else if($pathwayModel === KEGG_PATHWAYS_KO){
			$filterQuery = "kegg_tree:$parentId";
	
			$solrArguments = array(
									"facet" => "true",
									'facet.field' 	=> array_keys($facetFields),
									'fq' 			=> $filterQuery,
									'facet.mincount'=> 1,
									"facet.limit" 	=> NUM_TOP_FACET_COUNTS
			);
	
			try	{
				$result = $solr->search($dataset,$query,0,0,$solrArguments,true);
			}
			catch(Exception $e){
				//rethrow exception
				throw new Exception($e);
			}
			return $result->facet_counts;
		}
	}

	public function getCount($parentId,&$solr,$dataset,$query,$pathwayModel) {		
		
		$filterQuery = $this->getQuery($parentId,$pathwayModel);
		if(is_null($filterQuery)) {
			return 0;
		}
		else {
			$solrArguments = array('fq' => $filterQuery);	
		}
		
		try	{			
			$counts = $solr->count($dataset,$query,$solrArguments);	
		}
		catch(Exception $e){
			throw new Exception($e);
		}
		return $counts;
	}

	
	private function getEnzymeCounts($parentId,$solr,$dataset,$query,$pathwayModel) {
		$foundEnzymes = array();		
			
		//cache high level pathway results		
		if($this->isHighLevelPathwayCategory($pathwayModel,$parentId)) {
			if(($pathwayEnzymeIds = Cache::read($pathwayModel."_".$parentId.'_ec_fq')) === false) {
				$pathwayEnzymeIds = $this->getEnzymeIds($parentId,$pathwayModel);		
				Cache::write($pathwayModel."_".$parentId.'_ec_fq',$pathwayEnzymeIds);
			}
		}
		
		$pathwayEnzymeIds = $this->getEnzymeIds($parentId,$pathwayModel);				
		
		$filterQuery = "ec_id:(".implode(' OR ',array_unique($pathwayEnzymeIds)).")";
		
		$solrArguments = array(
								'fq' => $filterQuery,
								'facet' => 'true',
								'facet.field' => 'ec_id',
								'facet.mincount'=> 1,
								"facet.limit" 	=> -1
						);						
		try	{			
			$result = $solr->search($dataset,$query,0,0,$solrArguments);			
		}
		catch(Exception $e){
			throw new Exception($e);
		}
		
		$numResults = $result->response->numFound;
		
		$foundPathwayEnzymes = array();
		
		if($numResults > 0) {
			
			$foundEnzymes 	= (array) $result->facet_counts->facet_fields->ec_id;
			unset($result);
			
			$foundEnzymeIds =  array_keys($foundEnzymes);
			
			foreach($pathwayEnzymeIds as $pathwayEnzymeId) {	
				$exactMatch = str_replace('*','-',$pathwayEnzymeId);
				
				if(array_key_exists($exactMatch,$foundEnzymes)) {
					$foundPathwayEnzymes[$exactMatch] = $foundEnzymes[$exactMatch];
				}
				else{				
					$matches = preg_grep("/^$pathwayEnzymeId$/",$foundEnzymeIds);
					
					if(count($matches) == 1) {
						$foundPathwayEnzymes[$exactMatch] = $foundEnzymes[array_shift($matches)];
					}
					else if(count($matches) > 2 ) {						
						$filterQuery = "ec_id:(".implode(' OR ',$matches).")";
						
						$solrArguments = array('fq' => $filterQuery);						
						
						try	{			
							$count = $solr->count($dataset,$query,$solrArguments);							
						}
						catch(Exception $e){
							throw new Exception($e);
						}
						$foundPathwayEnzymes[$exactMatch] = $count;
					}					
				}
			} 
		}
		
		return array('numResults'=>$numResults,'enzymes'=>$foundPathwayEnzymes);
	}

	public function getById($pathwayId,$pathwayModel) {	
		
		if($pathwayModel === KEGG_PATHWAYS || $pathwayModel === METACYC_PATHWAYS) {
			$queryResults= $this->query("select id,name,level,external_id,ec_id FROM $pathwayModel where id = $pathwayId");
		
			$results = array();
			$results['id'] 			= $queryResults[0][$pathwayModel]['id'];
			$results['name'] 		= $queryResults[0][$pathwayModel]['name'];
			$results['level'] 		= $queryResults[0][$pathwayModel]['level'];
			$results['external_id'] = $queryResults[0][$pathwayModel]['external_id'];
			$results['ec_id'] 		= $queryResults[0][$pathwayModel]['ec_id'];
		}
		else if($pathwayModel === KEGG_PATHWAYS_KO) {
			$queryResults= $this->query("select id,name,level,external_id,ko_id FROM $pathwayModel where id = $pathwayId");
			
			$results = array();
			$results['id'] 			= $queryResults[0][$pathwayModel]['id'];
			$results['name'] 		= $queryResults[0][$pathwayModel]['name'];
			$results['level'] 		= $queryResults[0][$pathwayModel]['level'];
			$results['external_id'] = $queryResults[0][$pathwayModel]['external_id'];
			$results['ko_id'] 		= $queryResults[0][$pathwayModel]['ko_id'];
		}
		return $results;
	}	
	
	public function getByLevel($pathwayLevel,$pathwayModel) {
		//restrict metacyc pathways to metacyc slim version

		if($pathwayModel === KEGG_PATHWAYS_KO) {			
			$results = $this->query("select id,name,level,external_id,ko_id FROM $pathwayModel where level = '$pathwayLevel'");
		}				
		else if($pathwayModel === METACYC_PATHWAYS && $pathwayLevel === 'pathway') {		
		
			$results = $this->query("select id,name,level,external_id,ec_id FROM $pathwayModel 
			where level='pathway' and parent_id in (select id from $pathwayModel where level='super-pathway')");
		}
		else {
			$results = $this->query("select id,name,level,external_id,ec_id FROM $pathwayModel where level = '$pathwayLevel'");
		}
		
		return $results;
	}	

	function getChildrenByParentId($parentId,$pathwayModel) {
		if($pathwayModel === KEGG_PATHWAYS || $pathwayModel === METACYC_PATHWAYS) {
			$results = $this->query("select id,name,level,external_id,ec_id FROM $pathwayModel where parent_id = $parentId");
		}
		else if($pathwayModel === KEGG_PATHWAYS_KO) {
			$results = $this->query("select id,name,level,external_id,ko_id FROM $pathwayModel where parent_id = $parentId");
		}
		return $results;
	}

	function getUrl($externalId,$pathwayModel,$ecUrlString = '') {
		if($pathwayModel == KEGG_PATHWAYS) {
			return $this->keggBaseUrl.'ec'.str_pad($externalId,5,0,STR_PAD_LEFT).$ecUrlString;
		}
		else if($pathwayModel === KEGG_PATHWAYS_KO){
			return $this->keggBaseUrl.'ko'.str_pad($externalId,5,0,STR_PAD_LEFT).$ecUrlString;
		}		
		else if($pathwayModel === METACYC_PATHWAYS) {
			return $this->metacycBaseUrl.$externalId.'&detail-level=4';
		}

	}

	function getEnzymeQueryByPathwayId($pathwayId,$pathwayModel) {
		$results = $this->query("select count(*) as hits, replace(group_concat(distinct concat('ec_id:',ec_id) separator ' OR '),'-','*') as query from $pathwayModel where parent_id in (SELECT id FROM $pathwayModel where external_id = '$pathwayId' and level='pathway')");
		$search['hits']  =  $results[0][0]['hits'];
		$search['query'] =  $results[0][0]['query'];
		
		//external ID for KEGG pathways needs padding
		if($pathwayModel === KEGG_PATHWAYS) {
			$externalIdString="'ko',lpad(external_id,5,'0')";
		}
		elseif($pathwayModel === METACYC_PATHWAYS) {
			$externalIdString="external_id";
		}
		
		$results = $this->query("SELECT GROUP_CONCAT(DISTINCT concat($externalIdString,' ',name,' (',child_count,' enzymes)') separator '@') as suggestions FROM $pathwayModel where external_id = '$pathwayId' and level='pathway'");
		$search['suggestions'] =  explode('@',$results[0][0]['suggestions']);
		return $search;
	}
	function getEnzymeQueryByPathwayName($pathwayName,$pathwayModel) {
		$results = $this->query("select count(*) as hits,replace(group_concat(distinct concat('ec_id:',ec_id) separator ' OR '),'-','*') as query from $pathwayModel where parent_id in (SELECT id FROM $pathwayModel where name like '%$pathwayName%' and level='pathway')");
		$search['hits'] 	=  $results[0][0]['hits'];
		$search['query']	=  $results[0][0]['query'];
		
		//external ID for KEGG pathways needs padding
		if($pathwayModel === KEGG_PATHWAYS) {
			$externalIdString="'ko',lpad(external_id,5,'0')";
		}
		elseif($pathwayModel === METACYC_PATHWAYS) {
			$externalIdString="external_id";
		}
		
		$results = $this->query("SELECT GROUP_CONCAT(DISTINCT concat($externalIdString,' ',name,' (',child_count,' enzymes)') separator '@') as suggestions FROM $pathwayModel where name like '%$pathwayName%' and level='pathway'");
		$search['suggestions'] =  explode('@',$results[0][0]['suggestions']);
		return $search;
	}
	function getKeggTreeQueryByPathwayName($pathwayName,$pathwayModel) {
		$results = $this->query("select count(*) as hits,replace(group_concat(distinct concat('kegg_tree:',id) separator ' OR '),'-','*') as query from $pathwayModel where name like '%$pathwayName%' and level='pathway'");
		$search['hits'] 	=  $results[0][0]['hits'];
		$search['query']	=  $results[0][0]['query'];
		$results = $this->query("SELECT GROUP_CONCAT(DISTINCT concat('ko',lpad(external_id,5,'0'),' ',name,' (',child_count,' kegg orthologs)') separator '@') as suggestions FROM $pathwayModel where name like '%$pathwayName%' and level='pathway'");
		$search['suggestions'] =  explode('@',$results[0][0]['suggestions']);
		return $search;
	}
	function getKeggTreeQueryByPathwayId($pathwayId,$pathwayModel) {
		$results = $this->query("select count(*) as hits,replace(group_concat(distinct concat('kegg_tree:',id) separator ' OR '),'-','*') as query from $pathwayModel where external_id = $pathwayId and level='pathway'");
		$search['hits'] 	=  $results[0][0]['hits'];
		$search['query']	=  $results[0][0]['query'];
		$results = $this->query("SELECT GROUP_CONCAT(DISTINCT concat('ko',lpad(external_id,5,'0'),' ',name,' (',child_count,' kegg orthologs)') separator '@') as suggestions FROM $pathwayModel where external_id = $pathwayId and level='pathway'");
		$search['suggestions'] =  explode('@',$results[0][0]['suggestions']);
		return $search;
	}	


	/**
	 * Returns string of enzyme ids concatinated by an OR
	 *
	 * @param int $parentId kegg parent id
	 * @param String $level hierarchical KEGG pathway [level 1-3 or enzyme]
	 * @param String $ecId
	 * @return Array containing lists of enzymes used for Solr facet queries
	 * @access public
	 */
	function getEnzymeIds($parentId,$pathwayModel) {
		
		$enzymeIds = array() ;
		
		$parent 			= $this->getById($parentId,$pathwayModel);
		$parentLevel 		= $parent['level'];
		$parentExternalId 	= $parent['external_id'];
		$parentEcId 		= $parent['ec_id'];

		if($parentLevel == 'enzyme') {
			$solrEcId = str_replace("-","*",$parentEcId);
			array_push($enzymeIds,$solrEcId);
			return $enzymeIds;
		}
		else {
			$this->recursiveEnzymeLookup($parentId,$enzymeIds,$pathwayModel);
		}
			
		return $enzymeIds;
	}	
	
	private function recursiveEnzymeLookup($parentId,&$enzymeIds,$pathwayModel) {

		$results = $this->query("select id,ec_id,level FROM $pathwayModel where parent_id = $parentId");

		
		foreach($results as $result) {

			$level = $result[$pathwayModel]['level'];
			
			if($level == 'enzyme') {
				$ecId = $result[$pathwayModel]['ec_id'];
				//transform ex string to Solr query string
				$solrEcId = str_replace("-","*",$ecId);
				array_push($enzymeIds,$solrEcId);

			}
			else {
				//recursivly call this function
				$id = $result[$pathwayModel]['id'];
				$this->recursiveEnzymeLookup($id,$enzymeIds,$pathwayModel);
			}
		}
	}
	
	private function toFacetQueryFormat($facet,$facetField) {
		return "$facetField:$facet";
	}
	
	private function isHighLevelPathwayCategory($pathwayModel,$id) {
		if($id == 1) {
			return true;
		}
		else {
			$results = $this->query("select count(*) as count FROM $pathwayModel where parent_id = 1 and id='$id'");
			return (boolean)  $results[0][0]['count'];
		}
	}
}
?>