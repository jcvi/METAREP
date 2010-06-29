<?php
/***********************************************************
*  File: search_controller.php
*  Description:
*
*  Author: jgoll
*  Date:   Feb 16, 2010
************************************************************/

#increase php dowload limits on space and time
ini_set('memory_limit','256M');
ini_set('max_execution_time','3000');

class SearchController extends AppController {
	var $name = 'Search';
	
	var $limit = 10;
	//top number of classification returned
	var $numFacetCounts=10;

	#FIXME define search field array
	#var $searchFields = array('common name','com_name_src','go_id','go_id_evidence','ec_id','ec_src','blast_species','e_value_exponent');

	var $helpers = array('LuceneResultPaginator','Facet','Tree','Ajax','Dialog');
	
	var $uses = array('Project','Population','Library');
	
	var $components = array('Session','RequestHandler','Solr','Format');

	//this function lets us search the lucene index, by default it returns the first page of all results (*|*)
	function index($dataset='CBAYVIR',$page=1,$sessionQueryId=null) {
		
		
		#search field options array
		$searchFields = array(1 => 'Combination of Fields',
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
			$searchFields['Optional Fields'] = $optionalFields;
						
		}		
		
		$this->Session->write('searchFields',$searchFields);
		
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
			
			//if user has entered a query other than the default query *:*
			if(!empty($this->data['Search']['query']) && $this->data['Search']['query'] !='*:*') {	
				//init result page			
				$page  = 1;
				
				//get query and field
				$query = $this->data['Search']['query'];
				$field = $this->data['Search']['field'];
				
				$fieldCount = substr_count($query, ':');
				
				if($fieldCount > 1) {					
					$this->Session->write('searchField',1);
				}
				else {
					//if query does not have the same field prefix
					if(!preg_match("/^$field\:/",$query)) {					
						$queryParts = explode(":",$query);		
													
						if(preg_match("/{[0-9]* TO \*}/",$query)) {
							$rangeParts = explode(" TO",$queryParts[count($queryParts)-1]);	
							$minRange = str_replace('{','',$rangeParts[0]);
							#debug($minRange);	
							$queryParts[count($queryParts)-1] = $minRange;
						}
						
						if($field === 'blast_evalue_exp' || $field === 'blast_pid' ||  $field === 'blast_cov') {
								$query = '{'.$queryParts[count($queryParts)-1].' TO *}'; 
						}
						else {
							$query = $queryParts[count($queryParts)-1];
						}	
						
						$query = $field.":".$query;		
						
						$this->Session->write('searchField',$field);
					}
				}
			}			
			//if user has not entered a query
			else {			
				$query = "*:*";
				$this->Session->write('searchField','com_name_txt');
			}
			$sessionQueryId = 'query_'.time();
			$this->Session->write($sessionQueryId,$query);
		}
	
		
		//specify facet default behaviour
		$solrArguments = array(	'fl' => 'peptide_id com_name com_name_src blast_species blast_evalue go_id go_src ec_id ec_src hmm_id',
						'facet' => 'true',
						'facet.field' => array('blast_species','com_name','go_id','ec_id','hmm_id'),
						'facet.mincount' => 1,
						"facet.limit" => $this->numFacetCounts);

		#handle exceptions
		try{
			$result = $this->Solr->search($dataset,$query, ($page-1)*$this->limit,$this->limit,$solrArguments,true);	
		}
		catch (Exception $e) {			
			$this->Session->setFlash("METAREP Lucene Query Exception. Please correct your query and try again.");
			$this->redirect(array('controller'=>'search','action' => 'index', $dataset));
		}
		
		$numHits= (int) $result->response->numFound;
		$facets = $result->facet_counts;
		$hits 	= $result->response->docs;
			
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
		$this->set('limit',$this->limit);		
	}

	public function clear($dataset,$sessionQueryId) {
		$this->Session->read($sessionQueryId);		
		$this->Session->write($sessionQueryId,'*:*');
		$this->set('projectName', $this->Project->getProjectName($dataset));
		$this->set('projectId', $this->Project->getProjectId($dataset));	
		$this->set('numHits',0);	
		$this->set('dataset',$dataset);
		$this->set('sessionQueryId',$sessionQueryId);
		$this->render('index');		
	}
	
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
}
?>
