<?php
/***********************************************************
 * File: solr.php
 * Description: Handles communication between METAREP and
 * Solr/Lucene server(s).
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
 * @version METAREP v 1.4.0
 * @author Johannes Goll
 * @lastmodified 2010-07-09
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 **/
require_once('baseModel.php');
require_once('vendors/SolrPhpClient_r53/Apache/Solr/Service.php' );
require_once('vendors/SolrPhpClient_r53/Apache/Solr/Service/Balancer.php' );
require_once('vendors/SolrPhpClient_r53/Apache/Solr/HttpTransport/Abstract.php' );
require_once('vendors/SolrPhpClient_r53/Apache/Solr/HttpTransport/Interface.php' );
require_once('vendors/SolrPhpClient_r53/Apache/Solr/HttpTransport/Curl.php' );
require_once('vendors/SolrPhpClient_r53/Apache/Solr/HttpTransport/CurlNoReuse.php' );
require_once('vendors/SolrPhpClient_r53/Apache/Solr/HttpTransport/FileGetContents.php' );
require_once('vendors/SolrPhpClient_r53/Apache/Solr/HttpTransport/Response.php' );


define('SOLR_CONNECT_EXCEPTION', "There was a problem with fetching data from the Lucene index. Please contact ".METAREP_SUPPORT_EMAIL." if this problem is persistent");

class SolrComponent extends BaseModelComponent {

	var $uses 		= array();
	var $components = array('MultiCurl');
	var $method 	= 'POST';
	
	var $findTypes = array('doc','docsAndFacet','docCount','hitCount','facetQuery','facet');	
	
	
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
			
		if(PHP_HTTP_TRANSPORT === PHP_HTTP_TRANSPORT_CURL_REUSE) {
			$transportInstance = new Apache_Solr_HttpTransport_Curl();
		}
		else if(PHP_HTTP_TRANSPORT === PHP_HTTP_TRANSPORT_CURL_NOREUSE) {
			$transportInstance = new Apache_Solr_HttpTransport_CurlNoReuse();
		}
		else {
			$transportInstance = new Apache_Solr_HttpTransport_FileGetContents();
		}
			
		if(defined('SOLR_BIG_IP_HOST')) {
			//add big ip as load balancing service (single service)
			array_push($solrServices,new Apache_Solr_Service(SOLR_BIG_IP_HOST,SOLR_PORT,'',$transportInstance));
		}
		elseif(defined('SOLR_MASTER_HOST')) {
			//add master as load balancing service
			array_push($solrServices,new Apache_Solr_Service(SOLR_MASTER_HOST,SOLR_PORT,'',$transportInstance));

			if(defined('SOLR_SLAVE_HOST')) {
				//add slave as load balancing service
				array_push($solrServices,new Apache_Solr_Service(SOLR_SLAVE_HOST,SOLR_PORT,'',$transportInstance));
			}
		}
			
		if(SOLR_TRACK_QTIME) {
			$this->SolrQtime =& ClassRegistry::init('SolrQtime');
		}
		//create load balancing object
		$this->solrLoadBalancer = new Apache_Solr_Service_Balancer($solrServices);
	}
	
	/**
	* Main function to retrieve data from Lucene/Solr servers
	*
	* @var int	  search type; used to identify parser for results	
	* @var string core name
	* @var string user defined query
	* @access private 
	*/
	
	public function find($type 			= null,
						 $datasets 		= null,
						 $userQuery		= "*:*",
						 $facetQueries	= null,
						 $facetFields	= null,
						 $facetMinCount = -1, //return all facet counts
						 $facetPrefix	= null, 
						 $docFields		= null,
						 $docStart		= null, 
						 $docRows		= null,
						 $renameFacets	= false) {
			
		$this->validateFindArguments($type,$datasets,$userQuery,$facetQueries,$facetFields,$facetMinCount,$facetPrefix);
		
		$requests = array();
		
		foreach($datasets as $dataset) {
			switch($type) {				
				case 'docs' : // return paginated documents 
					$requests[$dataset] = $this->initRequest($type, $dataset, $userQuery);
					
					$requests[$dataset]['solr']['start'] = $docStart;
					$requests[$dataset]['solr']['rows']  = $docRows;
					$requests[$dataset]['solr']['fl']    = $docFields;
				break;	
				case 'docsAndFacet' : // return paginated documents with facet counts
					if($this->isWeighted($dataset)) {
						$requests[$dataset] = $this->initRequest('docsAndWeightedFacet', $dataset, $userQuery);
							
						$requests[$dataset]['solr']['start'] 		= $docStart;
						$requests[$dataset]['solr']['rows']  		= $docRows;
						$requests[$dataset]['solr']['fl']    		= $docFields;
						$requests[$dataset]['solr']['stats'] 		= true;
						$requests[$dataset]['solr']['stats.field']  = 'weight';;
						$requests[$dataset]['solr']['stats.facet']  = implode(',',$facetFields);												
					}
					else {
						$requests[$dataset] = $this->initRequest('docsAndUnweightedFacet', $dataset, $userQuery);
						
						$requests[$dataset]['solr']['facet'] 		 = true;																				
						$requests[$dataset]['solr']['facet.field']   = implode(',',$facetFields);
						$requests[$dataset]['solr']['facet.limit']   = $facetMinCount;							
						$requests[$dataset]['solr']['facet.prefix']  = $facetPrefix;	
					}					
					end;							
				case 'docCount' : // return dataset document count
					$requests[$dataset] = $this->initRequest('unweightedDocCount', $dataset, $userQuery);
				break;				
				case 'hitCount' : // return dataset document hits (for weighted datasets this is the sum of weights) 
					if($this->isWeighted($dataset)) {						
						$requests[$dataset] = $this->initRequest('weightedDocCount', $dataset, $userQuery);						
						$requests[$dataset]['solr']['stats'] 		= true;
						$requests[$dataset]['solr']['stats.field']  = 'weight';												
					}
					else {					
						$requests[$dataset] = $this->initRequest('unweigtedDocCount', $dataset, $userQuery);
					}					
				break;
				case 'facetQuery' :	// return dataset facet query counts
					if($this->isWeighted($dataset)) {
						$type = 'weightedFacetQuery'; // single dataset - facet search (weighted)
						
						// add request for foreach of the weighted facet queries
						foreach($facetQueries as $id => $facetQuery) {
							$userQuery = "$userQuery AND ($facetQuery)";
								
							$requests[$dataset.id] = $this->initRequest($type, $dataset, $userQuery);
						
							$requests[$dataset.id]['solr']['stats'] 	   = true;
							$requests[$dataset.id]['solr']['stats.field']  = 'weight';
							$requests[$dataset.id]['solr']['facet.prefix'] = $facetPrefix; //TODO test if works without eception
						}						
					}
					else{
						$type = 'unweightedFacetQuery';
							
						$requests[$dataset] = $this->initRequest($type, $dataset, $userQuery);
						
						$requests[$dataset]['solr']['facet'] 		 = true;
						$requests[$dataset]['solr']['facet.query']   = array_values($facetQueries);
						$requests[$dataset]['solr']['facet.limit']   = $facetMinCount;
						$requests[$dataset]['solr']['facet.prefix']  = $facetPrefix;						
					}
				break;	
				case 'facet' :	// return dataset facet counts
					if($this->isWeighted($dataset)) {					
						$type = 'weightedFacet'; // single dataset - facet search (weighted)
						$requests[$dataset] = $this->initRequest($type, $dataset, $userQuery);
						
						$requests[$dataset]['solr']['stats'] 		= true;
						$requests[$dataset]['solr']['stats.field']  = 'weight';
						$requests[$dataset]['solr']['stats.facet']  = implode(',',$facetFields);	
						$requests[$dataset]['solr']['facet.prefix'] = $facetPrefix; //TODO test if works without exception						
					}
					//handle unweighted datasets
					else {					
						$type = 'unweightedFacet';
						
						$requests[$dataset] = $this->initRequest($type, $dataset, $userQuery);
					
						$requests[$dataset]['solr']['facet'] 		 = true;																				
						$requests[$dataset]['solr']['facet.field']   = implode(',',$facetFields);
						$requests[$dataset]['solr']['facet.limit']   = $facetMinCount;							
						$requests[$dataset]['solr']['facet.prefix']  = $facetPrefix;							
					}		
				break;				
			}
		}
		// search reqiuests in parallel; uses reference to requests
		$this->MultiCurl->run($requests);
		
		if($renameFacets) {
			foreach ($requests as $requestId => $request) {   
				if(isset($request['result']['facet'])) {
					$this->addNamesToFacets($request['result']);
				}		
			}
		}
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
	
	/**
	* Checks if a dataset is weighted or not
	*
	* @var string  dataset name
	* @access private
	*/
		
	function isWeighted($dataset) {
		$this->Project =& ClassRegistry::init('Project');
		return $this->Project->isWeighted($dataset);
	}
	
	/**
	* Validates find arguments
	*
	* @access private
	*/	
	private function validateFindArguments($type,$dataset,$userQuery,$facetQueries,$facetField,$facetMinCount,$facetPrefix) {
		//check arguments
		if(! in_array($type,$this->findTypes)) {
			throw new Exception("Solr "._FUNCTION_." type is not supported.");
		}			
		if(!is_null($facetQueries)) {
			if(is_null($facetField)) {
				throw new Exception("Solr "._FUNCTION_." argument exception: facetField can not be empty if facetQueries are provided");
			}
		}	
		if(!is_null($facetMinCount)) {
			if(is_null($facetField)) {
				throw new Exception("Solr "._FUNCTION_." argument exception: facetField can not be empty if facetMinCount is provided");
			}
		}
		if(!is_null($facetPrefix)) {
			if(is_null($facetField)) {
				throw new Exception("Solr "._FUNCTION_." argument exception: facetField can not be empty if facetPrefix is provided");
			}
		}

		return	;
	}
	
	/**
	* Returns default request data structure
	*
	* @var string search type; used to identify result parser	
	* @var string core name
	* @var string user defined query
	* @access private 
	*/
		
	private function initRequest($type,$core,$userQuery) {
		return array(
							'type' => $type,
							'host' => $this->getHost(),
							'port' => SOLR_PORT,
							'core' => $core,
							'solr' => array(
											'q'				=> '*:*',
											'fq'   			=> $userQuery,
											'start' 		=> 0,
											'rows' 			=> 0,
											'wt' 			=> 'json',
											'json.nl' 		=> 'map')
		);		
	}
	
	/**
	* Returns solr host in a load-balanced fashion
	* 
	* @access private
	*/	
	
	private function getHost() {
		if(defined('SOLR_MASTER_HOST') && defined('SOLR_MASTER_HOST')) {
			$randomFlag  = mt_rand(0, 1);
			if($randomFlag == 0) {
				$host = SOLR_MASTER_HOST;
			}
			if($randomFlag == 1) {
				$host = SOLR_SLAVE_HOST;
			}
		}
		else {
			$host = SOLR_MASTER_HOST;
		}		
		
		return $host;		
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

	private function addNamesToFacets(&$result) {		
		foreach($result['facet'] as $facetId => $facets) {
			switch($facetId) {
				case 'ec_id':
					$this->addNamesToFacet($facets, $facetId, 'Enzymes');
				break;
				case 'go_id':
					$this->addNamesToFacet($facets, $facetId, 'GoTerm');
				break;
				case 'hmm_id':
					$this->addNamesToFacet($facets, $facetId, 'Hmm');
				break;
				case 'library_id':
					$this->addNamesToFacet($facets, $facetId, 'Library');
				break;									
				case 'ko_id':
					$this->addNamesToFacet($facets, $facetId, 'KeggOrtholog');
				break;
				case 'cluster_id':
					$this->addNamesToFacet($facets, $facetId, 'Cluster');
				break;				
			}
		}	
	}
		
	private function addNamesToFacet(&$facets,$facetField,$model) {
		$updatedFacets = array();
		$this->{$model} =& ClassRegistry::init($model);		
		foreach($facets[$facetField] as $facetId => $count) {			
			$updatedFacets[$facetId] = $this->{$model}->getDescription($facetId);
		}
		$facets[$facetField] = $updatedFacets;			
	}
}
?>