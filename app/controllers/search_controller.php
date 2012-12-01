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
 * @version METAREP v 1.4.0
 * @author Johannes Goll
 * @lastmodified 2010-07-09
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 **/

#increase php dowload limits on space and time
ini_set('memory_limit','556M');
ini_set('max_execution_time','3000');

define('MAX_NUM_QUERY_NAMES',400);
define('MIN_NAME_QUERY_LENGTH',4);
define('LUCENE_QUERY_EXCEPTION','Lucene Query Syntax Exception. Please correct your query and try again.');
define('SHORT_QUERY_EXCEPTION','Please enter at least '.MIN_NAME_QUERY_LENGTH.' characters. Please correct your query and try again.');
define('NO_NAMES_FOUND_QUERY_EXCEPTION','No macthing names found. Please correct your query and try again.');
define('TOO_MANY_NAMES_FOUND_QUERY_EXCEPTION','Too many matching names found (> '.MAX_NUM_QUERY_NAMES.'). Please enter a more specific query and try again.');

class SearchController extends AppController {

	var $name 			= 'Search';
	var $helpers 		= array('LuceneResultPaginator','Facet','Tree','Ajax','Dialog');
	var $uses 			= array();
	var $components 	= array('Session','RequestHandler','Solr','Download','Format','Blast');

	var $searchFields 	= array(1 => 'Lucene Query',

								'Search By ID' => array( 
											'peptide_id'=>'Peptide ID',											
											'blast_tree'=>'NCBI Taxonomy (Blast) ID',	
											'kegg_id' 	=>'Kegg Pathway ID',
											'metacyc_id'=>'Metacyc Pathway ID',																											
											'go_id' 	=>'Gene Ontology Term ID',
											'go_tree' 	=>'Gene Ontology Tree ID',																																
											'ec_id' 	=>'Enzyme ID',											
											'hmm_id'	=>'HMM ID',		
											'library_id'=>'Library ID',								
	),
								'Search By Name' => array( 
									'com_name_txt' =>'Common Name',
									'go_name' =>'Gene Ontology Term Name',
									'go_tree_name' =>'Gene Ontology Tree Name',
									'blast_tree_name'=>'NCBI Taxonomy (Blast) Name',	
									'blast_species_name' =>'Species (Blast) Name',
									'kegg_name' =>'Kegg Pathway Name (EC)',
									'metacyc_name' =>'Metacyc Pathway Name',
									'ec_name' =>'Enzyme Name',	
									'hmm_name'=>'HMM Name',												
	),
								'Search By Source' => array( 	
											'com_name_src'=>'Common Name Source',	
											'ec_src'=>'Enzyme Source',	
											'go_src'=>'Gene Ontology Source',											
	),
								'Search By Blast Statistics' => array(
											'blast_evalue_exp'=>'Min. Neg. E-Value Exponent [Positive Integer]',
											'blast_pid'=>'Min. Percent Identity [between 0 and 1]',
											'blast_cov' =>'Min. Percent Coverage [between 0 and 1]',
	),
	);

	var $facetFields = array(
								'blast_species'=>'Species (Blast)',
								'com_name'=>'Common Name',
								'go_id'=>'Gene Ontology',
								'ec_id'=>'Enzyme',
								'hmm_id'=>'HMM',
	);

	var $luceneFields = array('peptide_id','com_name_txt','com_name_src','go_id','go_tree',
							  'go_src','ec_id','ec_src','hmm_id','library_id','blast_species',
							  'blast_tree','blast_evalue_exp','blast_pid','blast_cov','apis_tree',
							  'cluster_id','filter','kegg_tree','ko_id','weight');

	var $resultFields = array(
								'peptide_id'=>'Peptide ID',
								'com_name'=>'Common Name',
								'com_name_src'=>'Common Name Source',
								'blast_species'=>'Blast Species',
								'blast_evalue'=>'Blast E-Value',
								'go_id'=>'GO ID',
								'go_src'=>'GO Source',
								'ec_id'=>'EC ID',
								'ec_src'=>'EC Source',
								'hmm_id'=>'HMM',
	);

	//this function lets us search the lucene index, by default it returns the first page of all results (*|*)
	function index($dataset,$page=1,$sessionQueryId=null) {
		$this->loadModel('Project');

		$time_start = getmicrotime();

		$pipeline	=  $this->Project->getPipeline($dataset);

		//add otpional datatypes
		$optionalDatatypes  = $this->Project->checkOptionalDatatypes(array($dataset));

		if($optionalDatatypes['viral'] || $optionalDatatypes['clusters'] || $optionalDatatypes['apis'] || $optionalDatatypes['filter']  || $optionalDatatypes['ko'] || $optionalDatatypes['sequence'])  {
			if($optionalDatatypes['viral']) {
				$this->searchFields['Search By Name']['env_lib'] = 'Environmental Library Name';
			}
			if($optionalDatatypes['apis']) {
				$this->searchFields['Search By ID']['apis_tree']= 'NCBI Taxonomy (Apis) ID';
				$this->searchFields['Search By Name']['apis_tree_name']= 'NCBI Taxonomy (Apis) Name';
				$this->searchFields['Search By Name']['apis_species_name']= 'Species (Apis) Name';
			}
			if($optionalDatatypes['clusters']) {
				$this->searchFields['Search By ID']['cluster_id']= 'Cluster ID';
				$this->searchFields['Search By Name']['cluster_name']= 'Cluster Name';
			}
			if($optionalDatatypes['filter']) {
				$this->searchFields['Search By Name']['filter']= 'Filter';
			}
			
			if($optionalDatatypes['ko']) {	
				
				$this->facetFields = array(
									'blast_species'=>'Species (Blast)',
									'ko_id'=>'Kegg Ortholog',
									'go_id'=>'Gene Ontology',
									'ec_id'=>'Enzyme',
				);
	
				$this->resultFields['ko_id'] = 'KO ID';
							
				$this->searchFields['Search By Name']['ko_name']='Kegg Ortholog Name';
				$this->searchFields['Search By Name']['kegg_tree_name']='Kegg Pathway Name (KO)';
				$this->searchFields['Search By ID']['ko_id']='Kegg Ortholog ID';
			}	

			if($optionalDatatypes['sequence']) {	
				$this->searchFields['Search By Sequence (Blast)']['seq_id05']='Search By Sequence (BLAST 1E-5)';
				$this->searchFields['Search By Sequence (Blast)']['seq_id10']='Search By Sequence (BLAST 1E-10)';
				$this->searchFields['Search By Sequence (Blast)']['seq_id50']='Search By Sequence (BLAST 1E-50)';
			}				
		}

		//for paging use existing query session
		if($sessionQueryId) {
			if($this->Session->valid()) {
				$query = $this->Session->read($sessionQueryId);
			}
			else {
				$this->Session->setFlash("Your search session has expired. Please reenter your query.");
				$this->redirect(array('controller'=>'search','action' => 'index', $dataset),null,true);
			}
		}

		//otherwise create query session
		else {
			//read fields from POST form and generate and store
			//lucene query and search field in the session variable
			$query = $this->data['Search']['query'];
			$field = $this->data['Search']['field'];

			try{
				$query = $this->generateLuceneQuery($query,$field,$dataset);
			}
			catch (Exception $e) {
				//write query
				$sessionQueryId = 'query_'.time();
				$this->Session->write($sessionQueryId,$query);
				$this->Session->write('searchField',$field);

				//set view variables
				$this->set('exception',$e->errorMessage());
				$this->set('sessionQueryId',$sessionQueryId);
				$this->set('projectName', $this->Project->getProjectName($dataset));
				$this->set('projectId', $this->Project->getProjectId($dataset));
				$this->set('dataset',$dataset);
				$this->set('numHits',0);			
				$this->render();
			}

			$sessionQueryId = 'query_'.time();
			$this->Session->write($sessionQueryId,$query);
		}

		//skip facet field com_name for HUMANN data sets
		if($pipeline === PIPELINE_HUMANN ) {

			$this->facetFields = array(
								'blast_species'=>'Species (Blast)',
								'ko_id'=>'Kegg Ortholog',
								'go_id'=>'Gene Ontology',
								'ec_id'=>'Enzyme',
			);

			$this->resultFields = array(
								'peptide_id'=>'Peptide ID',
								'com_name'=>'Common Name',
								'com_name_src'=>'Common Name Source',
								'blast_species'=>'Blast Species',
								'ko_id'=>'KO ID',
								'go_id'=>'GO ID',
								'go_src'=>'GO Source',
								'ec_id'=>'EC ID',
								'ec_src'=>'EC Source',
			);



			$this->searchFields['Search By Name']['ko_name']='Kegg Ortholog Name';
			$this->searchFields['Search By Name']['kegg_tree_name']='Kegg Pathway Name (KO)';
			$this->searchFields['Search By ID']['ko_id']='Kegg Ortholog ID';
			$this->searchFields['Search By ID']['kegg_tree_id']='Kegg Pathway ID';
		}


		asort($this->searchFields['Search By ID']);
		asort($this->searchFields['Search By Name']);

		//specify facet default behaviour
		$solrArguments = array(	'fl' => join(' ',array_keys($this->resultFields)),
						'facet' => 'true',
						'facet.field' => array_keys($this->facetFields),
						'facet.mincount' => 1,
						"facet.limit" => NUM_TOP_FACET_COUNTS);


		$numHits= 0;
		$facets 	= array();
		$documents 	= array();

		try{
			$result = $this->Solr->search($dataset,$query, ($page-1)*NUM_SEARCH_RESULTS,NUM_SEARCH_RESULTS,$solrArguments,true);
			$numDocuments = $this->Solr->documentCount($dataset,$query);
		}
		catch (Exception $e) {
			$this->set('exception',LUCENE_QUERY_EXCEPTION);
		}
		$numHits	= $result->response->numFound;
		$facets 	= $result->facet_counts;
		$documents 	= $result->response->docs;

		//store facets for download
		$this->Session->write('facets',$facets);

		$time_end = getmicrotime();
		#debug('Execution time: ' . round($time_end - $time_start,2) .' seconds.');

		$this->Session->write('facetFields',$this->facetFields);
		$this->Session->write('searchFields',$this->searchFields);
		$this->Session->write('resultFields',$this->resultFields);

		//prepare view
		$this->set('projectName', $this->Project->getProjectName($dataset));
		$this->set('projectId', $this->Project->getProjectId($dataset));
		$this->set('documents',$documents);
		$this->set('dataset',$dataset);
		$this->set('numHits',$numHits);
		$this->set('numDocuments',$numDocuments);
		$this->set('pipeline',$pipeline);
		$this->set('facets',$facets);
		$this->set('facetFields',$this->facetFields);
		$this->set('resultFields', $this->resultFields);
		$this->set('sessionQueryId',$sessionQueryId);
		$this->set('hasSequence',$optionalDatatypes['sequence']);
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
		$this->loadModel('Project');

		//adjust fields to allow search accross all samples including weighted datasets
		unset($this->searchFields['Search By Blast Statistics']);
		unset($this->searchFields['Search By Name']['hmm_name']);
		#unset($this->searchFields['Search By Name']['kegg_name']);
		unset($this->searchFields['Search By ID']['hmm_id']);
		#unset($this->searchFields['Search By ID']['kegg_id']);		
	
		//if a query string has been passed in as a variable
		if($query != "*:*") {
			$this->Session->write('searchField',1);				
		}
		//read fields from POST form and generate and store 
		//lucene query and search field in the session variable		
		else {
			$query = $this->data['Search']['query'];
			$field = $this->data['Search']['field'];
			
			try {
				$query = $this->generateLuceneQuery($query,$field);
			}
			catch (Exception $e) {
				$this->set('exception',$e->errorMessage());				
				$this->Session->write('query',$query);	
				$this->Session->write('numHits',0);	
				$this->Session->write('searchField',$field);
				$this->render();
			}	
		}		
		
		asort($this->searchFields['Search By ID']);	
		asort($this->searchFields['Search By Name']);		
		
		//get user id to make/get user specific cache
		$currentUser	= Authsome::get();
		$currentUserId 	= $currentUser['User']['id'];
		
		//try to use cache for default query *:*" 
		if ($query != "*:*" || ($searchAllResults = Cache::read($currentUserId.'searchAllResults')) === false) {	
			
			//start search all
			$totalHits = 0;
			
			//returns all datasets the current user has access to
			$datasets  = $this->Project->findUserDatasets(LIBRARY_DATASETS);
			
			$facets = array('habitat'=>array(),'location'=>array(),'filter'=>array(),'project'=>array(),'depth'=>array());
												
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
					$this->loadModel('Library');
					$libraryMetadata = $this->Library->find('all', array('fields'=>array('sample_habitat','sample_filter','sample_longitude','sample_latitude','sample_depth'),'conditions' => array('Library.name' => $dataset['name'])));
					
					$habitat 	= $libraryMetadata[0]['Library']['sample_habitat'];
					$filter 	= $libraryMetadata[0]['Library']['sample_filter'];
					$depth 		= $libraryMetadata[0]['Library']['sample_depth'];
					$location 	= trim($libraryMetadata[0]['Library']['sample_latitude']." ".$libraryMetadata[0]['Library']['sample_longitude']);
					
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
		
			if($totalHits >0 ){	
				foreach($facets as $key => $value){
					arsort($facets[$key]);
					$facets[$key] = array_slice($facets[$key],0,NUM_TOP_FACET_COUNTS,true);
				}
			}				
			
			
			
			//sort results by absolute counts
			usort($datasets, array('SearchController','sortResultsByCounts'));			
					
			//store everything in the searchAllResults object for caching
			$searchAllResults['datasets'] 	= $datasets;
			$searchAllResults['facets'] 	= $facets;
			$searchAllResults['numHits'] 	= $totalHits;
			$searchAllResults['query'] 		= $query;
			$searchAllResults['numDatasets']= count($datasets);
			
			//cache query results
			if($query === '*:*') {
				Cache::write($currentUserId.'searchAllResults', $searchAllResults);
			}
		}		
		
		//store data in session for search all view
		$this->Session->write('searchResults',$searchAllResults['datasets']);
		$this->Session->write('searchFields',$this->searchFields);
		$this->Session->write('query',$searchAllResults['query']);
		$this->Session->write('facets',$searchAllResults['facets']);
		$this->Session->write('numHits',$searchAllResults['numHits']);
		$this->Session->write('numDatasets',$searchAllResults['numDatasets']);
	}

	public function count($dataset) {
		try {
			$count = $this->Solr->documentCount($dataset);
		}
		catch(Exception $e) {
			$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
			$this->redirect('/projects/index',null,true);
		}
		return $count;
	}

	public function dowloadFacets($dataset,$numHits,$sessionQueryId) {
		$this->autoRender=false;

		$query = $this->Session->read($sessionQueryId);
		$facetFields = $this->Session->read('facetFields');

		#get facet data from session
		$facets = $this->Session->read('facets');

		$content=$this->Format->facetListToDownloadString('Search Results - Top 10 Functional Categories',$dataset,$facets,$facetFields,$query,$numHits);

		$fileName = uniqid('jcvi_metagenomics_report_').'.txt';
		$this->Download->string($fileName,$content);
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

		$fileName = uniqid('jcvi_metagenomics_report_').'.txt';
		$this->Download->string($fileName,$content);
	}	

	/**
	 * Download all annotations or list of IDs. Writes data to file first before opening the download dialog.
	 *
	 * @param String $dataset Dataset
	 * @param int $numHits number of the search
	 * @param String $sessionQueryId session id of the query
	 * @param boolean $idsOnly if true, onlyue download IDs; if false download all annotations
	 * @return void
	 * @access public
	 */
	public function dowloadAnnotation($dataset,$numHits,$sessionQueryId,$idsOnly) {
		$this->autoRender=false;

		$query = $this->Session->read($sessionQueryId);
		$resultFields = $this->Session->read('resultFields');

		$fileName = uniqid('jcvi_metagenomics_report_').'.txt';
		$fileLocation = METAREP_TMP_DIR."/$fileName";

		$fh = fopen("$fileLocation", 'w');

		if($idsOnly) {
			$title  = "Peptide ID List";
			$fields = array('peptide_id');
		}
		else {
			$title  = "Annotation List";
			
			$fields = array_keys($resultFields);
			
		}
		
		$content = $this->Format->infoString("Search Results - $title",$dataset,$query,0,$numHits);

		//add heading
		$headingFields = array();
		foreach($fields as $field) {
			array_push($headingFields,$resultFields[$field]);
		}	
		$content.=join("\t",$headingFields)."\n";	
		fwrite($fh, $content);
		
		$fieldArgument =  join(',',$fields);
		
		
		//get rows in batches of 10,000 and add to content string
		for($i=0;$i<$numHits+20000;$i+=20000) {

			try{
				$documents = $this->Solr->fetch($dataset,$query,$fieldArgument,$i,20000);
			}
			catch (Exception $e) {
				$this->Session->setFlash("METAREP Lucene Query Exception. Please correct your query and try again.");
				$this->redirect(array('action' => 'index'),null,true);

			}

			foreach ($documents as $document ) {
				$rowArray = array();				
				foreach($fields as $field) {
					if(is_array($document->{$field})) {
						array_push($rowArray,join('||',$document->{$field}));						
					}
					else {
						array_push($rowArray,$document->{$field});
					}
				}
				fwrite($fh, join("\t",$rowArray)."\n");
			}
			unset($documents);
		}
		fclose($fh);
		$this->Download->textFile($fileName,$fileLocation);
	}

	/**
	 * Download sequences in fasta format. Fetches IDs from lucene index and pulls sequences from 
	 * the sequence store location defined by SEQUENCE_STORE_PATH using fastacmd
	 *
	 * @param String $dataset Dataset
	 * @param int $projectId Project ID 
	 * @param int $numHits number of hits found for the query
	 * @param String $sessionQueryId session id of the query
	 * @return void
	 * @access public
	 */
	public function dowloadSequences($dataset,$projectId,$numHits,$sessionQueryId) {
		$this->autoRender=false;
		$this->loadModel('Project');
		
		//variable to preserve the original passed-in dataset
		$originalDataset = $dataset;
		
		//read query from session
		$query = $this->Session->read($sessionQueryId);

		//specify fasta file
		$fastaFileName 	= uniqid('jcvi_metagenomics_report_').'.fasta';
		$fastaFilePath  = METAREP_TMP_DIR."/$fastaFileName";
		
		//handle populations
		if($this->Project->isPopulation($originalDataset)) {
			$this->loadModel('Population');
			$datasets = $this->Population->getLibraries($originalDataset);		
		}
		else {
			$datasets = array($originalDataset);
		}		
		
		foreach($datasets as $dataset) {			
			$idFileName    	= uniqid('tmp_').'.txt';
			$idFilePath		= METAREP_TMP_DIR."/$idFileName";
			
			$fh = fopen($idFilePath, 'w');
			
			//iterate over index to avoid out-of-memory exceptions
			$batchSize = 25000;
			
			//get rows in batches of $batchSize
			for($i=0;$i<$numHits+$batchSize;$i+=$batchSize) {
				try{
					//foreach batch suze query peptide ids for the given orginal dataset addign the library to the query to 
					//restric results to library subsets for populations
					$documents = $this->Solr->fetch($originalDataset,"$query AND library_id:$dataset",'peptide_id',$i,$batchSize);
				}
				catch (Exception $e) {
					$this->Session->setFlash("METAREP Lucene Query Exception. Please correct your query and try again.");
					$this->redirect(array('action' => 'index'),null,true);
				}
	
				foreach ( $documents as $document ) {
					fwrite($fh, $document->peptide_id."\n");
				}
	
				unset($documents);
			}
			fclose($fh);
			exec(FASTACMD_PATH." -d ".SEQUENCE_STORE_PATH."/$projectId/$dataset/$dataset -i $idFilePath >> $fastaFilePath");
			
			unlink($idFilePath);
		}	
		
		exec(LINUX_BINARY_PATH."/sed -i 's/^>lcl|/>/' $fastaFilePath");
		
		//prepare download 
		header('Content-description: File Transfer');		
		header('Content-type: text/plain');
		header("Content-disposition: attachment; filename=$fastaFileName");
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		readfile($fastaFilePath);				
	}	
	
	/**
	 * Wrapps around index function and provides access
	 * to search results by specifying a dataset and query.
	 * Used for links in the search help dialog.
	 *
	 * @param String $dataset the dataset to search in
	 * @param String $action either index or all
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
	/**
	 * Generates Lucene query basede on a search term and a
	 * selected field (search field drop down)
	 *
	 * @param String $query entered search term
	 * @param String $field selected field

	 * @return String lucene query (<field>:<term>)
	 * @access private
	 */
	private function generateLuceneQuery($query,$field,$dataset=null) {
		//delete prvious suggestions
		$this->Session->delete('suggestions');

		//remove white spaces
		$query = trim($query);
			
		//if a non default query has been supplied
		if(!empty($query) && $query != '*:*') {

			$queryParts = explode(":",$query);

			//get rid of lucene specific characters
			$firstField = preg_replace('/(NOT||AND||\-||\+)/i','',trim($queryParts[0]));

			//handle default lucene query
			if($field == 1 && count($queryParts) == 1) {
				$query = $this->Solr->escape($query);
				$query = "com_name_txt:$query";
			}
			//handle non-Lucene queries
			else if(!in_array($firstField,$this->luceneFields) && $field != 1) {

				//check for short query for non-id fields
				$fieldPostFix = substr($field, strlen($field) - 3);

				if($fieldPostFix != '_id' && !preg_match('/blast_/',$field )) {
					if(strlen($query) < MIN_NAME_QUERY_LENGTH) {
						throw new ShortQueryException();
					}
				}

				//translate selected field and query into a lucene query
				switch($field) {
					case "blast_evalue_exp":
					case "blast_pid":
					case "blast_cov":
						$query = '{'.$queryParts[count($queryParts)-1].' TO *}';
						$query = "$field:$query";
						break;
					case "go_tree":
						$query = ltrim(str_replace('GO\:','',$query),'0');
						$query = "$field:$query";
						break;
					case "go_name":
						$this->loadModel('GoTerm');
						$searchByNameResults = $this->GoTerm->getIdQueryByName($query);
						break;
					case "go_tree_name":
						$this->loadModel('GoTerm');
						$searchByNameResults = $this->GoTerm->getTreeQueryByName($query);
						break;
					case "blast_tree_name":
						$this->loadModel('Taxonomy');
						$searchByNameResults = $this->Taxonomy->getTreeQueryByName($query,'blast_tree');
						break;
					case "blast_species_name":
						$this->loadModel('Taxonomy');
						$searchByNameResults = $this->Taxonomy->getTreeQueryByName($query,'blast_tree','species');
						break;
					case "apis_tree_name":
						$this->loadModel('Taxonomy');
						$searchByNameResults = $this->Taxonomy->getTreeQueryByName($query,'apis_tree');
						break;
					case "apis_species_name":
						$this->loadModel('Taxonomy');
						$searchByNameResults = $this->Taxonomy->getTreeQueryByName($query,'apis_tree','species');
						break;
					case "hmm_name":
						$this->loadModel('Hmm');
						$searchByNameResults = $this->Hmm->getIdQueryByName($query);
						break;
					case "ec_name":
						$this->loadModel('Enzyme');
						$searchByNameResults = $this->Enzyme->getIdQueryByName($query);
						break;
					case "ko_name":
						$this->loadModel('KeggOrtholog');
						$searchByNameResults = $this->KeggOrtholog->getIdQueryByName($query);
						break;
					case "kegg_name":
						$this->loadModel('Pathway');
						$searchByNameResults = $this->Pathway->getEnzymeQueryByPathwayName($query,KEGG_PATHWAYS);
						break;
					case "kegg_tree_name":
						$this->loadModel('Pathway');
						$searchByNameResults = $this->Pathway->getKeggTreeQueryByPathwayName($query,KEGG_PATHWAYS_KO);
						break;
					case "kegg_tree_id":
						$this->loadModel('Pathway');
						$query = ltrim(str_replace('ko','',$query),'0');
						$searchByNameResults = $this->Pathway->getKeggTreeQueryByPathwayId($query,KEGG_PATHWAYS_KO);
						break;
					case "metacyc_name":
						$this->loadModel('Pathway');
						$searchByNameResults = $this->Pathway->getEnzymeQueryByPathwayName($query,METACYC_PATHWAYS);
						break;
					case "kegg_id":
						$this->loadModel('Pathway');
						$query = ltrim(str_replace('ko','',$query),'0');
						$searchByNameResults = $this->Pathway->getEnzymeQueryByPathwayId($query,KEGG_PATHWAYS);
						break;
					case "metacyc_id":
						$this->loadModel('Pathway');
						$searchByNameResults = $this->Pathway->getEnzymeQueryByPathwayId($query,METACYC_PATHWAYS);
						break;
					case "seq_id05":
						$this->loadModel('Project');	
						$sequence = preg_replace('/\s+/', '', $query);
						$searchByNameResults = $this->Blast->getSubjectIdQueryBySequence($dataset,$sequence,'1E-5');
						break;	
					case "seq_id10":
						$this->loadModel('Project');	
						$sequence = preg_replace('/\s+/', '', $query);					
						$searchByNameResults = $this->Blast->getSubjectIdQueryBySequence($dataset,$sequence,'1E-10');
						break;	
					case "seq_id50":
						$this->loadModel('Project');	
						$sequence = preg_replace('/\s+/', '', $query);				
						$searchByNameResults = $this->Blast->getSubjectIdQueryBySequence($dataset,$sequence,'1E-50');
						break;							
					case "cluster_name":
						$this->loadModel('Cluster');
						$searchByNameResults = $this->Cluster->getClusterQueryByName($query);
						break;
					default:
						$query = $this->Solr->escape($query);
						$query = "$field:$query";
				}

				if(!empty($searchByNameResults)) {
					$query = $searchByNameResults['query'];
					$hits  = $searchByNameResults['hits'];

					//check naming results
					if($hits == 0 ) {
						throw new NoNamesFoundQueryException();
					}
					else if($hits > MAX_NUM_QUERY_NAMES ) {
						throw new TooManyNamesFoundQueryException();
					}
					else{
						$this->Session->write('searchField',1);
						$this->Session->write('suggestions',$searchByNameResults['suggestions']);
					}
				}
			}

			//set search field to Lucene Query
			$this->Session->write('searchField',1);
		}
		//set default query and search field
		else {
			//set search field to Lucene Query
			$query = "*:*";
			$this->Session->write('searchField','com_name_txt');
		}

		return $query;
	}
	public function sortResultsByCounts($a, $b) { return strnatcmp($b['hits'], $a['hits']); }
	
}
class ShortQueryException extends Exception{
	public function errorMessage(){
		return SHORT_QUERY_EXCEPTION;
	}
};
class NoNamesFoundQueryException extends Exception{
	public function errorMessage(){
		return NO_NAMES_FOUND_QUERY_EXCEPTION;
	}
}
class TooManyNamesFoundQueryException extends Exception{
	public function errorMessage(){
		return TOO_MANY_NAMES_FOUND_QUERY_EXCEPTION;
	}
}
?>