<?php
/***********************************************************
*  File: solr.php
*  Description: Handles communication with Solr server
*
*  Author: jgoll
*  Date:   Feb 16, 2010
*  
*  test servers:
*  metagenomics-prodd:8983
*  
*  production servers:
*  metarep-solr1 (master)   -   172.20.13.24:8989 
*  metarep-solr2 (slave)    -   172.20.13.25:8989 
*  bigip virtual   			-	172.20.12.25:8989
*  
************************************************************/

require_once('baseModel.php');
#App::import('Vendor', 'SolrPhpClient');

require_once( '../../vendors/SolrPhpClient/Apache/Solr/Service.php' );

define('SOLR_CONNECT_EXCEPTION', 'There was a problem with fetching data from the Lucene index. Please contact metarep-support@jcvi.org if this problem is persistent');

class SolrComponent extends BaseModelComponent {

	//user configuration
	
	//top number of classification returned
	var $numFacetCounts=10;
	
	//component configuration
    var $controller = true;
 	var $uses 		= array('GoTerm','Enzymes','Hmm','Population','Pathway'); 
     
    //solr server configuration
 	var $solrPort 		 	= 8989;	
 	var $solrBigIpHost      = '172.20.12.25';
	var $solrMasterHost     = '172.20.13.24';
	var $solrSlaveHost 		= '172.20.13.25';
	
	var $solrInstanceDir = '/opt/software/apache-solr/solr';
	var $solrDataDir 	 = '/solr-index';
	
   	const METHOD_GET 	= 'GET';
	const METHOD_POST 	= 'POST';

	function search($dataset,$query, $offset = 0, $limit = 10, $params = array(), $renameFacets=false,$method = self::METHOD_POST,$debug=false){
		
		$solr = new Apache_Solr_Service( $this->solrBigIpHost, $this->solrPort,"/solr/$dataset" );

		try {
			$result= $solr->search($query, $offset,$limit,$params,$method);			
		}
		catch (Exception $e) {			
			//rethrow exception
			throw new Exception($e);
		}
	
		#if documents are being returned
		if($limit>0) {
			$hits = $result->response->docs;		
			$this->removeUnassignedValues($hits);
		}
		
		if($renameFacets) {
			$facets = $result->facet_counts;
			if(!empty($facets->facet_fields->ec_id)) {
				$this->addEnzymeDescriptions($facets);
			}			
			if(!empty($facets->facet_fields->go_id)) {
				$this->addGeneOntologyDescriptions($facets);
			}
			if(!empty($facets->facet_fields->hmm_id)) {
				$this->addHmmDescriptions($facets);
			}
		}		
				
		return $result;
	}  

	function count($dataset,$query="*:*",$params=null) {		
		$solr = new Apache_Solr_Service( $this->solrBigIpHost, $this->solrPort, "/solr/$dataset");

		try {
			$result= $solr->search($query, 0,0,$params);
				
			//get the number of hits
			$numHits = (int) $result->response->numFound;
		}
		catch (Exception $e) {	
			throw new Exception($e);
		}
		return $numHits;
	}
	
	#deletes index data and core meta information
	public function deleteIndex($dataset) {			
		$removeIndexCommand 		= "<delete><query>*:*</query></delete>";
		
		try {
			$solr = new Apache_Solr_Service( $this->solrMasterHost, $this->solrPort, "/solr/$dataset");
			$solr->delete($removeIndexCommand);
			
			#sleep to allow slave to synchronize
			sleep(40);
			$this->unloadCore($dataset);
		}
		catch(Exception $e){
			throw new Exception($e);
		}
	}
	
	private function unloadCore($dataset) {
		$this->executeUrl("http://{$this->solrMasterHost}:{$this->solrPort}/solr/admin/cores?action=UNLOAD&core=$dataset");
		$this->executeUrl("http://{$this->solrSlaveHost}:{$this->solrPort}/solr/admin/cores?action=UNLOAD&core=$dataset");
	}
		
	private function createCore($projectId,$dataset) {
		$this->executeUrl("http://{$this->solrMasterHost}:{$this->solrPort}/solr/admin/cores?action=CREATE&name=$dataset&instanceDir={$this->solrInstanceDir}&dataDir={$this->solrDataDir}/$projectId/$dataset");
		$this->executeUrl("http://{$this->solrSlaveHost}:{$this->solrPort}/solr/admin/cores?action=CREATE&name=$dataset&instanceDir={$this->solrInstanceDir}&dataDir={$this->solrDataDir}/$projectId/$dataset");		
	}

	private function commitAndOptimize($dataset) {
		$solr = new Apache_Solr_Service( $this->solrMasterHost, $this->solrPort, "/solr/$dataset");
		$solr->commit();
		$solr->optimize();
	} 
	
	#merges several index file into a new index files d
	public function mergeIndex($projectId,$core,$datasets) {	
		
		#create cores
		$this->createCore($projectId,$core);	

		#populate newly created core with existing cores
		$url = "http://{$this->solrMasterHost}:{$this->solrPort}/solr/admin/cores?action=mergeindexes&core=$core";
		
		foreach($datasets as $dataset) {
			$url .= "&indexDir={$this->solrDataDir}/$projectId/$dataset/index";
		}
		$this->executeUrl($url);	
		$this->commitAndOptimize($core);
	}
	
	public function getPathwayCount($filter,$dataset,$level,$pathwayId,$pathwayEnzymeCount,$ecId=null) {
		
		$foundEnzymes = 0;
		$pathwayCount = 0;
		
		$pathway = $this->Pathway->find($pathwayId);
		#debug($pathway);
		$pathwayUrl = "http://www.genome.jp/kegg-bin/show_pathway?ec".str_pad($pathway['Pathway']['kegg_id'],5,0,STR_PAD_LEFT);	
		
		$facetQueries = $this->Pathway->getEnzymeFacetQueries($pathwayId,$level,$ecId);
		
		$facetQueryChunks = array_chunk($facetQueries,400);
		
		foreach($facetQueryChunks as $facetQueryChunk) {
		
			$solrArguments = array(	"facet" => "true",
				'facet.mincount' => 1,
				'facet.query' => $facetQueryChunk,
				"facet.limit" => -1);	
			
			
			try	{			
				$result = $this->search($dataset,$filter,0,0,$solrArguments);			
			}
			catch(Exception $e){
				
				$this->set('exception',SOLR_CONNECT_EXCEPTION);
				$this->redirect('/projects/index');
			}
			
			if(!$result->facet_counts->facet_queries) {
				debug($facetQueryChunk);
				die();
			}			
			$facetsQueryResults = $result->facet_counts->facet_queries;			
		
			foreach($facetsQueryResults as $facetQuery =>$count) {
				
				if($count > 0) {
					$ecId = str_replace('*','-',str_replace('ec_id:','',$facetQuery));
					$pathwayUrl .="+$ecId";
					$foundEnzymes++;
					$pathwayCount += $count;
				}				
			}			
		}
		#debug($pathwayUrl);
		if($pathwayEnzymeCount > 0) {
			$results['numPathwayEnzymes'] = $pathwayEnzymeCount;
			$results['numFoundEnzymes']   = $foundEnzymes;
			$results['percFoundEnzymes']  = round($foundEnzymes/$pathwayEnzymeCount,4)*100;
			$results['pathwayLink']		  = $pathwayUrl;
			$results['count']			  = $pathwayCount;
			return $results;
		}
		else {
			return $pathwayCount;
		}
	}	
	
	public function getPathwayFacets($filter,$dataset,$level,$nodeId,$children,$ecId=null) {
		
		if($level != 'level 1') {				
			$facetQueries = $this->Pathway->getEnzymeFacetQueries($nodeId,$level,$ecId);
						
			$solrArguments = array(	"facet" => "true",
			'facet.field' => array('blast_species','com_name','go_id','ec_id','hmm_id'),
			'fq' => implode(' OR ',$facetQueries),
			'facet.mincount' => 1,
			"facet.limit" => $this->numFacetCounts);

			try	{			
				$result 	  = $this->search($dataset,$filter,0,0,$solrArguments,true);			
			}
			catch(Exception $e){
				$this->set('exception',SOLR_CONNECT_EXCEPTION);
				$this->redirect('/projects/index');
			}
			unset($facetQueries);	
						
			$results['facets'] 	= $result->facet_counts;			
		}
		else {
			$results['facets'] = null;
		}
		
		$results['numHits'] = $this->getPathwayCount($filter,$dataset,$level,$nodeId,0,$ecId);
				
		return $results;
	}
	
	
	private function removeUnassignedValues(&$hits) {
		
		foreach($hits as $hit) {
			$hit->peptide_id = str_replace('JCVI_PEP_metagenomic.orf.','',$hit->peptide_id);
			$hit->com_name =  str_replace('unassigned','',$hit->com_name);
			$hit->com_name_src =  str_replace('unassigned','',$hit->com_name_src);
			$hit->go_id =  str_replace('unassigned','',$hit->go_id);
			$hit->go_src =  str_replace('unassigned','',$hit->go_src);
			$hit->ec_id =  str_replace('unassigned','',$hit->ec_id);
			$hit->ec_src =  str_replace('unassigned','',$hit->ec_src);
			$hit->blast_species =  str_replace('unassigned','',$hit->blast_species);
			$hit->blast_evalue =  str_replace('unassigned','',$hit->blast_evalue);
			$hit->hmm_id =  str_replace('unassigned','',$hit->hmm_id);
		}		
	} 
	
	private function addHmmDescriptions(&$facets) {
		
		$hmmHash = array();
		foreach($facets->facet_fields->hmm_id as $acc => $count) {
			//find go term descritpion
			$hmmTerm = $this->Hmm->find('all', array('fields'=> array('name'),'conditions' => array('acc' => $acc)));
			
			if(isset($hmmTerm[0])) {
				//concatinate to accession
				$acc = $acc." | ".$hmmTerm[0]['Hmm']['name'];
				
			}
			$hmmHash[$acc]= $count;		
		}
		$facets->facet_fields->hmm_id = $hmmHash;		
	} 
	
	#enriches facets with model descriptions
	private function addGeneOntologyDescriptions(&$facets) {
		$goHash = array();
		
		foreach($facets->facet_fields->go_id as $acc => $count) {
			//find go term descritpion
			$goTerm = $this->GoTerm->find('all', array('fields'=> array('name'),'conditions' => array('acc' => $acc)));
			if(isset($goTerm[0])) {
				//concatinate to accession
				$acc = $acc." | ".$goTerm[0]['GoTerm']['name'];
				
			}
			$goHash[$acc]= $count;		
		}
		$facets->facet_fields->go_id = $goHash;		
	}	

	#enriches facets with model descriptions
	private function addEnzymeDescriptions(&$facets) {
		$ecHash = array();
		foreach($facets->facet_fields->ec_id as $acc => $count) {
			//find go term descritpion
			$ecTerm = $this->Enzymes->find('all', array('fields'=> array('name'),'conditions' => array('ec_id' => $acc)));
			
			if(isset($ecTerm[0])) {
				
				//concatinate to accession
				$acc = $acc." | ".$ecTerm[0]['Enzymes']['name'];
				
			}
			$ecHash[$acc]= $count;		
		}
		$facets->facet_fields->ec_id = $ecHash;		
	}	

	public function executeUrl($url) {
		$this->log("solr request: $url",LOG_DEBUG);
		try {
			$solr = new Apache_Solr_Service();
			$response = $solr->_sendRawGet($url);
			$response=serialize($response);
			$this->log("solr response: $response",LOG_DEBUG);
		}
		catch (Exception $e) {
			throw new Exception($e);
		}
	}		
}
?>