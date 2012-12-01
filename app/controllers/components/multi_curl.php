<?php
/***********************************************************
* File: gradient.php
* Handles interactions with the KEGG URL based API.
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

class MulitCurlComponent extends Object {

	var $components  = array('SolrParser');
	
	var $curlOptions = array(
							CURLOPT_HEADER 			=> false, 
							CURLOPT_HTTPHEADER 		=> array("Content-Type: application/x-www-form-urlencoded; charset=UTF-8"),
							CURLOPT_TIMEOUT 		=> 0,
							CURLOPT_RETURNTRANSFER  => true
						);
	
	var $numParallelRequests = 60;							
		
	/**
	* Creates multi_curl session using an array of requests
	* and processes a speciefied number requests in parallel
	* 
	* @var reference array contains request data structure
	* @var int the number of requests to process in parallel
	* @access public
	*/
		
	public function run(&$requests, $numParallelRequests = null) {
		if(is_null($curlOptions)) {
			$curlOptions = $this->curlOptions;
		} 
		if(is_null($numParallelProcesses)) {
			$numParallelRequests = $this->numParallelRequests;
		}
		if(sizeof($requests) < $numParallelRequests) {
			$numParallelRequests = sizeof($requests);
		}

		// create multi curl session (one per method invocation)
        $multiCurlSession = curl_multi_init();
        $runningStack = array();
        $isMultiCurlRunning = 0;

        // init waiting stack; holds all outstanding processes
        $waitingStack = array(); 
        foreach ($requests as $requestId => $request) {    	
        	$waitingStack[$requestId] = null;
        }        

        // init process stack equal to the size of max. parallel processes
        // holds all processes tat are currently being processed 
        $requestIds = array_keys($requests);
        for($i =0; $i < $numParallelProcesses; $i++){
        	$requestId = $requestIds[$i];
            $this->addCurlProcess($request[$requestId],$processId,$multiCurlSession,$curlOptions,$runningStack,$waitingStack);
        } 
             
        do {
       		while (($curlMultiExecOut = curl_multi_exec($multiCurlSession,  $isMultiCurlRunning)) == CURLM_CALL_MULTI_PERFORM) ;
        	
       		// handle errors
       		if ($curlMultiExecOut != CURLM_OK) {
       			//debug('curl_multi_exec did not return CURLM_OK');
       			throw new Exception('Curl_multi_exec did not return CURLM_OK. Error executing one multi-curl request.');
       		}
            
            // find process that completed or failed
            while ($curlMultiInfoOut = curl_multi_info_read($multiCurlSession)) {
				//debug($curlMultiInfoOut);
            	$curlHandle = $curlMultiInfoOut['handle'];
            	
                // get information from curl handle
                $info = curl_getinfo($curlHandle);
           	
                //get Lucene/Solr results from curl handle
                $rawResponse = curl_multi_getcontent($curlHandle); 

                if(empty($result)) {
                	throw new Exception('Curl_multi_exec returned empty Lucence/Solr result set.');
                }
                
                // get resource ID from the curl handle
                $resourceId = str_replace('Resource id #','', (string) $curlHandle);                
                
                // parse results
                $requestId = $runningStack[$resourceId];                
          		$request   = $requests[$requestId];
          		
          		$phpReponseObject = $this->SolrParser->jason2php($rawResponse);
                #$requests[$requestId]['result'] = $this->SolrParser->{'parse' . ucfirst($request['type'])}($phpReponseObject,&$request);
                $requests[$requestId]['result'] = $this->SolrParser->parse(&$request);
                                
                // remove resource from the processing stack
                unset($runningStack[$resourceId]);
                
                // remove handle from multi curl session
                curl_multi_remove_handle($multiCurlSession, $curlHandle);
               
                if(count($waitingStack) > 0) {                	
					// add next request
                	$processId = array_shift(array_keys($waitingStack));
                   	$this->addCurlProcess($processes[$processId],$processId,$multiCurlSession,$curlOptions,$runningStack,$waitingStack);
                }
            }

            // wait until there is activity on any of the curl requests or for 5 seconds
            if ($isMultiCurlRunning)
                curl_multi_select($multiCurlSession, 5);

        } while ( $isMultiCurlRunning);
        
        //close store
        curl_multi_close($multiCurlSession);
	}
	
	/**
	* Adds a process to the mutli_curl session
	*
	* @var reference array contains request data structure
	* @var int ID of the resource handle
	* @var reference object to the existing multi curl session	
	* @access public
	*/
		
	private function addCurlProcess(&$request,&$processId,&$multiCurlSession,&$curlOptions,&$runningStack,&$waitingStack) {
	
		// generate Lucene/Sorl post argument string
		$postData = '';
		foreach($request['solr'] as $key=>$value) {
			$postData .= $key.'='.$value.'&';
		}
		$postData = rtrim($postData, '&');

		// intitialize handle
		$singleCurlHandle = curl_init();
		$curlOptions[CURLOPT_URL] 		 =  "http://{$request['host']}:".SOLR_PORT."/solr/{$request['core']}/select";
		$curlOptions[CURLOPT_POSTFIELDS] = $postData;
			
		// use global curl options to set options for individual process
		curl_setopt_array($singleCurlHandle, $curlOptions);
		$result = curl_multi_add_handle($multiCurlSession, $singleCurlHandle);
		 
		if($result == 0 ){
			$resourceId = str_replace('Resource id #','', (string) $singleCurlHandle);

			//create running stack of size numParallelProcesses that hold pointers
			//to the request IDs
			$runningStack[$resourceId] = $requestId;
			
			// remove process from the waiting stack
			unset($waitingStack[$requestId]);
		}
		else {
			throw new Exception('Failed to add handle.');
		}
	}

	private function parseDocs(&$phpReponseObject,&$request) {
		$resultArray['docs'] = $phpReponseObject->response->docs;
		unset($phpReponseObject);
		return $resultArray;
	}
	
	private function parseDocsAndUnweightedFacet(&$phpReponseObject,&$request) {
		$resultArray['docs']  =  array($phpReponseObject->response->docs);
		$resultArray['facet'] =  array($phpReponseObject->response->facet_counts->facet_fields);
		unset($phpReponseObject);
		return $resultArray;
	}

	private function parseDocsAndWeightedFacet(&$phpReponseObject,&$request) {
		$resultArray['docs']  = array($phpReponseObject->response->docs);
		$weightedFacetResult  = $this->parseWeightedFacet($phpReponseObject,$request);
		$resultArray['facet'] = $weightedFacetResult['facet'];
		unset($phpReponseObject);
		return $resultArray;
	}
		
	private function parseUnweightedDocCount(&$phpReponseObject,&$request) {
		$resultArray['docCount'] = (int) $phpReponseObject->response->numFound;
		unset($phpReponseObject);
		return $resultArray;
	}	

	private function parseWeightedDocCount(&$phpReponseObject,&$request) {
		$resultArray['docCount'] = round((double) $phpReponseObject->stats->stats_fields->weight->sum,WEIGHTED_COUNT_PRECISION);
		unset($phpReponseObject);
		return $resultArray;
	}	

	private function parseUnweightedFacetQuery(&$phpReponseObject,&$request) {
		$resultArray['facetQuery'] = array($phpReponseObject->facet_counts->facet_queries);
		unset($phpReponseObject);
		return $resultArray;
	}
		
	private function parseWeightedFacetQuery(&$phpReponseObject,&$request) {
		return $this->parseWeightedDocCount($phpReponseObject, $request);
	}
	
	private function parseUnweightedFacet(&$phpReponseObject,&$request) {
		$resultArray['facet'] =  array($phpReponseObject->response->facet_counts->facet_fields);
		unset($phpReponseObject);
		return $resultArray;		
	}
	
	private function parseWeightedFacet(&$phpReponseObject,&$request) {

		$facetFields = explode(',',$request['solr']['stats.facet']);
		$facetPrefix = $request['solr']['facet.prefix']; //TODO test if value can be passed along as facet pefix without exception
		
		foreach($facetFields as $facetField) {
			$sortedfacets = array();

			if(isset($phpReponseObject->stats->stats_fields->weight->facets->$facetField)) {
				foreach($phpReponseObject->stats->stats_fields->weight->facets->$facetField as $acc => $stats) {

					// filter based on facet prefix
					if(!is_null($facetPrefix)) {
						if(! preg_match("/^$facetPrefix.*/",$acc)) {
							continue;
						}
					}
					$sortedFacets[$acc] = round((double) $stats->sum,WEIGHTED_COUNT_PRECISION);
				}

				if(isset($sortedFacets)) {
					arsort($sortedFacets);

					if($limit > 0) {
						$sortedFacets = array_slice($sortedFacets, 0, $limit);
					}

					$result['facet'][$facetField] = $sortedFacets;
					unset($sortedFacets);
				}
			}
		}
		unset($phpReponseObject);
		return $result;		
	}
	
	
	private function parseDocCount(&$phpReponseObject) {
		
	}
	private function parseHitCount(&$phpReponseObject) {
		$resultArray['hits'] = $phpReponseObject->stats->stats_fields->weight->sum;
	}
	
	private function parseFacet(&$phpReponseObject) {
		$result->facet_counts->facet_queries;
	}
	
	private function jason2php(&$rawResponse) {
		// fix to handle Lucene/Solr bug
		$tmp = str_replace('"stddev":NaN','"stddev":0.0',$rawResponse);
		$phpResponseObject = json_decode($tmp);
		unset($tmp);
		unset($rawResponse);
		return $phpReponseObject;
	}
	
}
?>