<?php
/***********************************************************
*  File: solr_source.php
*  Description:
*
*  Author: jgoll
*  Date:   Feb 16, 2010
************************************************************/

App::import('Vendor', 'SolrPhpClient/Apache/Solr/Service.php');

class SolrSource extends DataSource {
	
    var $solrHost = "";
    var $solrPort = "";
 	
    protected $_schema = array();
    
	function __construct($config) {
        parent::__construct($config);
        #$this->Http =& new HttpSocket();
        $this->solrHost = $this->config['host'];
        $this->solrPort = $this->config['port'];
    } 
    
	public function read($dataset,$query, $offset = 0, $limit = 10, $params = array(), $method = self::METHOD_GET,$debug=false){

		$solr = new Apache_Solr_Service( $this->solrHost, $this->solrPort, "/solr/$dataset" );

		try {
			$result= $solr->search($query, $offset,$limit,$params,$method);
			$numHits = (int) $result->response->numFound;
			$facets = $result->facet_counts;
			$hits = $result->response->docs;
			$this->removeUnassignedValues($hits);
		}
		//rethrow exception
		catch (Exception $e) {
			throw new Exception($e);
		}
		return $result;
	}

	public function connect(){}
	
	function create($model, $fields = array(), $values = array()) {}
	function update($model, $fields = array(), $values = array()) {}
	function delete($model, $id = null) {}
	
	public function count($dataset,$query="*:*",$params=null) {

		$solr = new Apache_Solr_Service( $this->solrHost, $this->solrPort, "/solr/$dataset");

		try {
			$result= $solr->search($query, 0,0,$params);

			//get the number of hits
			$numHits = (int) $result->response->numFound;
		}
		catch (Exception $e) {
			die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
		}
		return $numHits;
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
}