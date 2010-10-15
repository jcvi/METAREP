<?php
/***********************************************************
* File: solr.php
* Description: Handles communication between METAREP and
* the Solr/Lucene server
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
* @version METAREP v 1.2.0
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/
require_once('baseModel.php');

require_once( 'vendors/SolrPhpClient/Apache/Solr/Service.php' );
require_once( 'vendors/SolrPhpClient/Apache/Solr/Service/Balancer.php' );

define('SOLR_CONNECT_EXCEPTION', "There was a problem with fetching data from the Lucene index. Please contact ".METAREP_SUPPORT_EMAIL." if this problem is persistent");

class SolrComponent extends BaseModelComponent {

 	var $uses = array(); 
	private $solrLoadBalancer;
	
 	/**
	 * Define Solr services used for load balancing.
	 * If only one host is specified, load balancing
	 * is swithed of. This is true if a BIG IP host
	 * has been specified or if only the master has 
	 * been specified and no slave server is available. 
	 *	
	 */
   function __construct() {
       parent::__construct();
       
       //stores services used for load balancing 
       $solrServices =  array();
       
       if(defined('SOLR_BIG_IP_HOST')) {
       		 //add big ip as load balancing service (single service)
       		array_push($solrServices,new Apache_Solr_Service(SOLR_BIG_IP_HOST,SOLR_PORT));
       }
       elseif(defined('SOLR_MASTER_HOST')) {
       		//add master as load balancing service
       		array_push($solrServices,new Apache_Solr_Service(SOLR_MASTER_HOST,SOLR_PORT));
       		
       		if(defined('SOLR_SLAVE_HOST')) {
       			//add slave as load balancing service
       			array_push($solrServices,new Apache_Solr_Service(SOLR_SLAVE_HOST,SOLR_PORT));
       		}
       }
       
 	   //create load balancing object
       $this->solrLoadBalancer = new Apache_Solr_Service_Balancer($solrServices);
   }	
 	
	/**
	 * Searches Solr core/index
	 *
	 * @param String $dataset Dataset/Core/Index name to search in
	 * @param String $query Lucene query string http://lucene.apache.org/java/2_4_0/queryparsersyntax.html
	 * @param int $offset The starting offset for result documents
	 * @param int $limit The maximum number of result documents to return
	 * @param array $params key / value pairs for other query parameters (see Solr documentation), use arrays for parameter keys used more than once (e.g. facet.field)
 	 * @param boolean $renameFacets If set to 1, facet names are added based on the facet IDs
 	 * @param const $method PSOT or GET
	 * @return void
	 * @access public
	 * @throws Throws exception if an error occurs during the Solr service call
	 */	
	function search($dataset,$query, $offset = 0, $limit = NUM_SEARCH_RESULTS, $params = array(), $renameFacets=false,$method = 'POST'){
		
		try {		
			$result = $this->solrLoadBalancer->search("/solr/$dataset",$query, $offset,$limit,$params,$method);	
		}
		catch (Exception $e) {				
			//rethrow exception
			throw new Exception($e);
		}
	
		//if documents are being returned
		if($limit > 0) {
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
			if(!empty($facets->facet_fields->library_id)) {
				$this->addLibraryDescriptions($facets);
			}			
		}		
				
		return $result;
	}  
	
	/**
	 * Returns documetn count of dataset/core/index
	 *
	 * @param String $dataset Dataset/Core/Index name
	 * @return void
	 * @access private
	 */
	function count($dataset,$query="*:*",$params=null) {	
		try {
			$result = $this->solrLoadBalancer->search("/solr/$dataset",$query, 0,0,$params, 'POST');		

			//get the number of hits
			$numHits = (int) $result->response->numFound;
		}
		catch (Exception $e) {	
			throw new Exception($e);
		}
		return $numHits;
	}
	
	/**
	 * Deletes Solr core/index
	 *
	 * @param String $dataset Dataset/Core/Index name
	 * @return void
	 * @access private
	 */
	public function deleteIndex($dataset) {		
		
		//command to delete all documetns of an index	
		$removeIndexCommand = "<delete><query>*:*</query></delete>";
		
		try {
			$solr = new Apache_Solr_Service( SOLR_MASTER_HOST, SOLR_PORT, "/solr/$dataset");
			$solr->delete($removeIndexCommand);
			
			//if master/slave configuration sleep to allow slave to synchronize
			if(defined(SOLR_SLAVE_HOST)) {
				sleep(40);
			}
			
			$this->unloadCore($dataset);
		}
		catch(Exception $e){
			throw new Exception($e);
		}
	}
	
	/**
	 * Unloads Solr core and deletes it from the Solr configuration file (solr.xml)
	 *
	 * @param String $dataset Dataset name that equals the core name to be deleted
	 * @return void
	 * @access private
	 */
	private function unloadCore($dataset) {
		try {
			$this->executeUrl($this->getSolrUrl(SOLR_MASTER_HOST,SOLR_PORT)."/solr/admin/cores?action=UNLOAD&core=$dataset");
			
			//unload slave core if a Solr slave host has been defined in the METAREP configuration file
			if(defined('SOLR_SLAVE_HOST')) {
				$this->executeUrl($this->getSolrUrl(SOLR_SLAVE_HOST,SOLR_PORT)."/solr/admin/cores?action=UNLOAD&core=$dataset");
			}
		}
		catch (Exception $e) {
			throw new Exception($e);
		}				
	}

	/**
	 * Creates Solr core and add it to the Solr configuration file (solr.xml)
	 * Each new core is stored in solr-index-dir/project-dir/dataset
	 *
	 * @param Integer $projectId project ids is used to define the root folder of the index directory
	 * @param String $dataset Dataset name that equals the core name that needs to be created
	 * @return void
	 * @access private
	 */
	private function createCore($projectId,$dataset) {		
		try {
			$this->executeUrl($this->getSolrUrl(SOLR_MASTER_HOST,SOLR_PORT)."/solr/admin/cores?action=CREATE&name=$dataset&instanceDir=".SOLR_INSTANCE_DIR."&dataDir=".SOLR_DATA_DIR."/$projectId/$dataset");				
			//create slave core if a Solr slave host has been defined in the METAREP configuration file
			if(defined('SOLR_SLAVE_HOST')) {
				$this->executeUrl($this->getSolrUrl(SOLR_SLAVE_HOST,SOLR_PORT)."/solr/admin/cores?action=CREATE&name=$dataset&instanceDir=".SOLR_INSTANCE_DIR."&dataDir=".SOLR_DATA_DIR."/$projectId/$dataset");
			}
		}
		catch (Exception $e) {
			throw new Exception($e);
		}	
	}
	
	/**
	 * Commits and pptimizes newly created Solr index
	 *
	 * @param String $dataset Dataset name that equals the core name to identify the index to optimize/commit
	 * @return void
	 * @access private
	 */
	private function commitAndOptimize($dataset) {
		$solr = new Apache_Solr_Service( SOLR_MASTER_HOST, SOLR_PORT, "/solr/$dataset");
		
		try {
			$solr->commit();
			$solr->optimize();
		}
		catch (Exception $e) {
			throw new Exception($e);
		}				
	} 

	/**
	 * Returns a Solr Url string based on Solr host and port
	 * Each new core is stored in solr-index-dir/project-dir/dataset
	 *
	 * @param String $host Solr host
	 * @param Integer $port Solr port
	 * @return String Solr Url
	 * @access private
	 */
	private function getSolrUrl($host,$port) {
		return "http://$host:$port";
	}
	
	/**
	 * Merges Solr indices
	 *
	 **@param Integer $projectId Project ID
	 * @param Sttring $		}
		catch (Exception $e) {
			throw new Exception($e);
		}	core name of new index file/core after merging
	 * @param Array $datasets Datasets to be mergede
	 * @return void
	 * @access private
	 */
	public function mergeIndex($projectId,$core,$datasets) {		
		
		//create merge url string
		$mergeUrl = $this->getSolrUrl(SOLR_MASTER_HOST,SOLR_PORT)."/solr/admin/cores?action=mergeindexes&core=$core";
		
		//add index information for cores that are going to be merged
		foreach($datasets as $dataset) {
			$mergeUrl .= "&indexDir=".SOLR_DATA_DIR."/$projectId/$dataset/index";
		}
		
		try {
			$this->log("Create Core: $projectId,$core");
			$this->createCore($projectId,$core);	
			$this->log("Execute Merge Url: $mergeUrl");
			$this->executeUrl($mergeUrl,3600);	
			$this->log("Commit & Optimize Core: $core");
			$this->commitAndOptimize($core);
		}
		catch (Exception $e) {
			throw new Exception($e);
		}		
	}
	
	/**
	 * Executes Solr url command 
	 *
	 **@param String $url Solr command
	 * @return void
	 * @access public
	 */	
	public function executeUrl($url,$timeout = 600) {	
		
		//write request to log file
		$this->log("solr request: $url",LOG_DEBUG);
		
		try {
			$solr = new Apache_Solr_Service();
			$response = $solr->_sendRawGet($url,$timeout);
			$response = serialize($response);
			//write response to log file
			$this->log("solr response: $response",LOG_DEBUG);
		}
		catch (Exception $e) {
			throw new Exception($e);
		}
	}	
	
	/**
	 * Returns facets counts for a certain field
	 *
	 * @param String $dataset Dataset/Core/Index name to search 
	 * @param String $facetField String that specifies the field to generate facets for 
	 * @param Integer $limit Integer that specifies the nunber of top facets to return (default is set to -1 which returns all facets
	 * @return Array Associateive array containing the facet field as key and counts as values
	 * @access public
	 */	
	public function facet($dataset,$facetField,$query='*:*',$limit=-1) {		
		$solrArguments = array(	"facet" => "true",
						'facet.field' => $facetField,
						'facet.sort' =>'count',
						'facet.mincount' => 1,
						"facet.limit" => $limit);			
		try {
			$result = $this->search($dataset,$query,0,0,$solrArguments,false);
			$facets = $result->facet_counts;			
			return (array) $facets->facet_fields->{$facetField};
		}
		catch (Exception $e) {
			throw new Exception($e);
		}
	}		
	
	/**
	 * Pathway helper function
	 *
	 */	
	public function getPathwayCount($filter,$dataset,$level,$pathwayId,$pathwayEnzymeCount,$ecId=null) {		
		$this->Pathway =& ClassRegistry::init('Pathway'); 
		
		#$this->loadModel('Pathway');
		$foundEnzymes = 0;
		$pathwayCount = 0;
		
		$pathway = $this->Pathway->findById($pathwayId);
			
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
				//rethrow exception
				throw new Exception($e);
			}
			
			if(!$result->facet_counts->facet_queries) {
				//rethrow exception
				throw new Exception($e);
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
	public function getPathwayFacets($filter,$dataset,$level,$nodeId,$children,$ecId=null) {
		$this->Pathway =& ClassRegistry::init('Pathway'); 
		
		if($level != 'level 1') {				
			
			$facetQueries = $this->Pathway->getEnzymeFacetQueries($nodeId,$level,$ecId);
						
			$solrArguments = array(	"facet" => "true",
			'facet.field' => array('blast_species','com_name','go_id','ec_id','hmm_id'),
			'fq' => implode(' OR ',$facetQueries),
			'facet.mincount' => 1,
			"facet.limit" => NUM_TOP_FACET_COUNTS);

			try	{			
				$result = $this->search($dataset,$filter,0,0,$solrArguments,true);			
			}
			catch(Exception $e){
				//rethrow exception
				throw new Exception($e);
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
	
	/**
	* Replaces Solr default values with empty string
	**/
	
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

	/**
	* Mapps HMMs names to HMM IDs returned by Solr
	**/
	private function addHmmDescriptions(&$facets) {
		$this->Hmm =& ClassRegistry::init('Hmm'); 
		
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
	

	/**
	* Mapps GO names to GO IDs returned by Solr
	**/
	private function addGeneOntologyDescriptions(&$facets) {
		$this->GoTerm =& ClassRegistry::init('GoTerm'); 
		
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

	/**
	* Mapps Enzyme names to Enzyme IDs returned by Solr
	**/
	private function addEnzymeDescriptions(&$facets) {
		$this->Enzymes =& ClassRegistry::init('Enzymes'); 
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

	/**
	* Mapps Library descriptions to Library IDs returned by Solr
	**/
	private function addLibraryDescriptions(&$facets) {
		$this->Library =& ClassRegistry::init('Library'); 
		
		$libraryHash = array();
		foreach($facets->facet_fields->library_id as $acc => $count) {
			//find go term descritpion
			$result = $this->Library->find('all', array('fields'=> array('description'),'conditions' => array('name' => $acc)));
			$description = 	$result[0]['Library']['description'];
			
			if(!empty($description)) {
				//concatinate accession with library description
				$acc = $acc." | ".$description;
			}
			
			$libraryHash[$acc]= $count;		
		}
		$facets->facet_fields->library_id = $libraryHash;		
	}	
	
	/**
	 * Escape a value for special query characters such as ':', '(', ')', '*', '?', etc.
	 *
	 * NOTE: inside a phrase fewer characters need escaped, use {@link Apache_Solr_Service::escapePhrase()} instead
	 *
	 * @param string $value
	 * @return string
	 */
	private function escapeWord($value) {
		//list taken from http://lucene.apache.org/java/docs/queryparsersyntax.html#Escaping%20Special%20Characters
		$pattern = '/(\+|-|&&|\|\||!|\(|\)|\{|}|\[|]|\^|"|~|\*|\?|:|\\\)/';
		$replace = '\\\$1';

		return preg_replace($pattern, $replace, $value);
	}

	/**
	 * Escape a value meant to be contained in a phrase for special query characters
	 *
	 * @param string $value
	 * @return string
	 */
	private function escapePhrase($value) {
		$pattern = '/("|\\\)/';
		$replace = '\\\$1';

		return preg_replace($pattern, $replace, $value);
	}	
	
	public function escape($value) {
		if(str_word_count($value) >1) {
			$value = $this->escapePhrase($value);
			$value = "\"$value\"";
		}	
		else {
			$value = $this->escapeWord($value);
		}	
		return $value;
	}
}
?>