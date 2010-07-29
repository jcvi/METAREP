<?php
/***********************************************************
* File: compare_controller.php
* Description: The Compare controller handles all
* multi-dataset comparisons. Choices are NCBI Taxonomy
* Gene Ontology terms, KEGG metabolic pathways, Enzyme 
* Classifcation, HMMs, and functional descriptions.
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

#define heatmap colors
define('HEATMAP_COLOR_YELLOW_RED', 0);
define('HEATMAP_COLOR_YELLOW_BLUE', 1);
define('HEATMAP_COLOR_BLUE', 2);
define('HEATMAP_COLOR_GREEN', 3);

#define comparative options
define('FISHER', 0);
define('ABSOLUTE_COUNTS', 1);
define('RELATIVE_COUNTS', 2);
define('HEATMAP', 3);
define('CHISQUARE', 4);
define('WILCOXON', 5);
define('METASTATS', 6);
define('COMPLETE_LINKAGE_CLUSTER_PLOT', 7);
define('AVERAGE_LINKAGE_CLUSTER_PLOT', 8);
define('SINGLE_LINKAGE_CLUSTER_PLOT', 9);
define('WARDS_CLUSTER_PLOT', 10);
define('MEDIAN_CLUSTER_PLOT', 11);
define('MCQUITTY_CLUSTER_PLOT', 12);
define('CENTROID_CLUSTER_PLOT', 13);
define('MDS_PLOT', 14);
define('HEATMAP_PLOT', 15);


define('SHOW_ALL_DATASETS',0);
define('SHOW_PROJECT_DATASETS',1);

class CompareController extends AppController {

	var $name 		= 'Compare';	
	var $helpers 	= array('Matrix','Dialog','Ajax');
	var $uses 		= array('Project','Taxonomy','GoGraph','Enzymes','Hmm','Library','Population','Pathway','EnvironmentalLibrary');
	var $components = array('Solr','RequestHandler','Session','Matrix','Format');

	/**
	 * Initializes index compare page
	 * 
	 * @param String $dataset Initial dataset to compare others against
	 * @return void
	 * @access public
	 */			
	function index($dataset = null,$mode = SHOW_PROJECT_DATASETS) {
		
		//increase memory size
		ini_set('memory_limit', '856M');
				
		$this->pageTitle = 'Compare Multiple Datasets';

		if(!$this->Session->check('filter')) {
			$this->Session->write('filter',"*:*");
		}	
		if(!$this->Session->check('minCount')) {
			$this->Session->write('minCount',0);
		}	
		if(!$this->Session->check('option')) {
			$this->Session->write('option',ABSOLUTE_COUNTS);
		}	

		$projectId = $this->Project->getProjectId($dataset);
		
		$selectedDatasets = array($dataset);
		
		if($mode == SHOW_ALL_DATASETS) {
			$allDatasets = $this->Project->findUserDatasetsCompareFormat(POPULATION_AND_LIBRARY_DATASETS);
		}
		else if($mode == SHOW_PROJECT_DATASETS) {
			$allDatasets = $this->Project->findUserDatasetsCompareFormat(POPULATION_AND_LIBRARY_DATASETS,$projectId);
		}
		
		$this->Session->write('allDatasets',$allDatasets);
		
		$this->set('projectId', $projectId);
		$this->set('selectedDatasets', $selectedDatasets);
		$this->set('dataset', $dataset);	
		$this->set('mode', $mode);		
	}
	
	/**
	 * Function activates the tab panel though an ajax call. The function is executed via an ajax call 
	 * when the use clicks on the update button on the compare index page
	 * 
	 * @return void
	 * @access public
	 */		
	function ajaxTabPanel() {
			
		#get compare form data
		$option				= $this->data['Compare']['option'];
		$minCount 			= $this->data['Compare']['minCount'];
		$filter				= $this->data['Compare']['filter'];

		if(isset($this->data['selectedDatasets'])) {						
			$selectedDatasets	= $this->data['selectedDatasets'];
			$optionalDatatypes  = $this->Project->checkOptionalDatatypes($selectedDatasets);

			//core data types			
			$tabs = array(array('function'=>'blastTaxonomy','isActive'=>1,'tabName' => 'Taxonomy (Blast)','dbTable' => 'Taxonomy','sorlField' => 'blast_tree'),
						  array('function'=>'geneOntology','isActive'=>1,'tabName' => 'Gene Ontology','dbTable' => 'GeneOntology','sorlField' => 'go_tree'),
						  array('function'=>'pathways','isActive'=>1,'tabName' => 'Pathways','dbTable' => 'Pathways','sorlField' => 'ec_id'),
						  array('function'=>'enzymes','isActive'=>1,'tabName' => 'Enzymes','dbTable' => 'Enzymes','sorlField' => 'enzyme_id'),
						  array('function'=>'hmms','isActive'=>1,'tabName' => 'HMMs','dbTable' => 'Hmm','sorlField' => 'hmm_id'),
						  array('function'=>'commonNames','isActive'=>1,'tabName' => 'Common Names','dbTable' => null,'sorlField' => 'com_name'));

			//set optional data types for JCVI-only installation					  
			if(JCVI_INSTALLATION) {			  							  
				if($optionalDatatypes['apis']) {
					array_push($tabs,array('function'=>'apisTaxonomy','isActive'=>1,'tabName' => 'Taxonomy (Apis)','dbTable' => 'Taxonomy','sorlField' => 'apis_tree'));
				}
				else {
					array_push($tabs,array('function'=>'apisTaxonomy','isActive'=>0,'tabName' => 'Taxonomy (Apis)','dbTable' => 'Taxonomy','sorlField' => 'apis_tree'));
				}	
				if($optionalDatatypes['clusters']) {
					array_push($tabs,array('function'=>'clusters','isActive'=>1,'tabName' => 'Clusters','dbTable' => null,'sorlField' => 'cluster_id'));
				}	
				else {
					array_push($tabs,array('function'=>'clusters','isActive'=>0,'tabName' => 'Clusters','dbTable' => null,'sorlField' => 'cluster_id'));
				}		
				if($optionalDatatypes['viral']) {
					array_push($tabs,array('function'=>'environmentalLibraries','isActive'=>1,'tabName' => 'Environmental Libraries','dbTable' => 'environemental_libraries','sorlField' => 'env_lib'));
				}					  
				else {
					array_push($tabs,array('function'=>'environmentalLibraries','isActive'=>0,'tabName' => 'Environmental Libraries','dbTable' => 'environemental_libraries','sorlField' => 'env_lib'));				
				}						  
			}
			
			#set default variables
			if(empty($option)) {
				debug('test');
				$option = ABSOLUTE_COUNTS;
			}
			if(empty($filter)) {
				$filter = 	"*:*";
			}
			if(empty($minCount)) {
				$minCount = 0;
			}
			#handle tests
			if($option == CHISQUARE) {
				$minCount = 5;
			}
			else if($option == METASTATS || $option == WILCOXON) {
				#handle metastats exception (fewer than 3 datasets)				
				if(count($selectedDatasets) != 2 || !$optionalDatatypes['population']) {
					$this->set('multiSelectException','Please select 2 populations for this statistical test.');
					$this->set('filter',$filter);
					$this->render('/compare/result_panel','ajax');
				}								
			}			
				
			$heatMapColor = HEATMAP_COLOR_YELLOW_RED;
			
			#handle plot exception (fewer than 3 datasets)
			if(count($selectedDatasets) < 3 && $option > 6 ) {				
				$this->set('multiSelectException','Please select at least 3 datasets for this plot option.');
				$this->set('filter',$filter);
				$this->render('/compare/result_panel','ajax');
			}		
			
			//write variables to sessions				
			$this->Session->write('option',$option);
			$this->Session->write('minCount',$minCount);
			$this->Session->write('filter',$filter);
			$this->Session->write('selectedDatasets',$selectedDatasets);
			$this->Session->write('optionalDatatypes',$optionalDatatypes);
			$this->Session->write('tabs',$tabs);
			$this->Session->write('flipAxis',0);
			$this->Session->write('heatmapColor',$heatMapColor);		
			$this->render('/compare/tab_panel','ajax');
		}
		else {
			#handle select datasets exception
			$this->set('multiSelectException','Please select a dataset.');
			$this->set('filter',$filter);
			$this->render('/compare/result_panel','ajax');
		}
	}

	/**
	 * Compare NCBI taxonomic assignments assigned by Blast
	 * 
	 * @return void
	 * @access private
	 */	
	public function blastTaxonomy() {
		$this->taxonomy('blast_tree');
	}

	/**
	 * Compare NCBI taxonomic assignments assigned by Apis
	 * 
	 * @return void
	 * @access private
	 */	
	public function apisTaxonomy() {
		$this->taxonomy('apis_tree');
	}	

	/**
	 * Compare NCBI taxonomic assignments across selected datasets
	 * 
	 * @param String $facetField 	equals blast_tree for Blast taxonomic assignments
	 * 								or apis_tree for Apis taxonomic assignments
	 * @return void
	 * @access private
	 */	
	private function taxonomy($facetField = 'blast_tree') {
		
		if($facetField === 'blast_tree') {
			$mode = 'blastTaxonomy';
		}
		if($facetField === 'apis_tree') {
			$mode = 'apisTaxonomy';
		}		
		
		$counts	= array();
		$level  = 'root';
		
		#read post data
		if(!empty($this->data['Post']['level'])) {
			$level = $this->data['Post']['level'];
		}
		else {
			if($this->Session->check("$mode.level")) {
				$level = $this->Session->read("$mode.level");
			}	
		}

		$levels = array(
						'root' 		=> 'root',
						'kingdom' 	=> 'kingdom',
						'class' 	=> 'class',
						'phylum' 	=> 'phylum',
						'order' 	=> 'order',
						'family' 	=> 'family',
						'genus' 	=> 'genus',
						);
						
		#read session variables
		$option 			= $this->Session->read('option');
		$minCount 			= $this->Session->read('minCount');
		$filter 			= $this->Session->read('filter');
		$selectedDatasets	= $this->Session->read('selectedDatasets');
		
		if($option == METASTATS || $option == WILCOXON) {
			#split the two populations into their libraries; store population 
			#names and start position of secontd population
			$this->Session->write('comparePopulations',$selectedDatasets);
			$librariesA = $this->Population->getLibraries($selectedDatasets[0]);
			$librariesB = $this->Population->getLibraries($selectedDatasets[1]);
			
			$selectedDatasets = array_merge($librariesA,$librariesB);
			$this->Session->write('compareStartSecondPopulation',count($librariesA)+1);
		}		
		
		#write session variables
		$this->Session->write('mode',$mode);
		
		
		$this->Session->write("$mode.level",$level);
		$this->Session->write('levels',$levels);
			
		$facetQueries = array();

		if($level === 'root') {
			$taxonResults = $this->Taxonomy->findTopLevelTaxons();
		}
		else {
			$taxonResults = $this->Taxonomy->find('all', array('conditions' => array('Taxonomy.rank' => $level),'fields' => array('taxon_id','name')));
		}
		
		//set up count matrix
		foreach($taxonResults as $taxonResult) {
			$id = $taxonResult['Taxonomy']['taxon_id'];
			$name = $taxonResult['Taxonomy']['name'];
			$counts[$id]['name'] 	= $name;	
			$counts[$id]['sum'] 	= 0;	
			array_push($facetQueries,"$facetField:$id");			
		}
		
		unset($taxonResults);
		
	
		
		////populate count matrix with solr facet counts using solr's filter query
		foreach($selectedDatasets as $dataset) {
			
			$facetQueryChunks = array_chunk($facetQueries,6700);
			
			foreach($facetQueryChunks as $facetQueryChunk) {
				
					//specify facet behaviour (fetch all facets)
					$solrArguments = array(	"facet" => "true",
					'facet.mincount' => $minCount,
					'facet.query' => $facetQueryChunk,
					"facet.limit" => -1);	
					try	{
						$result 	  = $this->Solr->search($dataset,$filter,0,0,$solrArguments);
						
					}
					catch(Exception $e){
						$this->set('exception',SOLR_CONNECT_EXCEPTION);
						$this->render('/compare/result_panel','ajax');
					}
				
				$facets = $result->facet_counts->facet_queries;
				
				foreach($facets as $facetQuery =>$count) {
					$tmp 	= explode(":", $facetQuery);
					$id 	= $tmp[1];	
					
					$counts[$id][$dataset] = $count;	
					$counts[$id]['sum'] += $count;
				}
			}
		}
		
		$this->Matrix->formatCounts($option,$filter,$minCount,$selectedDatasets,$counts);
		
		#debug($counts);
		
		$this->Session->write('counts',$counts);
		
		$this->render('/compare/result_panel','ajax');
	}
	
	/**
	 * Compare Gene Ontology terms across selected datasets
	 * 
	 * @return void
	 * @access public
	 */
	function geneOntology() {
		$mode 			= __FUNCTION__;
		$counts			= array();
		$subontology 	= 'universal';
		$ancestor  		= 'all';
		$levelLabel 	= 1;
		$level			= 1;

		//drop down menu information
		$levels = array(1 =>'root','Molecular Function' =>
		array('MF2' => 'Molecular Function Root Distance 2',
										'MF3' => 'Molecular Function Root Distance 3',
										'MF4' => 'Molecular Function Root Distance 4',
										'MF5' => 'Molecular Function Root Distance 5'),
							'Cellular Component' =>
		array('CC2' => 'Cellular Component Root Distance 2',
										'CC3' => 'Cellular Component Root Distance 3',
										'CC4' => 'Cellular Component Root Distance 4',
										'CC5' => 'Cellular Component Root Distance 5'),
							'Biological Process' =>
		array('BP2' => 'Biological Process Root Distance 2',
										'BP3' => 'Biological Process Root Distance 3',
										'BP4' => 'Biological Process Root Distance 4',
										'BP5' => 'Biological Process Root Distance 5')									
		);

		#read post data
		if(!empty($this->data['Post']['level'])) {				
			//adjust level argument to match differenc subontologies
			$levelLabel = $this->data['Post']['level'];
		}
		else {
			if($this->Session->check("$mode.level")) {
				$levelLabel = $this->Session->read("$mode.level");
			}	
		}
			
		if(substr($levelLabel,0,2)=== 'MF') {
			$subontology = 'molecular_function';
			$ancestor = 'GO:0003674';
		}
		else if(substr($levelLabel,0,2) === 'CC') {
			$subontology = 'cellular_component';
			$ancestor = 'GO:0005575';

		}
		else if(substr($levelLabel,0,2) === 'BP') {
			$subontology = 'biological_process';
			$ancestor = 'GO:0008150';
				
		}
		$level = substr($levelLabel,-1,1);
		
		#read session variables
		$option 			= $this->Session->read('option');
		$minCount 			= $this->Session->read('minCount');
		$filter 			= $this->Session->read('filter');
		$selectedDatasets	= $this->Session->read('selectedDatasets');

		if($option == METASTATS || $option == WILCOXON) {
			#split the two populations into their libraries; store population 
			#names and start position of secontd population
			$this->Session->write('comparePopulations',$selectedDatasets);
			$librariesA = $this->Population->getLibraries($selectedDatasets[0]);
			$librariesB = $this->Population->getLibraries($selectedDatasets[1]);
			$selectedDatasets = array_merge($librariesA,$librariesB);
			$this->Session->write('compareStartSecondPopulation',count($librariesA)+1);		
		}			
		
		#write session variables
		$this->Session->write('levels',$levels);
		$this->Session->write('mode',$mode);
		$this->Session->write("$mode.level", $levelLabel);

		#datastructure of dataset names and ids
		$goChildren = $this->GoGraph->find('all', array('fields' => array('Descendant.acc','Descendant.name'),'conditions' => array('Ancestor.acc' => $ancestor,'distance'=>$level, 'Ancestor.term_type'=>$subontology,'Descendant.is_obsolete'=>0)));

		$facetQueries = array();
		
		//set up count matrix
		foreach($goChildren as $goChild) {
				
			//init taxon information
			$goAcc	 = $goChild['Descendant']['acc'];
			$category= $goChild['Descendant']['acc'];
			$tmp  = split("\\:",$goAcc);
			$category = ltrim($tmp[1], "0");
			
			$counts[$category]['name'] = $goChild['Descendant']['name'];
			$counts[$category]['sum'] = 0;
				
			
			array_push($facetQueries,"go_tree:$category");	
		}
		
		
		unset($goChildren);
		
		//specify facet behaviour (fetch all facets)
		$solrArguments = array(	"facet" => "true",
		'facet.mincount' => $minCount,
		'facet.query' => $facetQueries,
		"facet.limit" => -1);				
			
		
			////populate count matrix with solr facet counts using solr's filter query
		foreach($selectedDatasets as $dataset) {
			try	{
				$result 	  = $this->Solr->search($dataset,$filter,0,0,$solrArguments);				
			}
			catch(Exception $e){
				$this->set('exception',SOLR_CONNECT_EXCEPTION);
				$this->render('/compare/result_panel','ajax');
			}
			
			#debug($result->facet_counts->facet_queries);
			
			$facets = $result->facet_counts->facet_queries;
			
			foreach($facets as $facetQuery =>$count) {
				$tmp 	= explode(":", $facetQuery);
				$id 	= $tmp[1];	
				
				$counts[$id][$dataset] = $count;	
				$counts[$id]['sum'] += $count;
			}
		}
		
		$this->Matrix->formatCounts($option,$filter,$minCount,$selectedDatasets,$counts);


		$this->Session->write('counts',$counts);


		#reset label to alpha numeric version to allow selection of drop down
		$this->set(compact('mode','counts','filter','option','minCount','selectedDatasets','level','levels','test'));
		$this->render('/compare/result_panel','ajax');
	}
	
	/**
	 * Compare enzymes across selected datasets
	 * 
	 * @return void
	 * @access public
	 */
	function enzymes() {
		$mode = __FUNCTION__;
		$counts	= array();
		$level	='level 1';

		$levels = array(
		'level 1' => 'Enzyme Commission Level 1',
		'level 2' => 'Enzyme Commission Level 2',
		'level 3' => 'Enzyme Commission Level 3',
		'level 4' => 'Enzyme Commission Level 4'
		);
			
		#read post data
		if(!empty($this->data['Post']['level'])) {
			$level = $this->data['Post']['level'];
		}
		else {
			if($this->Session->check("$mode.level")) {
				$level = $this->Session->read("$mode.level");
			}	
		}

		#read session variables
		$option 			= $this->Session->read('option');
		$minCount 			= $this->Session->read('minCount');
		$filter 			= $this->Session->read('filter');
		$selectedDatasets	= $this->Session->read('selectedDatasets');

		if($option == METASTATS || $option == WILCOXON) {
			#split the two populations into their libraries; store population 
			#names and start position of secontd population
			$this->Session->write('comparePopulations',$selectedDatasets);
			$librariesA = $this->Population->getLibraries($selectedDatasets[0]);
			$librariesB = $this->Population->getLibraries($selectedDatasets[1]);
			$selectedDatasets = array_merge($librariesA,$librariesB);
			$this->Session->write('compareStartSecondPopulation',count($librariesA)+1);
		}	

		#write session variables
		$this->Session->write('levels',$levels);
		$this->Session->write('mode',$mode);
		$this->Session->write("$mode.level", $level);		
		

		$facetQueries = array() ;
		
		#datastructure of dataset names and ids
		$enzymeResults 	= $this->Enzymes->find('all', array('conditions' => array('Enzymes.rank' => $level)));
		
			//set up count matrix
		foreach($enzymeResults as $enzymeResult) {

			$ecId = $enzymeResult['Enzymes']['ec_id'];
			
			//add fuzzy matching to handle higher level enzyme classifications, 
			//e.g. 1.3.-.- becomes 1.3.*.*
			$solrEcId = str_replace("-","*",$ecId);
			array_push($facetQueries,"ec_id:$solrEcId");	
											
			$name 	= $enzymeResult['Enzymes']['name'];

			//init count data
			$counts[$ecId]['name'] 	= $name;	
			$counts[$ecId]['sum'] 	= 0;	
		}
		
		
		unset($enzymeResults);
		
		//specify facet behaviour (fetch all facets)
		$solrArguments = array(	"facet" => "true",
		'facet.mincount' => $minCount,
		'facet.query' => $facetQueries,
		"facet.limit" => -1);		
		
		////populate count matrix with solr facet counts using solr's filter query
		foreach($selectedDatasets as $dataset) {
			try	{
				$result 	  = $this->Solr->search($dataset,$filter,0,0,$solrArguments);				
			}
			catch(Exception $e){
				$this->set('exception',SOLR_CONNECT_EXCEPTION);
				$this->render('/compare/result_panel','ajax');
			}
			
			$facets = $result->facet_counts->facet_queries;
			
			foreach($facets as $facetQuery =>$count) {
				$tmp 	= explode(":", $facetQuery);
				$id 	= str_replace('*','-',$tmp[1]);	
				$counts[$id][$dataset] = $count;	
				$counts[$id]['sum'] += $count;
			}
		}

		$this->Matrix->formatCounts($option,$filter,$minCount,$selectedDatasets,$counts);
			

		$this->Session->write('counts',$counts);

		$this->render('/compare/result_panel','ajax');
	}
	
	/**
	 * Compare HMMs across selected datasets
	 * 
	 * @return void
	 * @access public
	 */
	function hmms() {
		$mode = __FUNCTION__;
		$counts	= array();
		$level	='TIGR';

		//drop down selection
		$levels = array(
		'TIGR' => 'TIGRFam',
		'PF'   => 'Pfam'
		);
			
		#read post data
		if(!empty($this->data['Post']['level'])) {
			$level = $this->data['Post']['level'];
		}
		else {
			if($this->Session->check("$mode.level")) {
				$level = $this->Session->read("$mode.level");
			}	
		}

		#read session variables
		$option 			= $this->Session->read('option');
		$minCount 			= $this->Session->read('minCount');
		$filter 			= $this->Session->read('filter');
		$selectedDatasets	= $this->Session->read('selectedDatasets');

		if($option == METASTATS || $option == WILCOXON) {
			#split the two populations into their libraries; store population 
			#names and start position of secontd population
			$this->Session->write('comparePopulations',$selectedDatasets);
			$librariesA = $this->Population->getLibraries($selectedDatasets[0]);
			$librariesB = $this->Population->getLibraries($selectedDatasets[1]);
			$selectedDatasets = array_merge($librariesA,$librariesB);
			$this->Session->write('compareStartSecondPopulation',count($librariesA)+1);
		}			
		
		#write session variables
		$this->Session->write("$mode.level", $level);
		$this->Session->write('levels',$levels);
		$this->Session->write('mode',$mode);

		//fetch all models
		$hmmResults = $this->Hmm->find('all', array('conditions' => array('model' => $level),'fields' => array('acc','name')));

		//set up count matrix
		foreach($hmmResults as $hmmResult) {
				
			//init hmm information
			$category = $hmmResult['Hmm']['acc'];
			$counts[$category]['name'] = $hmmResult['Hmm']['name'] ;
			$counts[$category]['sum'] = 0;
				
			//init counts
			foreach($selectedDatasets as $dataset) {
				$counts[$category][$dataset]=0;
			}
		}

		//specify facet behaviour (fetch all facets)
		$solrArguments = array(	"facet" => "true",
		'facet.field' => array('hmm_id'),
		'facet.mincount' => $minCount,
		'facet.prefix' => $level,
		"facet.limit" => -1);

		//populate count matrix with solr facet counts
		foreach($selectedDatasets as $dataset) {
			try {
				$result = $this->Solr->search($dataset,$filter, 0,0,$solrArguments);
			}
			catch(Exception $e){
				$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
				$this->redirect('/projects/index');
			}
				
			$facets = $result->facet_counts->facet_fields->hmm_id;
				
			foreach($facets as $category => $count) {
				$counts[$category][$dataset] = $count;
				$counts[$category]['sum'] += $count;
			}
				
		}

		$this->Matrix->formatCounts($option,$filter,$minCount,$selectedDatasets,$counts);

		$this->Session->write('counts',$counts);

		$this->render('/compare/result_panel','ajax');
	}

	/**
	 * Compare clusters across selected datasets
	 * 
	 * @return void
	 * @access public
	 */	
	function clusters() {
		$mode   = __FUNCTION__;
		$counts	= array();
		$level	='CAM_CR';

		//drop down selection
		$levels = array(
		'CAM_CR' => 'Core Clusters',
		'CAM_CL'   => 'Final Clusters'
		);
			
		//get post data
		if(!empty($this->data['Post']['level'])) {
			$level = $this->data['Post']['level'];
		}
		else {
			if($this->Session->check("$mode.level")) {
				$level = $this->Session->read("$mode.level");
			}	
		}

		#read session variables
		$option 			= $this->Session->read('option');
		$minCount 			= $this->Session->read('minCount');
		$filter 			= $this->Session->read('filter');
		$selectedDatasets	= $this->Session->read('selectedDatasets');

		if($option == METASTATS || $option == WILCOXON) {
			#split the two populations into their libraries; store population 
			#names and start position of secontd population
			$this->Session->write('comparePopulations',$selectedDatasets);
			$librariesA = $this->Population->getLibraries($selectedDatasets[0]);
			$librariesB = $this->Population->getLibraries($selectedDatasets[1]);
			$selectedDatasets = array_merge($librariesA,$librariesB);
			$this->Session->write('compareStartSecondPopulation',count($librariesA)+1);
		}			
		
		if($minCount==0) {
			$minCount=2;
		}

		#write session variables
		$this->Session->write("$mode.level", $level);
		$this->Session->write('levels',$levels);
		$this->Session->write('mode',$mode);

		//specify facet behaviour (fetch all facets)
		$solrArguments = array(	"facet" => "true",
		'facet.field' => array('cluster_id'),
		'facet.mincount' => $minCount,
		'facet.prefix' => $level,
		"facet.limit" => -1);

		//populate count matrix with solr facet counts
		foreach($selectedDatasets as $dataset) {
			try {
				$result = $this->Solr->search($dataset,$filter, 0,0,$solrArguments);
			}
			catch(Exception $e){
				$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
				$this->redirect('/projects/index');
			}
				
			$facets = $result->facet_counts->facet_fields->cluster_id;
				
			foreach($facets as $category => $count) {

				$counts[$category]['name'] =$category;
				$counts[$category][$dataset] = $count;

				if(!empty($counts[$category]['sum'])) {
					$counts[$category]['sum'] += $count;
				}
				else {
					$counts[$category]['sum'] = $count;
				}
			}
		}

		//populate missing slots
		foreach($counts as $category => $value) {
			foreach($selectedDatasets as $dataset) {
				if(empty($counts[$category][$dataset])) {
					$counts[$category][$dataset]=0;
				}
			}
		}

		$this->Matrix->formatCounts($option,$filter,$minCount,$selectedDatasets,$counts);

		$this->Session->write('counts',$counts);

		$this->render('/compare/result_panel','ajax');
	}
	
	/**
	 * Compare environmental libraries across selected datasets
	 * 
	 * @return void
	 * @access public
	 */
	function environmentalLibraries() {
		$mode   = __FUNCTION__;
		$counts	= array();
		
		$level	='level1';
		
		//drop down selection
		$levels = array(
						'level1' => 'Level 1',					
						'level2'  => 'Level 2',
		);
	
			//get post data
		if(!empty($this->data['Post']['level'])) {
			$level = $this->data['Post']['level'];
		}
		else {
			if($this->Session->check("$mode.level")) {
				$level = $this->Session->read("$mode.level");
			}	
		}		
		
		
		$facetQueries = array();

		

		$taxonResults = $this->EnvironmentalLibrary->find('all',array('conditions'=>array('rank'=>$level)));
		
		//set up count matrix
		foreach($taxonResults as $taxonResult) {			
			$name = $taxonResult['EnvironmentalLibrary']['name'];
			$counts[$name]['name'] 	= '';	
			$counts[$name]['sum'] 	= 0;

			#escape lucene special characters
			$name = str_replace(' ','?',$name);
			$name = str_replace('(','\(',$name);
			$name = str_replace(')','\)',$name);
			$name = str_replace('-','\-',$name);

			array_push($facetQueries,"env_lib:*$name*");			
		}
	
		unset($taxonResults);


		#read session variables
		$option 			= $this->Session->read('option');
		$minCount 			= $this->Session->read('minCount');
		$filter 			= $this->Session->read('filter');
		$selectedDatasets	= $this->Session->read('selectedDatasets');		

		if($option == METASTATS || $option == WILCOXON) {
			#split the two populations into their libraries; store population 
			#names and start position of secontd population
			$this->Session->write('comparePopulations',$selectedDatasets);
			$librariesA = $this->Population->getLibraries($selectedDatasets[0]);
			$librariesB = $this->Population->getLibraries($selectedDatasets[1]);
			$selectedDatasets = array_merge($librariesA,$librariesB);
			$this->Session->write('compareStartSecondPopulation',count($librariesA)+1);
		}			
		
		//specify facet behaviour (fetch all facets)
		$solrArguments = array(	"facet" => "true",
		'facet.mincount' => $minCount,
		'facet.query' => $facetQueries,
		"facet.limit" => -1);		
		
		////populate count matrix with solr facet counts using solr's filter query
		foreach($selectedDatasets as $dataset) {

			try	{
				$result 	  = $this->Solr->search($dataset,$filter,0,0,$solrArguments);
				#debug($result);
			}	
			catch(Exception $e){
				$this->set('exception',SOLR_CONNECT_EXCEPTION);
				$this->render('/compare/result_panel','ajax');
			}
			
			$facets = $result->facet_counts->facet_queries;
			
			foreach($facets as $facetQuery =>$count) {
				$tmp 	= explode(":", $facetQuery);
				$category 	= str_replace('*','',$tmp[1]);	
				$category 	= str_replace('?',' ',$category);
				$category 	= str_replace('\\','',$category);	
				$counts[$category]['name'] = $category;
				$counts[$category][$dataset] = $count;	
				
				if(!empty($counts[$category]['sum'])) {
					$counts[$category]['sum'] += $count;
				}
				else {
					$counts[$category]['sum'] = $count;
				}
			}
		}		
		
		#write session variables
		$this->Session->write("$mode.level", $level);
		$this->Session->write('levels',$levels);
		$this->Session->write('mode',$mode);

		#debug($counts);
		
		$this->Matrix->formatCounts($option,$filter,$minCount,$selectedDatasets,$counts);

		$this->Session->write('counts',$counts);

		$this->render('/compare/result_panel','ajax');
	}
	
	/**
	 * Compare pathways across selected datasets
	 * 
	 * @return void
	 * @access public
	 */
	function pathways() {
		$mode   = __FUNCTION__;
		$counts	= array();

		$level	='level 2';

		//drop down selection
		$levels = array(
			'level 2' => 'Metabolic Pathways (level 2)',	
			'level 3' => 'Metabolic Pathways (level 3)',				
		);

		#read post data
		if(!empty($this->data['Post']['level'])) {
			$level = $this->data['Post']['level'];
		}
		else {
			if($this->Session->check("$mode.level")) {
				$level = $this->Session->read("$mode.level");
			}	
		}
		
		#read session variables
		$option 			= $this->Session->read('option');
		$minCount 			= $this->Session->read('minCount');
		$filter 			= $this->Session->read('filter');
		$selectedDatasets	= $this->Session->read('selectedDatasets');

		if($option == METASTATS || $option == WILCOXON) {
			#split the two populations into their libraries; store population 
			#names and start position of second population
			$this->Session->write('comparePopulations',$selectedDatasets);
			$librariesA = $this->Population->getLibraries($selectedDatasets[0]);
			$librariesB = $this->Population->getLibraries($selectedDatasets[1]);
			$selectedDatasets = array_merge($librariesA,$librariesB);
			$this->Session->write('compareStartSecondPopulation',count($librariesA)+1);
		}			
				
		#write session variables
		$this->Session->write("$mode.level", $level);
		$this->Session->write('levels',$levels);
		$this->Session->write('mode',$mode);

		$solrArguments = array(	"facet" => "true",
						'facet.field' => array('ec_id'),
						'facet.mincount' => $minCount,
						"facet.limit" => -1);
			
		//populate count matrix with solr facet counts
		$pathways  = $this->Pathway->find('all', array('conditions' => array('Pathway.level' => $level)));
		
		foreach($pathways as $pathway) {
			
			#init pathway information
			$id 	= $pathway['Pathway']['id'];
			$keggId = str_pad($pathway['Pathway']['kegg_id'],5,0,STR_PAD_LEFT);
			
			$name 	= $pathway['Pathway']['name'];			
			$counts[$keggId]['name'] = $name;
			$counts[$keggId]['sum']  = 0;	
		
			foreach($selectedDatasets as $dataset) {				
				try {
					$count= $this->Solr->getPathwayCount($filter,$dataset,$level,$id,0,null);

					$counts[$keggId][$dataset] = $count;
					$counts[$keggId]['sum'] += $count;
					
				}
				catch(Exception $e){
					$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
					$this->render('/compare/result_panel','ajax');
				}
			}
		}

		$this->Matrix->formatCounts($option,$filter,$minCount,$selectedDatasets,$counts);

		$this->Session->write('counts',$counts);

		$this->render('/compare/result_panel','ajax');
	}
	
	/**
	 * Compare common names across selected datasets
	 * 
	 * @return void
	 * @access public
	 */	
	function commonNames() {
		$mode   = __FUNCTION__;
		$counts = array();
		$level	=10;
		$levels = array('10' => 'Top 10 Hits',
		'20' => 'Top 20 Hits',
		'50' => 'Top 50 Hits',
		'100' => 'Top 100 Hits',
		'1000' => 'Top 1000 Hits'
		);

			//get post data
		if(!empty($this->data['Post']['level'])) {
			$level = $this->data['Post']['level'];
		}
		else {
			if($this->Session->check("$mode.level")) {
				$level = $this->Session->read("$mode.level");
			}	
		}

		#read session variables
		$option 			= $this->Session->read('option');#ylab="Datasets"
		$minCount 			= $this->Session->read('minCount');
		$filter 			= $this->Session->read('filter');
		$selectedDatasets	= $this->Session->read('selectedDatasets');

		if($option == METASTATS || $option == WILCOXON) {
			#split the two populations into their libraries; store population 
			#names and start position of secontd population
			$this->Session->write('comparePopulations',$selectedDatasets);
			$librariesA = $this->Population->getLibraries($selectedDatasets[0]);
			$librariesB = $this->Population->getLibraries($selectedDatasets[1]);
			$selectedDatasets = array_merge($librariesA,$librariesB);
			$this->Session->write('compareStartSecondPopulation',count($librariesA)+1);	
		}			
		
		#write session variables
		$this->Session->write("$mode.level", $level);
		$this->Session->write('levels',$levels);
		$this->Session->write('mode',$mode);


		//specify facet default behaviour
		$facet = array(	"facet" => "true",
			'facet.field' => array('com_name'),
			'facet.mincount' => $minCount,
			'facet.limit' => $level);

		//populate count matrix with solr facet counts
		foreach($selectedDatasets as $dataset) {

			try {
				$result = $this->Solr->search($dataset,$filter, 0,0,$facet);
			}
			catch(Exception $e){
				$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
				$this->redirect('/projects/index');
			}
				
			$facets = $result->facet_counts->facet_fields->com_name;

			foreach($facets as $category => $count) {
				$counts[$category]['name'] = $category;
				$counts[$category][$dataset] = $count;

				if(!empty($counts[$category]['sum'])) {
					$counts[$category]['sum'] += $count;
				}
				else {
					$counts[$category]['sum'] = $count;
				}
			}
				
		}

		//populate missing slots
		foreach($counts as $category => $value) {
			foreach($selectedDatasets as $dataset) {
				if(empty($counts[$category][$dataset])) {
					$counts[$category][$dataset]=0;
				}
			}
		}

		$this->Matrix->formatCounts($option,$filter,$minCount,$selectedDatasets,$counts);
		
		$this->Session->write('counts',$counts);

		$this->render('/compare/result_panel','ajax');
	}
	
	/**
	 * Opens download dialog to export the current compare result matrix
	 * 
	 * @return void
	 * @access public
	 */	
	function download() {

		$this->autoRender=false;

		$option 			= $this->Session->read('option');
		$minCount 			= $this->Session->read('minCount');
		$filter 			= $this->Session->read('filter');
		$selectedDatasets	= $this->Session->read('selectedDatasets');
		$counts				= $this->Session->read('counts');

		if($option == CHISQUARE) {
			$title = 'Comparison Results - Chi-Square Test of Independence';
			$content = $this->Format->infoString($title,$selectedDatasets,$filter,null);
			$content.= $this->Format->comparisonResultsToDownloadString($counts,$selectedDatasets,$option);
		}
		elseif($option == FISHER) {			
			$title = "Comparison Results - Fisher's Exact Test";
			$content = $this->Format->infoString($title,$selectedDatasets,$filter,null);
			$content.= $this->Format->comparisonResultsToDownloadString($counts,$selectedDatasets,$option);
		}
		elseif($option == METASTATS) {
			$title = "Comparison Results - METASTATS non-parametric t-test";
			$content = $this->Format->infoString($title,$selectedDatasets,$filter,null);
			$content.= $this->Format->metatstatsResultsToDonwloadString($counts,$selectedDatasets);
		}	
		elseif($option == WILCOXON) {
			$title = "Comparison Results - Wilcoxon Signed Rank Test";
			$content = $this->Format->infoString($title,$selectedDatasets,$filter,null);
			$content.= $this->Format->wilcoxonResultsToDonwloadString($counts,$selectedDatasets);
		}	
		//plot options
		elseif($option > 6) {
			$title = "Comparison Results - Euclidean Distance Matrix";
			$content = $this->Format->infoString($title,$selectedDatasets,$filter,null);
			$content .= $this->Session->read('distantMatrices');		
		}				
		else{
			$title = "Comparison Results";
			$content = $this->Format->infoString($title,$selectedDatasets,$filter,null);
			$content.= $this->Format->comparisonResultsToDownloadString($counts,$selectedDatasets,$option);			
		}		

	
		$fileName = "jcvi_metagenomics_report_".time().'.txt';

		header("Content-type: text/plain");
		header("Content-Disposition: attachment;filename=$fileName");
		echo $content;
	}

	/**
	 * Chnages the heatmap color of the HTML heatmap
	 * 
	 * @return void
	 * @access public
	 */	
	function changeHeatmapColor() {

		if(!empty($this->data['Post']['heatmap'])) {
			$heatmapColor = $this->data['Post']['heatmap'];
			$this->Session->write('heatmapColor',$heatmapColor);
		}

		$this->render('/compare/result_panel','ajax');
	}

	
	/**
	 * Flipps compare result matrix
	 * 
	 * @return void
	 * @access public
	 */	
	function flipAxis() {
		$flipAxis= $this->Session->read('flipAxis');

		#switch flipAxis
		if($flipAxis == 0) {
			$flipAxis = 1;
		}
		elseif($flipAxis == 1) {
			$flipAxis = 0;
		}

		$this->Session->write('flipAxis',$flipAxis);

		$this->render('/compare/result_panel','ajax');
	}
}
?>