<?php
/***********************************************************
* File: SolrParserComponent
* Component to extract relevant results from Lucene/Solr results
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
* @lastmodified 2012-03-14
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

class SolrParserComponent extends Object {

	/**
	* Returns parsed Lucene/Solr results based on the results type
	* specifed for each request
	*
	* @var reference PHP object of Lucene/Solr results
	* @var reference array contains request data structure
	* @access public
	*/
		
	public function parse($phpReponseObject,&$request) {
		return $this->{'parse' . ucfirst($request['type'])}($phpReponseObject,&$request);		
	}
	
	/**
	* Returns PHP object from JSON Luence/Solr response
	*
	* @var string reference Luence/Solr raw response
	* @access public§
	*/
	
	public function jason2php(&$rawResponse) {
		// fix to handle Lucene/Solr bug TODO; check if already fixed in Lucene/Solr 3.2
		if(isset($result->stats)) {
			$rawResponse = str_replace('"stddev":NaN','"stddev":0.0',$rawResponse);
		}
		$phpResponseObject = json_decode($rawResponse);
	
		unset($tmp);
		unset($rawResponse);
		return $phpReponseObject;
	}	
	
	/**
	* Returns stored field data for specified field list
	*
	* @var reference PHP object of Lucene/Solr results
	* @var reference array contains request data structure
	* @access public
	*/
	
	private function parseDocs(&$phpReponseObject,&$request) {
		$resultArray['docs'] = $phpReponseObject->response->docs;
		unset($phpReponseObject);
		return $resultArray;
	}
	
	/**
	* Returns result array that contains documents and unweighted facet counts
	*
	* @var reference PHP object of Lucene/Solr results
	* @var reference array contains request data structure
	* @access private
	*/
		
	private function parseDocsAndUnweightedFacet(&$phpReponseObject,&$request) {
		$resultArray['docs']  =  array($phpReponseObject->response->docs);
		$resultArray['facet'] =  array($phpReponseObject->response->facet_counts->facet_fields);
		unset($phpReponseObject);
		return $resultArray;
	}
	
	/**
	* Returns result array that contains documents and weighted facet counts
	*
	* @var reference PHP object of Lucene/Solr results
	* @var reference array contains request data structure
	* @access private
	*/
	private function parseDocsAndWeightedFacet(&$phpReponseObject,&$request) {
		$resultArray['docs']  = array($phpReponseObject->response->docs);
		$weightedFacetResult  = $this->parseWeightedFacet($phpReponseObject,$request);
		$resultArray['facet'] = $weightedFacetResult['facet'];
		unset($phpReponseObject);
		return $resultArray;
	}
		
	/**
	* Returns result array that contains unweighted document counts
	*
	* @var reference PHP object of Lucene/Solr results
	* @var reference array contains request data structure
	* @access private
	*/
		
	private function parseUnweightedDocCount(&$phpReponseObject,&$request) {
		$resultArray['docCount'] = (int) $phpReponseObject->response->numFound;
		unset($phpReponseObject);
		return $resultArray;
	}	
	
	/**
	* Returns result array that contains weighted document counts 
	* - sum of weights of matching documents 
	*
	* @var reference PHP object of Lucene/Solr results
	* @var reference array contains request data structure
	* @access private
	*/
	
	private function parseWeightedDocCount(&$phpReponseObject,&$request) {
		$resultArray['docCount'] = round((double) $phpReponseObject->stats->stats_fields->weight->sum,WEIGHTED_COUNT_PRECISION);
		unset($phpReponseObject);
		return $resultArray;
	}	

	/**
	* Returns result array that contains facet query results
	*
	* @var reference PHP object of Lucene/Solr results
	* @var reference array contains request data structure
	* @access private
	*/
		
	private function parseUnweightedFacetQuery(&$phpReponseObject,&$request) {
		$resultArray['facetQuery'] = array($phpReponseObject->facet_counts->facet_queries);
		unset($phpReponseObject);
		return $resultArray;
	}

	/**
	* Returns result array that contains weighted doc counts
	* for facet query
	*
	* @var reference PHP object of Lucene/Solr results
	* @var reference array contains request data structure
	* @access private
	*/
		
	private function parseWeightedFacetQuery(&$phpReponseObject,&$request) {
		return $this->parseWeightedDocCount($phpReponseObject, $request);
	}
	
	/**
	* Returns result array that contains unweighted facet counts
	*
	* @var reference PHP object of Lucene/Solr results
	* @var reference array contains request data structure
	* @access private
	*/
		
	private function parseUnweightedFacet(&$phpReponseObject,&$request) {
		$resultArray['facet'] =  array($phpReponseObject->response->facet_counts->facet_fields);
		unset($phpReponseObject);
		return $resultArray;		
	}

	/**
	* Returns result array that contains weighted facet counts.
	* It filters and sorts weighted facets. 
	*
	* @var reference PHP object of Lucene/Solr results
	* @var reference array contains request data structure
	* @access private
	*/
	
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

					$resultArray['facet'][$facetField] = $sortedFacets;
					unset($sortedFacets);
				}
			}
		}
		unset($phpReponseObject);
		return $resultArray;		
	}
}
?>