<?php
/***********************************************************
* File: search_controller.php
* Description: Users can use a SQL like query syntax including
* logical combinations of annotation fields to filter datasets. 
* For example a user may filter results based on the BLAST E-
* Value or combination of BLAST E-Value and percent identity, 
* search for only bacterial species, or choose to exclude results
* that have BLAST hits to eukaryotes. The search returns results
* as well as frequency count lists and pie charts, that summarize 
* the top functional and taxonomic categories for the identified
* subset. Counts and identiers can be exported as tab delimited
* files.
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

#increase php dowload limits on space and time
ini_set('memory_limit','556M');
ini_set('max_execution_time','3000');

define('LUCENE_QUERY_EXCEPTION','Lucene Query Syntax Exception. Please correct your query and try again.');

class SearchController extends AppController {

	var $name 			= 'Search';
	var $helpers 		= array('LuceneResultPaginator','Facet','Tree','Ajax','Dialog');	
	var $uses 			= array('Project','Population','Library');	
	var $components 	= array('Session','RequestHandler','Solr','Format');
	var $searchFields 	= array(1 => 'Lucene Query',
								'Core Fields' => array( 
											'peptide_id' =>'Peptide ID',										
											'com_name_txt' =>'Common Name',
											'com_name_src'=>'Common Name Source',
											'go_id' =>'Gene Ontology ID',
											'go_tree' =>'Gene Ontology Tree',
											'go_src'=>'Gene Ontology Source',
											'ec_id' =>'Enzyme ID',
											'ec_src'=>'Enzyme Source',	
											'hmm_id'=>'HMM ID',		
											'library_id' =>'Library ID',								
											),
								'BLAST Fields' => array(
											'blast_species' =>'Species',
											'blast_tree'=>'Taxonomy [NCBI Taxon ID]',
											'blast_evalue_exp'=>'Min. Neg. E-Value Exponent [Positive Integer]',
											'blast_pid'=>'Min. Percent Identity [between 0 and 1]',
											'blast_cov' =>'Min. Percent Coverage [between 0 and 1]',
											),);
											
	var $luceneFields = array('peptide_id','com_name_txt','com_name_src','go_id','go_tree',
							  'go_src','ec_id','ec_src','hmm_id','library_id','blast_species',
							  'blast_tree','blast_evalue_exp','blast_pid','blast_cov','apis_tree',
							  'cluster_id','filter');
	
	//this function lets us search the lucene index, by default it returns the first page of all results (*|*)
	function index($dataset='CBAYVIR',$page=1,$sessionQueryId=null) {
			
		#add otpional datatypes				
		$optionalDatatypes  = $this->Project->checkOptionalDatatypes(array($dataset));
				
		if($optionalDatatypes['viral'] || $optionalDatatypes['clusters'] || $optionalDatatypes['apis'] || $optionalDatatypes['filter']) {
			if($optionalDatatypes['viral']) {
					$optionalFields['env_id']= 'Environmental Library';
			}	
			if($optionalDatatypes['apis']) {
					$optionalFields['apis_tree']= 'APIS Taxonomy [NCBI Taxon ID]';
			}					
			if($optionalDatatypes['clusters']) {
					$optionalFields['cluster_id']= 'Cluster ID';
			}				
			if($optionalDatatypes['filter']) {
					$optionalFields['filter']= 'Filter';
			}	
			$this->searchFields['Optional Fields'] = $optionalFields;						
		}		
		
		$this->Session->write('searchFields',$this->searchFields);
		
		//for paging use existing query session
		if($sessionQueryId) {
			if($this->Session->valid()) {
				$query = $this->Session->read($sessionQueryId);				
			}
			else {
				$this->Session->setFlash("Your search session has expired. Please reenter your query.");
				$this->redirect(array('controller'=>'search','action' => 'index', $dataset));	
			}		
		}
		
		//otherwise create query session
		else {		
			//read fields from POST form and generate and store 
			//lucene query and search field in the session variable
			$query = $this->data['Search']['query'];
			$field = $this->data['Search']['field'];			
			$query = $this->generateLuceneQuery($query,$field); 	
			$sessionQueryId = 'query_'.time();
			$this->Session->write($sessionQueryId,$query);
		}
	
		
		//specify facet default behaviour
		$solrArguments = array(	'fl' => 'peptide_id com_name com_name_src blast_species blast_evalue go_id go_src ec_id ec_src hmm_id',
						'facet' => 'true',
						'facet.field' => array('blast_species','com_name','go_id','ec_id','hmm_id'),
						'facet.mincount' => 1,
						"facet.limit" => NUM_TOP_FACET_COUNTS);

		#handle exceptions
		$numHits= 0;
		$facets = array();
		$hits 	= array();
		
		try{
			$result = $this->Solr->search($dataset,$query, ($page-1)*NUM_SEARCH_RESULTS,NUM_SEARCH_RESULTS,$solrArguments,true);	
			$numHits= (int) $result->response->numFound;
			$facets = $result->facet_counts;
			$hits 	= $result->response->docs;
		}
		catch (Exception $e) {
			$this->set('exception',LUCENE_QUERY_EXCEPTION);
		}
				
		//store facets for download
		$this->Session->write('facets',$facets);
		
		//prepare view		
		$this->set('projectName', $this->Project->getProjectName($dataset));
		$this->set('projectId', $this->Project->getProjectId($dataset));
		$this->set('hits',$hits);
		$this->set('dataset',$dataset);
		$this->set('numHits',$numHits);
		$this->set('facets',$facets);
		$this->set('sessionQueryId',$sessionQueryId);
		$this->set('page',$page);				
	}
	
	/**
	 * Search all datasets
	 * 
	 * @param String $query Lucene query string
	 * @return void
	 * @access public
	 */
	public function all($query = "*:*") {
				
		//returns all datasets the current user has access to
		$datasets  = $this->Project->findUserDatasets(LIBRARY_DATASETS);
		$totalHits = null;
		$facets = array('habitat'=>array(),'location'=>array(),'filter'=>array(),'project'=>array(),'depth'=>array());
		
		//if a query string has been passed in as a variable
		if($query != "*:*") {
			$this->Session->write('searchField',1);		
		}
		//read fields from POST form and generate and store 
		//lucene query and search field in the session variable		
		else {
			$query = $this->data['Search']['query'];
			$field = $this->data['Search']['field'];			
			$query = $this->generateLuceneQuery($query,$field); 	
		}
					
		$totalHits = 0;
		
		foreach($datasets as &$dataset) {				
			$numHits = 0;
			
			//get number of hits
			try {
				$numHits = $this->Solr->count($dataset['name'],$query);
			}
			catch (Exception $e) {				
				$this->set('exception',LUCENE_QUERY_EXCEPTION);
				break;			
			}
			
			$totalHits += $numHits;
			$dataset['hits']  = $numHits;
			
			//get number of overall counts
			if($query === '*:*') {
				$counts = $numHits;
			}
			else {
				$counts = $this->count($dataset['name']);
			}
			
			$dataset['counts'] = $counts;
			
			if($numHits > 0 ) {
				$libraryMetadata = $this->Library->find('all', array('fields'=>array('sample_habitat','sample_filter','sample_longitude','sample_latitude','sample_depth'),'conditions' => array('Library.name' => $dataset['name'])));
				$habitat = $libraryMetadata[0]['Library']['sample_habitat'];
				$filter = $libraryMetadata[0]['Library']['sample_filter'];
				$depth = $libraryMetadata[0]['Library']['sample_depth'];
				$location = trim($libraryMetadata[0]['Library']['sample_latitude']." ".$libraryMetadata[0]['Library']['sample_longitude']);
				
				if(empty($habitat)) {
					$habitat = 'unassigned';
				}
				if(empty($location)) {
					$location = 'unassigned';
				}	
				if(empty($filter)) {
					$filter = 'unassigned';
				}	
				if(empty($depth)) {
					$depth = 'unassigned';
				}				
				if(empty($dataset['project'])) {
					$project = 'unassigned';
				}	
				else {
					$project = $dataset['project'];
				}
																					
				if(array_key_exists($habitat,$facets['habitat'])) {
					$facets['habitat'][$habitat] += $numHits;
				} 
				else {
					$facets['habitat'][$habitat] =  $numHits;
				}
				if(array_key_exists($location,$facets['location'])) {
					$facets['location'][$location] +=  $numHits;
				} 
				else {
					$facets['location'][$location] =  $numHits;
				}
				if(array_key_exists($depth,$facets['depth'])) {
					$facets['depth'][$depth] +=  $numHits;
				} 
				else {
					$facets['depth'][$depth] =  $numHits;
				}				
				if(array_key_exists($filter,$facets['filter'])) {
					$facets['filter'][$filter] +=  $numHits;
				} 
				else {
					$facets['filter'][$filter] =  $numHits;
				}		
				if(array_key_exists($project,$facets['project'])) {
					$facets['project'][$project] +=  $numHits;
				} 
				else {
					$facets['project'][$project] =  $numHits;
				}																	
			}
			if($dataset['counts'] > 0) {
				$percent = round(($dataset['hits'] /$dataset['counts'])*100,2);
			}	
			else {
				$percent = 0;			
			}
			$dataset['perc'] = $percent;
		}	
	
		if($numHits > 0) {
			foreach($facets as $key => $value){
				arsort($facets[$key]);
				$facets[$key] = array_slice($facets[$key],0,10,true);
			}

			//sort results by absolute counts
			usort($datasets, array('SearchController','sortResultsByCounts'));			
		}
	
		$this->Session->write('searchResults',$datasets);
		$this->Session->write('searchFields',$this->searchFields);
		$this->Session->write('query',$query);
		$this->Session->write('facets',$facets);
		$this->Session->write('numHits',$totalHits);
		$this->Session->write('numDatasets',count($datasets));
	}
	
	
	private function sortResultsByCounts($a, $b) { return strnatcmp($b['hits'], $a['hits']); }
	
	public function count($dataset) {
		try {
			$count = $this->Solr->count($dataset);;
		}
		catch(Exception $e) {
			$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
			$this->redirect('/projects/index');
		}
		return $this->Solr->count($dataset);
	}	
	
	public function dowloadFacets($dataset,$numHits,$sessionQueryId) {
		$this->autoRender=false; 
		
		$query = $this->Session->read($sessionQueryId);

		#get facet data from session
		$facets = $this->Session->read('facets');

		$content=$this->Format->facetListToDownloadString('Search Results - Top 10 Functional Categories',$dataset,$facets,$query,$numHits);
		
		$fileName = "jcvi_metagenomics_report_".time().'.txt';
		
        header("Content-type: text/plain"); 
        header("Content-Disposition: attachment;filename=$fileName");
       
        echo $content;
	}
	
	public function downloadMetaInformationFacets() {
		$this->autoRender=false; 
		
		//read session variables
		$numHits 	= $this->Session->read('numHits');
		$query 		= $this->Session->read('query');
		$numDatasets= $this->Session->read('numDatasets');

		#get facet data from session
		$facets = $this->Session->read('facets');

		$content=$this->Format->facetMetaInformationListToDownloadString('Search Results - Top 10 Metainformation Categories',$facets,$query,$numHits,$numDatasets);
		
		$fileName = "jcvi_metagenomics_report_".time().'.txt';
		
        header("Content-type: text/plain"); 
        header("Content-Disposition: attachment;filename=$fileName");
       
        echo $content;		
	}
	
	public function dowloadData($dataset,$numHits,$sessionQueryId) {
		$this->autoRender=false; 
		
		$query = $this->Session->read($sessionQueryId);
		
		$fileName = "jcvi_metagenomics_report_".time().'.txt';
		$fileLocation = METAREP_TMP_DIR."/$fileName";
		
		$fh = fopen("$fileLocation", 'w');
		
		
		$facets =  $this->Session->read('facets');
		$content = $this->Format->infoString('Search Results - Peptide Id List',$dataset,$query,$numHits);	
		$content.="Peptide Id\n";
		fwrite($fh, $content);				
		
		$solrArguments = array(	'fl' => 'peptide_id');
		
		#get rows in batches of 10,000 and add to content string
		for($i=0;$i<$numHits+20000;$i+=20000) {
			
			
			try{		
				$result = $this->Solr->search($dataset,$query,$i,20000,$solrArguments);	
			}
			catch (Exception $e) {				
				$this->Session->setFlash("METAREP Lucene Query Exception. Please correct your query and try again.");
				$this->redirect(array('action' => 'search', $solrArguments));
			}
		
			$rows 	= $result->response->docs;
					
			foreach ( $rows as $row ) {	
				$line =$this->Format->dataRowToDownloadString($row);
				fwrite($fh, $line);				
			}
			unset($results);
			unset($rows);			
		}
				
        header('Content-type: text/plain');
        header("Content-disposition: attachment; filename=$fileName");		
		readfile($fileLocation);	
	}
	
	/**
	 * Generates Lucene query basede on a search term and a 
	 * selected field (search field drop down)
	 * 
	 * @param String $query entered search term
	 * @param String $field selected field 

	 * @return String lucene query (<field>:<term>)
	 * @access private
	 */	
	private function generateLuceneQuery($query,$field) {
		
		//if a non default query has been supplied
		if(!empty($query) && $query !='*:*') {	
			$query = preg_replace('/[gG][oO]\:/i','GO\:',$query);
					
			$queryParts = explode(":",$query);		
			
			//get rid of lucene specific characters
			$firstField = preg_replace('/(NOT||AND||\-||\+)/i','',trim($queryParts[0]));
							
			//handle default lucene query 
			if($field == 1 && count($queryParts) == 1) {
				$query = "com_name_txt:$query";
			}
			//handle non-Lucene queries
			else if(!in_array($firstField,$this->luceneFields) && $field != 1) {
				
				//translate selected field and query into a lucene query
				if($field === 'blast_evalue_exp' || $field === 'blast_pid' ||  $field === 'blast_cov') {
							$query = '{'.$queryParts[count($queryParts)-1].' TO *}'; 
				}
				else if($field === 'go_tree') {
					$query = ltrim(str_replace('GO\:','',$query),'0');
				}					
				$query = "$field:$query";		
			}

			//set search field to Lucene Query
			$this->Session->write('searchField',1);		
		}
		//set default query
		else {
			//set search field to Lucene Query
			$query = "*:*";
			$this->Session->write('searchField','com_name_txt');			
		}
	
		return $query;
	} 
	
	/**
	 * Wrapps around index function and provides access
	 * to search results by specifying a dataset and query.
	 * Used for links in the search help dialog.
	 * 
	 * @param String $dataset the dataset to search in
	 * @param String $query lucene query to use

	 * @return void
	 * @access public
	 */		
	public function link($action,$query,$dataset=null) {
		$query = str_replace('@',':',$query);	
		if($action === 'index') {
			$this->Session->write('searchField',1);					
			$sessionQueryId = 'query_'.time();
			$this->Session->write($sessionQueryId,$query);			
			$this->index($dataset,1,$sessionQueryId);
		}
		else if($action === 'all') {
			$this->all($query);
		}		
		$this->render($action);
	}
}
?>
