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

	var $uses = array();
	var $components = array('Parallelization');
	var $method = 'POST';
	
	
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
			
		#phpinfo();
			
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
	 * Searches Solr core. If a weighted population is supplied it executes a distributed search across
	 * population datasets and returns aggregated results.
	 *
	 * @param String $dataset dataset or dataset population
	 * @param String $query lucene query string http://lucene.apache.org/java/2_4_0/queryparsersyntax.html
	 * @param int $offset the starting offset for result documents
	 * @param int $limit the maximum number of result documents to return
	 * @param array $params key / value pairs for other query parameters (see Solr documentation), use arrays for parameter keys used more than once (e.g. facet.field)
	 * @param boolean $renameFacets if set to 1, facet names are added based on the facet IDs

	 * @return void
	 * @access public
	 * @throws Throws exception if an error occurs during the Solr service call
	 */

	public function search($dataset,$query, $offset = 0, $limit = NUM_SEARCH_RESULTS, $params = array(), $renameFacets=false){
		$this->Project =& ClassRegistry::init('Project');

		if($this->Project->isWeighted($dataset)) {
			if($this->Project->isPopulation($dataset)) {
				$this->Population =& ClassRegistry::init('Population');
				$datasets = $this->Population->getLibraries($dataset);
				return $this->weightedSearch($datasets,$query, $offset, $limit, $params, $renameFacets);
			}	
			else {
				return $this->weightedSearch($dataset,$query, $offset, $limit, $params, $renameFacets);
			}
		}
		else {
			return $this->unweightedSearch($dataset,$query, $offset, $limit, $params, $renameFacets);
		}
	}

	/**
	 * Returns document counts of a Solr core. If a weighted population is supplied it executes a distributed
	 * search across population datasets and returns aggregated hits.
	 *
	 * @param String $dataset Dataset/Core/Index name
	 * @return void
	 * @access private
	 * @throws throws exception if an error occurs during the Solr service call	 
	 */
	
	function count($dataset,$query="*:*",$params=null) {
		$this->Project =& ClassRegistry::init('Project');
		if($this->Project->isWeighted($dataset)) {			
			if($this->Project->isPopulation($dataset)) {
				$this->Population =& ClassRegistry::init('Population');
				$populationDatatsets = $this->Population->getLibraries($dataset);
				return $this->weightedCount($populationDatatsets,$query, $params);
			}
			else {
				return $this->weightedCount($dataset,$query,$params);
			}
		}
		else {
			return $this->unweightedCount($dataset,$query,$params);
		}
	}

	/**
	 * Searches multiple Solr cores using multiple queries. Supplied datsets can be a mixture of weighted or unweighted datasets 
	 * or populations. If a weighted population is supplied it executes a distributed search across population datasets and 
	 * returns aggregated results. If several weighted datasets are supplied it executes a distributed search across all of the
	 * datasets for each query using a group by library id to retrieve individual results.
	 *
	 * @param String $counts reference to count array which serves as the result set (come prep-poplutes with category information).
	 * @param String $datasets array of datasets. Can be a mixture of weighted or unweighted datasets or populations.
 	 * @param String $queries array of Lucene queries.
	 * @param String $filterQuery user provided query to filter categories.
	 * @param int 	 $minCount minimum category count provided by the user.
 	 * @param Array  $query2CategoryMapping mapps between query and category IDs

	 * @return void populates the $counts reference variable.
	 * @access public
	 * @throws Throws exception if an error occurs during the Solr service call
	 */
		
	function multiSearch(&$counts,$datasets,$queries,$filterQuery,$minCount,$query2CategoryMapping = null) {
		$this->Project =& ClassRegistry::init('Project');
	
		//init arrays of dataset types
		$unweightedDatasets 	= array();
		$weightedDatasets 		= array();
		$weightedPopulations	= array();

		//group datasets by type
		foreach($datasets as $dataset) {			
			if($this->Project->isWeighted($dataset)) {
				if($this->Project->isPopulation($dataset)) {
					array_push($weightedPopulations,$dataset);
				}	
				else {		
					array_push($weightedDatasets,$dataset);
				}
			}		
			else {
				array_push($unweightedDatasets,$dataset);
			}
		}

		//foreach dataset type execute specific searches
//		if(count($weightedPopulations) > 0) {
//			$this->Population =& ClassRegistry::init('Population');
//			foreach($weightedPopulations as $weightedPopulation) {
//				
//				$weightedPopulationDatatsets = $this->Population->getLibraries($weightedPopulation);
//				foreach($queries as $query) {
//					$split = explode(":", $query,2);
//					$category = $split[1];	
//					if($enzymeQueryFlag) {
//						$category = str_replace('*','-',$split[1]);
//					}
//					try {
//						$result = $this->weightedDistributedSearch($weightedPopulationDatatsets,$filterQuery,$query);
//					}
//					catch (Exception $e) {
//						throw new Exception($e);
//					}
//					$counts[$category][$weightedPopulation] = $result['sum'];
//					$counts[$category]['sum'] +=  $result['sum'];
//				}
//			}
//		}
		if(count($weightedPopulations) > 0) {
			$this->Population =& ClassRegistry::init('Population');
			foreach($weightedPopulations as $weightedPopulation) {
				$solrArguments = array(	"facet" 			=> "true",
								'facet.mincount' 	=> $minCount,
								'facet.query' 		=> $queries,
								"facet.limit" 		=> -1);	
				try {
					$facets = $this->weightedSearch($weightedPopulation,$filterQuery,0,0,$solrArguments);							
				}
				catch (Exception $e) {
					throw new Exception($e);
				}
				
				
				foreach($facets as $facetQuery =>$count) {
					
					if(is_null($query2CategoryMapping)) {
						$split = explode(":", $facetQuery,2);
						$category = $split[1];	
					}		
					else {
						$category = $query2CategoryMapping[$facetQuery];		
					}
																
					$counts[$category][$weightedPopulation] = $count;
					$counts[$category]['sum'] += $count;
				}		
			}
		}		
		if(count($weightedDatasets) > 0) {
			#$result = $this->testMultiCurlSearch($weightedDatasets,$queries,$filterQuery);
			
			foreach($queries as $query) {
				if(is_null($query2CategoryMapping)) {
					$split = explode(":", $query,2);
					$category = $split[1];	
				}		
				else {
				
					$category = $query2CategoryMapping[$query];		
				}				
				try {
					
					$result = $this->weightedDistributedSearch($weightedDatasets,$filterQuery,$query);
					//$result = $this->testMultiCurlSearch($weightedDatasets,$filterQuery,$query);
				}
				catch (Exception $e) {
					throw new Exception($e);
				}	
				$counts[$category]['sum'] = $result['sum'];	
				if(isset($result['datasets'])) {
					foreach($result['datasets'] as $weightedDataset =>$count) {
							$counts[$category][$weightedDataset] = $count;	
					}	
				}
				unset($result);			
			}
		}
		if(count($unweightedDatasets) > 0) {
			foreach($unweightedDatasets as $unweightedDataset) {
				$solrArguments = array(	"facet" 			=> "true",
										'facet.mincount' 	=> $minCount,
										'facet.query' 		=> $queries,
										"facet.limit" 		=> -1);	
				try {
					$result = $this->unweightedSearch($unweightedDataset,$filterQuery,0,0,$solrArguments);	
					#$result = $this-unweightedMultiCurlSearch($unweightedDatasets,$filterQuery,$query);
				}
				catch (Exception $e) {
					throw new Exception($e);
				}
				$facets = $result->facet_counts->facet_queries;
				unset($result);		
				foreach($facets as $facetQuery =>$count) {
					if(is_null($query2CategoryMapping)) {
						$split = explode(":", $facetQuery,2);
						$category = $split[1];	
					}		
					else {
						$category = $query2CategoryMapping[$facetQuery];		
					}											
					$counts[$category][$unweightedDataset] = $count;
					$counts[$category]['sum'] += $count;
				}							
			}					
		}
	}
	
	public function request_callback($response, $info, $request) {
	        // parse the page title out of the returned HTML
	        if (preg_match("~<title>(.*?)</title>~i", $response, $out)) {
	                $title = $out[1];
	        }
	        echo "<b>$title</b><br />";
	        debug($info);
	    debug($request);
	        echo "<hr>";
	}
	
	public function testMultiCurlSearch($datasets,$queries,$filterQuery=null) {
		
		$processes = array();
		#debug($datasets);
		foreach($queries as $query) {
			
			$countResults = array();
			$countResults['sum'] = 0;
			
			if(!is_null($filterQuery)) {
				if($query === '*:*' ) {
					$solrQuery = $filterQuery;
				}
				else {
					$solrQuery  = "($filterQuery) AND ($query)";
				}
			}
			$solrQuery  = 	$query;
			//$datasetChunks = array_chunk($datasets ,SOLR_NUM_MAX_WEIGHTED_SHARDS);
			
				
			
			
			$urls = $this->getSolrShardArgument($datasets);	
			
			//collect requests per chunk
			for($datasetCounter = 0; $datasetCounter<count($datasets);$datasetCounter++) {
				
		
				if(defined('SOLR_SLAVE_HOST')) {
					$randomFlag  = mt_rand(0, 1);
					if($randomFlag == 0) {
						$shardIp = SOLR_SLAVE_HOST;
					}
					if($randomFlag == 1) {
						$shardIp = SOLR_MASTER_HOST;
					}
				}
				else {
					$shardIp = SOLR_MASTER_HOST;
				}
				
				$dataset = 	$datasets[$datasetCounter];
							
				$processes[$dataset.$query]['url'] 			= "http://$shardIp".":".SOLR_PORT."/solr/$dataset/select";
				$processes[$dataset.$query]['dataset'] 		= $dataset;
				$processes[$dataset.$query]['query'] 		= $solrQuery;			
				$processes[$dataset.$query]['solrArguments']= array(
																'q'=>'*:*',
																'fq'=>$solrQuery,
																'stats'=>'true',
																'stats.field'=>'weight',
																'rows' => '0',
																'wt' => 'json',
																'json.nl' =>'map'
																);											
			
			}	
		}
//		debug(sizeof($queries));
//		debug(sizeof($datasets));
//		debug(sizeof($processes));
		//die();
		$this->Parallelization->execute($processes);
		return 	$countResults;		
	}
	

	/* Executes a a multi-curl distributed search across the specified datasets using the 
	 * respective query [client side distributed]
	 *
	 * @param String $datasets datasets
	 * @param String $query query
	 * @param String $filterQuery filter query
	 * 
	 * @return Array returns an array that contains counts for each datasets
	 * @access public
	 * @throws Throws exception if an error occurs during the Solr service call
	 */	
	
	public function weightedMultiCurlSearch($datasets,$query,$filterQuery=null) {
		$countResults = array();
		$countResults['sum'] = 0;
		
		if(!is_null($filterQuery)) {
			if($query === '*:*' ) {
				$filterQuery = $filterQuery;
			}
			else {
				$filterQuery  = "($filterQuery) AND ($query)";
			}
		}

		$datasetChunks = array_chunk($datasets ,SOLR_NUM_MAX_WEIGHTED_SHARDS);

		//loop through chunks of shards
		foreach($datasetChunks as $datasetChunk) {
			
			$curlRequests = array();//handle array
			
			$urls = $this->getSolrShardArgument($datasetChunk);
				
			$myurl = "";
			
			
			//collect requests per chunk
			for($datasetCounter = 0; $datasetCounter<count($datasetChunk);$datasetCounter++) {
				$h = curl_init();

				if(defined('SOLR_SLAVE_HOST')) {
					$randomFlag  = mt_rand(0, 1);
					if($randomFlag == 0) {
						$shardIp = SOLR_SLAVE_HOST;
					}
					if($randomFlag == 1) {
						$shardIp = SOLR_MASTER_HOST;
					}
				}
				else {
					$shardIp = SOLR_MASTER_HOST;
				}
							
				$dataset = 	$datasetChunk[$datasetCounter];
				
				//set culr options
				curl_setopt($h,CURLOPT_URL,"http://$shardIp".":".SOLR_PORT."/solr/$dataset/select");
				curl_setopt($h,CURLOPT_HEADER,false);
				curl_setopt($h,CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded; charset=UTF-8"));
				curl_setopt($h,CURLOPT_POSTFIELDS,"q=*:*&fq=$filterQuery&stats=true&stats.field=weight&rows=0&wt=json&json.nl=map");				
		 		curl_setopt($h,CURLOPT_RETURNTRANSFER,true);
		 		curl_setopt($h,CURLOPT_BINARYTRANSFER,true);
		 		curl_setopt($h,CURLOPT_POST, true);
		 		curl_setopt($h,CURLOPT_TIMEOUT, 0);
		 		
		 		$myurl= "http://$shardIp".":".SOLR_PORT."/solr/$dataset/select?q=*:*&fq=$filterQuery&stats=true&stats.field=weight&rows=0&wt=json&json.nl=map";
//		 		if($datasetCounter = 0) {
//		 			debug("http://$shardIp".":".SOLR_PORT."/solr/$dataset/select?q=*:*&fq=$filterQuery&stats=true&stats.field=weight&rows=0&wt=json&json.nl=map");
//		 		}
		 		$curlRequests[$dataset]=$h;		 		
			}	
				
			//start multi-search
			$mh = curl_multi_init();
			foreach($curlRequests as $dataset => $h) curl_multi_add_handle($mh,$h);
			$running = null;
			
			try {
				do{
					set_time_limit (0);
					curl_multi_exec($mh,$running);
				}while($running > 0);
			}
			catch (Exception $e) {
				debug($myurl);
				debug($e->getTrace());
				#throw new Exception($e);
			}
			
			// get the result and save it in the result ARRAY
			foreach($curlRequests as $dataset => $h){
				$responseBody = curl_multi_getcontent($h);
				$statusCode   = curl_getinfo($h, CURLINFO_HTTP_CODE);
				$contentType  = curl_getinfo($h, CURLINFO_CONTENT_TYPE);
				$httpResponse = new Apache_Solr_HttpTransport_Response($statusCode, $contentType, $responseBody);
				$solrResponse = new Apache_Solr_Response($httpResponse,false, true);

				try {
				#$data = json_decode(curl_multi_getcontent($h));
					if(isset($solrResponse->stats->stats_fields->weight->sum)) {
						$numHits =  round((double) $solrResponse->stats->stats_fields->weight->sum,WEIGHTED_COUNT_PRECISION);
					}
					else {
						$numHits = 0;
					}
				}
				catch (Exception $e) {
					debug($myurl);
					debug($e->getTrace());
				}
				
				$countResults['sum'] += $numHits;
				$countResults['datasets'][$dataset] = $numHits;

				curl_multi_remove_handle($mh,$h);
			}
			curl_multi_close($mh); 
		}	
		return 	$countResults;
	}	

	/* Executes a a multi-curl distributed search across the specified datasets using the 
	 * respective query [client side distributed]
	 *
	 * @param String $datasets datasets
	 * @param String $query query
	 * @param String $filterQuery filter query
	 * 
	 * @return Array returns an array that contains counts for each datasets
	 * @access public
	 * @throws Throws exception if an error occurs during the Solr service call
	 */	
	
	public function unweightedMultiCurlSearch($datasets,$query,$filterQuery=null) {
		$countResults = array();
		$countResults['sum'] = 0;
		
		if(!is_null($filterQuery)) {
			if($query === '*:*' ) {
				$filterQuery = $filterQuery;
			}
			else {
				$filterQuery  = "($filterQuery) AND ($query)";
			}
		}

			
		$curlRequests = array();//handle array
			
			
		$myurl = "";
		//collect requests per chunk
		foreach($datasets as $dataset) {
			$h = curl_init();

			if(defined('SOLR_SLAVE_HOST')) {
				$randomFlag  = mt_rand(0, 1);
				if($randomFlag == 0) {
					$solrHostIp = SOLR_SLAVE_HOST;
				}
				if($randomFlag == 1) {
					$solrHostIp = SOLR_MASTER_HOST;
				}
			}
			else {
				$solrHostIp = SOLR_MASTER_HOST;
			}
			
			//set culr options
			curl_setopt($h,CURLOPT_URL,"http://$shardIp".":".SOLR_PORT."/solr/$dataset/select");
			curl_setopt($h,CURLOPT_HEADER,false);
			curl_setopt($h,CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded; charset=UTF-8"));
			curl_setopt($h,CURLOPT_POSTFIELDS,"q=*:*&fq=$filterQuery&rows=0&wt=json&json.nl=map");				
	 		curl_setopt($h,CURLOPT_RETURNTRANSFER,true);
	 		curl_setopt($h,CURLOPT_BINARYTRANSFER,true);
	 		curl_setopt($h,CURLOPT_POST, true);
	 		curl_setopt($h,CURLOPT_TIMEOUT, 0);
	 		
	 		$curlRequests[$dataset]=$h;		 		
		}	
				
		//start multi-search
		$mh = curl_multi_init();
		foreach($curlRequests as $dataset => $h) curl_multi_add_handle($mh,$h);
		$running = null;
			
		try {
			do{
				set_time_limit (0);
				curl_multi_exec($mh,$running);
			}while($running > 0);
		}
		catch (Exception $e) {
			debug($myurl);
			debug($e->getTrace());
			#throw new Exception($e);
		}
			
		// get the result and save it in the result ARRAY
		foreach($curlRequests as $dataset => $h){
			$responseBody = curl_multi_getcontent($h);
			$statusCode   = curl_getinfo($h, CURLINFO_HTTP_CODE);
			$contentType  = curl_getinfo($h, CURLINFO_CONTENT_TYPE);
			$httpResponse = new Apache_Solr_HttpTransport_Response($statusCode, $contentType, $responseBody);
			$solrResponse = new Apache_Solr_Response($httpResponse,false, true);

			try {
			#$data = json_decode(curl_multi_getcontent($h));
				if(isset($solrResponse->stats->stats_fields->weight->sum)) {
					$numHits =  round((double) $solrResponse->response->numFound);
				}
				else {
					$numHits = 0;
				}
			}
			catch (Exception $e) {
				debug($myurl);
				debug($e->getTrace());
			}
			
			$countResults['sum'] += $numHits;
			$countResults['datasets'][$dataset] = $numHits;

			curl_multi_remove_handle($mh,$h);
		}
		curl_multi_close($mh); 
		
		return 	$countResults;
	}	
	
	/**
	 * Returns the number of documents for an index. If a weighted population is supplied it executes a distributed search across
	 * population datasets and returns the number of overall documents.
	 *
	 * @param String $dataset dataset
	 * @param String $query query
	 * 
	 * @return int document count
	 * @access public
	 * @throws Throws exception if an error occurs during the Solr service call
	 */
	
	public function documentCount($dataset,$query="*:*") {
		try {
			$result = $this->unweightedSearch($dataset,$query,0,0,null,false);
		}
		catch (Exception $e) {
			throw new Exception($e);
		}
		return (int) $result->response->numFound;
	}
		
	public function weightedDistributedSearch($datasets,$query,$filterQuery=null) {

		$params 		   	 = array();
		$shardedCountResults = array();
		$shardedCountResults['sum'] = 0;
			
		//set filter query
		if(!is_null($filterQuery)) {
			if($query === '*:*' ) {
				$params['fq'] = $filterQuery;
			}
			else {
				$params['fq'] = $query." AND ($filterQuery)";
			}
		}

		$params['stats'] 	   = 'true';
		$params['stats.field'] = 'weight';
		$params['stats.facet'] = 'library_id';

		$shardChunks = array_chunk($datasets ,SOLR_NUM_MAX_WEIGHTED_SHARDS);

		//loop through chunks of shards
		foreach($shardChunks as $datasets) {
			
			//get shard argument
			$params['shards'] = $this->getSolrShardArgument($datasets);

			if(SOLR_TRACK_QTIME) {
				$start = $this->time();
			}
						
			try {
				$solrResult = $this->solrLoadBalancer->search("/solr/{$datasets[0]}",'*:*', 0,0,$params, $this->method);
			}
			catch (Exception $e) {
				throw new Exception($e);
			}
			
			if(SOLR_TRACK_QTIME) {
				$this->trackQtime(__FUNCTION__,$start,$solrResult,$query,$datasets[0]);	
			}			
			
			if(isset($solrResult->stats->stats_fields->weight->facets->library_id)) {
				foreach($datasets as $dataset) {
					if(isset($solrResult->stats->stats_fields->weight->facets->library_id->$dataset)) {
						$shardedCountResults['datasets'][$dataset] = round($solrResult->stats->stats_fields->weight->facets->library_id->$dataset->sum,WEIGHTED_COUNT_PRECISION);
					}
					else{
						$shardedCountResults['datasets'][$dataset] = 0;
					}
				}
			}
			else {
				foreach($datasets as $dataset) {
					$shardedCountResults['datasets'][$dataset] = 0;
				}
			}
				
			if(isset($solrResult->stats->stats_fields->weight->sum)) {
				$shardedCountResults['sum'] +=  round($solrResult->stats->stats_fields->weight->sum,WEIGHTED_COUNT_PRECISION);
			}
			else {
				if(!isset($shardedCountResults['sum'])) {
					$shardedCountResults['sum'] = 0;
				}
			}
				
			unset($solrResult);
		}

		return $shardedCountResults;
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
			//create master service
			$transportInstance = new Apache_Solr_HttpTransport_CurlNoReuse();

			$service = new Apache_Solr_Service(SOLR_MASTER_HOST,SOLR_PORT,"/solr/$dataset",
			$transportInstance);
				
			$service->delete($removeIndexCommand);
				
			//if master/slave configuration sleep to allow slave to synchronize
			if(defined(SOLR_SLAVE_HOST)) {
				sleep(100);
			}
				
			$this->unloadCore($dataset);
		}
		catch(Exception $e){
			throw new Exception($e);
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
	 * Merges Solr indices to create a population dataset
	 *
	 **@param Integer	$projectId 	project ID of the new population dataset
	 * @param String	$core 		name of new index file/core after merging
	 * @param Array 	$datasets 	datasets to be merged
	 * @return void
	 * @access private
	 */
	public function mergeIndex($projectId,$core,$datasets) {
			
		//define the root URL for the merge that points to the new core
		$mergeUrl = $this->getSolrUrl(SOLR_MASTER_HOST,SOLR_PORT)."/solr/admin/cores?action=mergeindexes&core=$core";
		
		//add index information for cores that are going to be merged
		foreach($datasets as $dataset) {
			$mergeUrl .= "&indexDir=".SOLR_DATA_DIR."/$projectId/$dataset/index";
			
			try {
				//execute a commit and optimize before the merge
				$this->commitAndOptimize($dataset);
			}
			catch (Exception $e) {
				throw new Exception($e);
			}				
		}	
		try {
			//delete core; ignore exception if exists
			$this->deleteIndex($core);
		}
		catch (Exception $e) {
			
		}			
				
		try {	
			//create new merge core				
			$this->log("Create Core: $projectId,$core");
			$this->createCore($projectId,$core);			
			$this->log("Execute Merge Url: $mergeUrl");
			$this->executeUrl($mergeUrl,3600);
			$this->commitAndOptimize($core);
			$this->log("Commit & Optimize Core: $core");
			//sleep 80s to synchronize slave and master cores
			sleep(80);						
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
	public function executeUrl($url,$timeout = 6000) {

		//write request to log file
		$this->log("solr request: $url",LOG_DEBUG);

		try {
			$service = new Apache_Solr_Service();
				
			$response = $service->_sendRawGet($url,$timeout);

			$response = serialize($response);
			//write response to log file
			$this->log("solr response: $response",LOG_DEBUG);
		}
		catch (Exception $e) {
			throw new Exception($e);
		}
	}

	public function fetch($dataset,$query,$fields,$offset,$limit) {

		$params['fl'] = $fields;

		if(SOLR_TRACK_QTIME) {
			$start = $this->time();
		}		
		
		try {
			$result = $this->solrLoadBalancer->search("/solr/$dataset",$query, $offset,$limit,$params,$this->method);
				
		}
		catch (Exception $e) {
			//rethrow exception
			throw new Exception($e);
		}
		
		if(SOLR_TRACK_QTIME) {
			$this->trackQtime(__FUNCTION__,$start,$result,$query,$dataset);	
		}		
		
		//if documents are being returned
		$docs = $result->response->docs;
		$this->removeUnassignedValues($docs);

		return $docs;
	}

	/**
	 * Searches dataset using an unweighted search (without setting the Solr StatsComponent argument).
	 *
	 * @param String $dataset dataset or dataset population
	 * @param String $query lucene query string http://lucene.apache.org/java/2_4_0/queryparsersyntax.html
	 * @param int $offset the starting offset for result documents
	 * @param int $limit the maximum number of result documents to return
	 * @param array $params key / value pairs for other query parameters (see Solr documentation), use arrays for parameter keys used more than once (e.g. facet.field)
	 * @param boolean $renameFacets if set to 1, facet names are added based on the facet IDs

	 * @return void
	 * @access public
	 * @throws Throws exception if an error occurs during the Solr service call
	 */
		
	private function unweightedSearch($dataset,$query, $offset, $limit, $params, $renameFacets = false){	
		
		if(SOLR_TRACK_QTIME) {
			$start = $this->time();
		}
			
		try {	
			$result = $this->solrLoadBalancer->search("/solr/$dataset",$query, $offset,$limit,$params,$this->method);				
		}
		catch (Exception $e) {				
			//rethrow exception
			throw new Exception($e);
		}	
		
		if(SOLR_TRACK_QTIME) {
			#debug(__FUNCTION__.",$start,$query,$dataset");
			$this->trackQtime(__FUNCTION__,$start,$result,$query,$dataset);	
		}
		
		//if documents are being returned
		if($limit > 0) {
			$hits = $result->response->docs;		
			$this->removeUnassignedValues($hits);
		}
		//rename facets if flag has been set
		if($renameFacets) {
			$this->renameFacets($result);	
		}				
		return $result;
	} 	
	
	/**
	 * Returns found hits for a dataset using an unweighted search (without setting the Solr StatsComponent argument).
	 *
	 * @param  String 	$dataset dataset 
	 * @return int 		$numHits the number of hits found
	 * @access private
	 * 
	 * @throws throws exception if an error occurs during the Solr service call
	 */
	
	private function unweightedCount($dataset,$query,$params) {
		
		if(SOLR_TRACK_QTIME) {
			$start = $this->time();
		}
		
		try {
			$result = $this->solrLoadBalancer->search("/solr/$dataset",$query, 0,0,$params,$this->method);			
		}
		catch (Exception $e) {
			throw new Exception($e);
		}

		if(SOLR_TRACK_QTIME) {
			$this->trackQtime(__FUNCTION__,$start,$result,$query,$dataset);	
		}		
		
		$numHits = (int) $result->response->numFound;	
		unset($result);
		return $numHits;
	}	

	/**
	 * Searches Solr cores using a weighted search by setting the Solr StatsComponent argument.
	 * If a populations is supplied it executes a distributes search across
	 * population datasets and returns aggregated results.
	 *
	 * @param String $dataset dataset or dataset population
	 * @param String $query lucene query string http://lucene.apache.org/java/2_4_0/queryparsersyntax.html
	 * @param int $offset the starting offset for result documents
	 * @param int $limit the maximum number of result documents to return
	 * @param array $params key / value pairs for other query parameters (see Solr documentation), use arrays for parameter keys used more than once (e.g. facet.field)
	 * @param boolean $renameFacets if set to 1, facet names are added based on the facet IDs

	 * @return void
	 * @access public
	 * @throws Throws exception if an error occurs during the Solr service call
	 */	
	
	private function weightedSearch($datasets,$query, $offset, $limit, $params, $renameFacets = false){
	
		//transform facet queries into queries that are executed sequentially
		if(isset($params['facet.query'])) {
			$facetQueries = $params['facet.query'];

			unset($params['facet']);
			unset($params['facet.mincount']);
			unset($params['facet.query']);

			$facetQueryResults = array();
				
			foreach($facetQueries as $facetQuery) {
				$params['fq'] = $facetQuery;
				if(count($datasets) == 1) {
					$facetQueryResults[$facetQuery] = $this->weightedCount($datasets,$query,$params);									
				}
				else if(count($datasets) > 1) {
					$result = $this->weightedDistributedSearch($datasets,$query,$facetQuery);
					$facetQueryResults[$facetQuery] = $result['sum'];
				}
			}
				
			return $facetQueryResults;
		}
		
		//transform facets into stats facets by retrieving all facets and returning
		//the top results of the sorted set 
		else if(isset($params['facet'])) {
			$numFound = 0;
				
			$facetFields = $params['facet.field'];
			$facetLimit  = $params['facet.limit'];
			
			$facetPrefix = null;
			if(isset($params['facet.prefix'])) {
				$facetPrefix = $params['facet.prefix'];
			}
			
			unset($params['facet.limit']);
			unset($params['facet.field']);

			##specify arguments
			$params['stats'] 	   = 'true';
			$params['stats.field'] = 'weight';
			$params['stats.facet'] =  $facetFields;
				
			if(count($datasets) == 1) {
				
				if(SOLR_TRACK_QTIME) {
					$start = $this->time();
				}					
				
				try {
					$result = $this->solrLoadBalancer->search("/solr/$datasets",$query, $offset,$limit,$params,$this->method);
				}
				catch (Exception $e) {
					//rethrow exception
					throw new Exception($e);
				}
				
				if(SOLR_TRACK_QTIME) {
					$this->trackQtime(__FUNCTION__,$start,$result,$query,$datasets);	
				}					
			}
			//execute distributed search for multiple datasets
			else if(count($datasets) > 1) {
				$aggregatedFacetCounts = null;
				$shardChunks = array_chunk($datasets ,SOLR_NUM_MAX_WEIGHTED_SHARDS);
				$results = array();
				foreach($shardChunks as $datasets) {
					$params['shards'] = $this->getSolrShardArgument($datasets);

					if(SOLR_TRACK_QTIME) {
						$start = $this->time();
					}						
					
					try {		
						$shardResult = $this->solrLoadBalancer->search("/solr/$datasets[0]",$query, $offset,$limit,$params,$this->method);
					}
					catch (Exception $e) {				
						//rethrow exception
						throw new Exception($e);
					}

					if(SOLR_TRACK_QTIME) {
						$this->trackQtime(__FUNCTION__,$start,$shardResult,$query,$datasets[0]);	
					}						
					
					array_push($results,$shardResult);
				}
				$result = $this->mergeWeightedFacetShardResults($facetFields,$results);
			}
				
			//adjust the the number of found hits by the sum of weights
			if(!is_null($result->stats->stats_fields->weight)) {
				$numFound = round((double) $result->stats->stats_fields->weight->sum,WEIGHTED_COUNT_PRECISION);
			}
				
			$result->response->numFound = $numFound;

			//if documents are being returned
			if($limit > 0) {
				$hits = $result->response->docs;
				$this->removeUnassignedValues($hits);
			}

			//if facets are provided do facet weighting
			if(isset($params['stats.facet'])) {
				$this->weightFacets($datasets,$query,$result,$facetFields,$facetLimit,$facetPrefix);
			}

			if($renameFacets) {
				$this->renameFacets($result);
			}
				
			return $result;
		}
	}	
	
	/**
	 * Returns weighted number of hits for a single dataset
	 * or the sum of hits if multiple datasets are provided as
	 * input.
	 *
	 * @param String $datasets dataset(s)
	 * @return int $numHits the number of hits found
	 * @access private
	 */
	
	private function weightedCount($datasets,$query,$params) {
		
		$numHits = 0;

		// specify stats arguments
		$params['stats'] 	   = 'true';
		$params['stats.field'] = 'weight';

		// execute search for single dataset
		if(count($datasets) == 1) {
			
			if(SOLR_TRACK_QTIME) {
				$start = $this->time();
			}					
			
			try {
				$result = $this->solrLoadBalancer->search("/solr/$datasets",$query, 0,0,$params, $this->method);
			}
			catch (Exception $e) {
				//rethrow exception
				throw new Exception($e);
			}
			
			if(SOLR_TRACK_QTIME) {
				$this->trackQtime(__FUNCTION__,$start,$result,$query,$datsets);	
			}
							
			//get the number of weighted hits
			if(!is_null($result->stats->stats_fields->weight)) {
				$numHits =  round((double) $result->stats->stats_fields->weight->sum,WEIGHTED_COUNT_PRECISION);
			}
			unset($result);
		}
		// execute distributed search for multiple weighted datasets
		else if(count($datasets) > 1) {
			$aggregatedCounts = null;
			$shardChunks = array_chunk($datasets ,SOLR_NUM_MAX_WEIGHTED_SHARDS);
			$results = array();
			foreach($shardChunks as $datasets) {
				$params['shards'] = $this->getSolrShardArgument($datasets);
				
				if(SOLR_TRACK_QTIME) {
					$start = $this->time();
				}					
				
				try {
					$result = $this->solrLoadBalancer->search("/solr/$datasets[0]",$query, 0,0,$params,$this->method);
				}
				catch (Exception $e) {
					//rethrow exception
					throw new Exception($e);
				}	
				
				if(SOLR_TRACK_QTIME) {
					$this->trackQtime(__FUNCTION__,$start,$result,$query,$datasets[0]);	
				}								
				
				if(!is_null($result->stats->stats_fields->weight)) {
					$numHits +=  round((double) $result->stats->stats_fields->weight->sum,WEIGHTED_COUNT_PRECISION);
				}
				unset($result);
			}
		}
		return $numHits;
	}
		
	private function renameFacets(&$result) {
		$facets = $result->facet_counts;

		if(!empty($facets->facet_fields->ec_id)) {
			$this->addEnzymeDescriptions($facets);
		}
		if(!empty($facets->facet_fields->go_id)) {
			$this->addGeneOntologyDescriptions($facets);
		}
		if(isset($facets->facet_fields->hmm_id)) {
			$this->addHmmDescriptions($facets);
		}
		if(!empty($facets->facet_fields->library_id)) {
			$this->addLibraryDescriptions($facets);
		}
		if(!empty($facets->facet_fields->ko_id)) {
			$this->addKeggOrthologDescriptions($facets);
		}
		if(JCVI_INSTALLATION && !empty($facets->facet_fields->cluster_id)) {
			$this->addClusterDescriptions($facets);
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
	 * Commits and optimizes newly created Solr index
	 *
	 * @param String $dataset Dataset name that equals the core name to identify the index to optimize/commit
	 * @return void
	 * @access private
	 */
	private function commitAndOptimize($dataset) {

		$transportInstance = new Apache_Solr_HttpTransport_CurlNoReuse();

		$service = new Apache_Solr_Service(SOLR_MASTER_HOST,SOLR_PORT,"/solr/$dataset",
		$transportInstance);

		try {
			//commit and optimize
			$service->commit(true);
			#$service->optimize();
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
	 * Replaces Solr default values with empty string
	 **/
	private function removeUnassignedValues(&$hits) {
		foreach($hits as $hit) {
			##$hit->peptide_id = str_replace('JCVI_PEP_metagenomic.orf.','',$hit->peptide_id);
			$hit->com_name =  str_replace('unassig$resultned','',$hit->com_name);
			$hit->com_name_src =  str_replace('unassigned','',$hit->com_name_src);
			$hit->go_id =  str_replace('unassigned','',$hit->go_id);
			$hit->go_src =  str_replace('unassigned','',$hit->go_src);
			$hit->ec_id =  str_replace('unassigned','',$hit->ec_id);
			$hit->ec_src =  str_replace('unassigned','',$hit->ec_src);
			$hit->blast_species =  str_replace('unassigned','',$hit->blast_species);
			$hit->blast_evalue =  str_replace('unassigned','',$hit->blast_evalue);
			$hit->hmm_id =  str_replace('unassigned','',$hit->hmm_id);
			$hit->ko_id =  str_replace('unassigned','',$hit->ko_id);
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
			//get library description
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
	 * Mapps cluster descriptions to cluster IDs returned by Solr
	 **/
	private function addClusterDescriptions(&$facets) {
		$this->Cluster =& ClassRegistry::init('Cluster');

		$clusterHash = array();
		foreach($facets->facet_fields->cluster_id as $acc => $count) {
			//get cluster description
			$description = $this->Cluster->getDescription($acc);
				
			//			if(!empty($description)) {
			//				//concatinate accession with library description
			//				$acc = $acc." | ".$description;
			//			}
				
			$clusterHash[$description]= $count;
		}
		$facets->facet_fields->cluster_id = $clusterHash;
	}

	/**
	 * Mapps Kegg Ortholog descriptions to KO IDs returned by Solr
	 **/
	private function addKeggOrthologDescriptions(&$facets) {
		$this->KeggOrtholog =& ClassRegistry::init('KeggOrtholog');

		$keggOrthologHash = array();
		foreach($facets->facet_fields->ko_id as $acc => $count) {
			//get cluster description
			$result = $this->KeggOrtholog->findByKoId($acc);
				
			$description = $result['KeggOrtholog']['name'];
			$acc = $acc." | ".$description;
				
			$keggOrthologHash[$acc]= $count;
		}

		$facets->facet_fields->ko_id = $keggOrthologHash;
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

	/**
	 * Adjusts the facet counts using individual weights
	 * returned by the solr stats component
	 *
	 * @param string $result solr result set
	 * @param string $facetFields facwt fields
 	 * @param string $limit the top number of facets to return
	 * @return string $facetPrefix filter for facets that start with the prefix
	 */
	private function weightFacets($dataset,$query,$result,$facetFields,$limit=-1,$facetPrefix = null) {

		if(!is_array($facetFields)) {
			$facetFields = array($facetFields);
		}
		foreach($facetFields as $facetField) {
			$sortedfacets = array();

			if(isset($result->stats->stats_fields->weight->facets->$facetField)) {
				foreach($result->stats->stats_fields->weight->facets->$facetField as $acc => $stats) {
					
					## filter based on facet prefix
					if(!is_null($facetPrefix)) {
						if(! preg_match("/^$facetPrefix.*/",$acc)) {
							continue;
						}
					}
					$sortedFacets[$acc] = round($stats->sum,WEIGHTED_COUNT_PRECISION);
				}

				if(isset($sortedFacets)) {
					arsort($sortedFacets);

					if($limit > 0) {
						$sortedFacets = array_slice($sortedFacets, 0, $limit);
					}

						
					$result->facet_counts->facet_fields->$facetField = $sortedFacets;
					unset($sortedFacets);
				}
			}
		}
		return $result;
	}

	/**
	 * Merges facets and generates sum of weights for multiple
	 * disributed Solr search results.
	 *
	 * @param string $facetFields the facet fields to merge
	 * @return string $results reference of array of Solr result sets
	 */
	private function mergeWeightedFacetShardResults($facetFields,&$results) {

		//init merged result using the first result from the result set
		$mergedResult 		= $results[0];
		$mergedFacets 		= $mergedResult->stats->stats_fields->weight->facets;
		$mergedSumWeights 	= (double) $mergedResult->stats->stats_fields->weight->sum;
			
		for($x=1; $x<count($results);$x++) {
				
			$result = $results[$x];
				
			//loop facets
			foreach($facetFields as $facetField) {
				//check if facet results exist for the specific facet
				if(isset($result->stats->stats_fields->weight->facets->$facetField)) {
					//loop through facet results
					foreach($result->stats->stats_fields->weight->facets->$facetField as $acc => $stats) {
						//increment facet weights if already part of merged results
							
						if(isset($mergedFacets->$facetField->$acc)) {
							$mergedFacets->$facetField->$acc->sum += $stats->sum;
						}
						//add additional facets weights if not yet part of merged results
						else {
							$mergedFacets->$facetField->$acc->sum = $stats->sum;
						}


					}
				}
			}
			//sum up weight sums
			if(isset($result->stats->stats_fields->weight->sum)) {
				$mergedSumWeights += (double) $result->stats->stats_fields->weight->sum;
			}

			unset($result);
		}
		unset($results);

		//reset object fields
		$mergedResult->stats->stats_fields->weight->facets = $mergedFacets;
		$mergedResult->stats->stats_fields->weight->sum = $mergedSumWeights;

		return $mergedResult;
	}
	
	/**
	 * Returns a concateninated list of shards that can
	 * be used for the Solr shard argument
	 *
	 * @param array $datasets array of datasets
	 */
	private function getSolrShardArgument($datasets) {

		$shardIps = array();
			
		if(defined('SOLR_SLAVE_HOST')) {
			$randomFlag  = mt_rand(0, 1);
			if($randomFlag == 0) {
				$shardIp = SOLR_SLAVE_HOST;
			}
			if($randomFlag == 1) {
				$shardIp = SOLR_MASTER_HOST;
			}
		}
		else {
			$shardIp = SOLR_MASTER_HOST;
		}
		
		foreach ($datasets as $dataset) {
			array_push($shardIps,$shardIp.":".SOLR_PORT."/solr/$dataset");
		}
		
		return implode(',',$shardIps);
	}
	
	private function trackQtime($action,$start,$result,$query,$dataset) {	
		$entry = array();
		$entry['SolrQtime']['wtime_ms']   		= round(($this->time() - $start)*1000,0);
		$entry['SolrQtime']['qtime_ms']   		= $result->responseHeader->QTime;
		$entry['SolrQtime']['action'] 	  		= $action;
		$entry['SolrQtime']['http_status'] 		= $result->getHttpStatus();	
		$entry['SolrQtime']['url']  	  		= $result->url;
		$entry['SolrQtime']['query']  	  		= $query;
		$entry['SolrQtime']['dataset']  	  	= $dataset;
		
		if(isset($result->responseHeader->params->facet)) {
			$entry['SolrQtime']['facet_flag']  	  	= ($result->responseHeader->params->facet === 'true') ?1:0;
			$entry['SolrQtime']['facet_query_flag'] = (preg_match('/facet\.query/',$result->url)) ?1:0;
		}		
		if(isset($result->responseHeader->params->stats)) {
			$entry['SolrQtime']['stats_flag']  	  	= ($result->responseHeader->params->stats === 'true') ?1:0;
			$entry['SolrQtime']['stats_facet_flag'] = (preg_match('/stats\.facet/',$result->url)) ?1:0;
		}
		
		$entry['SolrQtime']['host_ip']  	  	= $result->host;
		$entry['SolrQtime']['http_transport'] 	= $result->transport;
		
		
		if(! $this->SolrQtime->save($entry)) {
			debug('failed saving solr_qtime entry!');
		}
	}
}
?>