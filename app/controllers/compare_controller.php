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
* @version METAREP v 1.3.0
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

class CompareController extends AppController {

	var $name 		= 'Compare';	
	var $helpers 	= array('Matrix','Dialog','Ajax');
	var $uses 		= array();
	#var $uses 		= array('Project','Library','Population');
	var $components = array('Solr','RequestHandler','Session','Matrix','Format','Color'); 
	
	var $taxonomyLevels = array(
		'root' 		=> 'root',
		'kingdom' 	=> 'kingdom',
		'class' 	=> 'class',
		'phylum' 	=> 'phylum',
		'order' 	=> 'order',
		'family' 	=> 'family',
		'genus' 	=> 'genus',
	);
	
	var $geneOntologyLevels = array(
		1 =>'root',
		'go slim' =>
			array(
				'goslim_pir.obo' => 'goslim_pir.obo',
				'goslim_generic.obo' => 'goslim_generic.obo',
			),		
		'molecular function (mf)' =>
			array(
				'MF2' => 'mf root distance 2',
				'MF3' => 'mf root distance 3',
				'MF4' => 'mf root distance 4',
				'MF5' => 'mf root distance 5',
			),
		'cellular component (cc)' =>
			array(
				'CC2' => 'cc root distance 2',
				'CC3' => 'cc root distance 3',
				'CC4' => 'cc root distance 4',
				'CC5' => 'cc root distance 5',
			),
		'biological process (bp)' =>
			array(
				'BP2' => 'bp root distance 2',
				'BP3' => 'bp root distance 3',
				'BP4' => 'bp root distance 4',
				'BP5' => 'bp root distance 5',
			)									
	);	
	
	var $enzymeLevels = array(
		'level 1' => 'level 1',
		'level 2' => 'level 2',
		'level 3' => 'level 3',
		'level 4' => 'level 4',
	);
		
	var $hmmLevels = array(
		'TIGR' => 'TIGRFam',
		'PF'   => 'Pfam',
	);

	var $clusterLevels  = array(
		'CAM_CR' => 'core clusters',
		'CAM_CL' => 'final clusters'
	);
	
	var $environmentalLibrariesLevels = array(
		'level1' => 'level 1',					
		'level2' => 'level 2',
	);
	
	var $pathwayLevelsEc = array(
		'super-pathway' => 'super pathways',	
		'pathway' => 'pathways',				
	);		

	var $pathwayLevelsKo = array(
		'level 1' => 'root',	
		'super-pathway' => 'super pathways',	
		'pathway' => 'pathways',				
	);		
	
	var $commonNamelevels = array(
		'10'  => 'top 10 hits',
		'20'  => 'top 20 hits',
		'50'  => 'top 50 hits',
		'100' => 'top 100 hits',
		'1000'=> 'top 1000 hits',
	);	
	
	/**
	 * Initializes index compare page
	 * 
	 * @param String $dataset Initial dataset to compare others against
	 * @return void
	 * @access public
	 */			
	function index($dataset = null,$mode = SHOW_PROJECT_DATASETS) {
		
		$this->loadModel('Project');
		$this->loadModel('Population');
			
		//increase memory size
		ini_set('memory_limit', '856M');
				
		$this->pageTitle = 'Compare Multiple Datasets';

		//set default values
		if(!$this->Session->check('filter')) {
			$this->Session->write('filter',"*:*");
		}	
		if(!$this->Session->check('minCount')) {
			$this->Session->write('minCount',0);
		}	
		if(!$this->Session->check('option')) {
			$this->Session->write('option',ABSOLUTE_COUNTS);
		}	
		if(!$this->Session->check('plotLabel')) {
			$this->Session->write('plotLabel',PLOT_LIBRARY_NAME);
		}	
		if(!$this->Session->check('distanceMatrix')) {
			$this->Session->write('distanceMatrix',DISTANCE_BRAY);
		}	
		if(!$this->Session->check('clusterMethod')) {
			$this->Session->write('clusterMethod',CLUSTER_AVERAGE);
		}		
		if(!$this->Session->check('maxPvalue')) {
			$this->Session->write('maxPvalue',PVALUE_ALL);
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
		
		$this->set('projectName',$this->Project->getProjectName($dataset));
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
		$this->loadModel('Project');
		
		#get compare form data
		$option				= $this->data['Compare']['option'];
		$minCount 			= $this->data['Compare']['minCount'];
		$filter				= $this->data['Compare']['filter'];

		if(isset($this->data['selectedDatasets'])) {						
			$selectedDatasets	= $this->data['selectedDatasets'];

			//get pipeline summary for all selected datasets
			$datasetPipelines 	= $this->Project->checkDatasetPipelines($selectedDatasets);
			
					//configure result tabs for each pipeline type
			if($datasetPipelines[PIPELINE_DEFAULT] || $datasetPipelines[PIPELINE_JCVI_META_PROK] ) {			
			$tabs = array(array('function'=>'blastTaxonomy','isActive'=>1,'tabName' => 'Taxonomy (Blast)','dbTable' => 'Taxonomy','sorlField' => 'blast_tree','rootLevel'=>'root'),
						  array('function'=>'geneOntology','isActive'=>1,'tabName' => 'Gene Ontology','dbTable' => 'GeneOntology','sorlField' => 'go_tree','rootLevel'=>1),
						  array('function'=>'keggPathwaysEc','isActive'=>1,'tabName' => 'Kegg Pathway (EC)','dbTable' => null,'sorlField' => 'ec_id','rootLevel'=>'super-pathway'),
						  array('function'=>'metacycPathways','isActive'=>1,'tabName' => 'Metacyc Pathway (EC)','dbTable' => null,'sorlField' => 'ec_id','rootLevel'=>'super-pathway'),
						  array('function'=>'enzymes','isActive'=>1,'tabName' => 'Enzyme','dbTable' => 'Enzymes','sorlField' => 'enzyme_id','rootLevel'=>'level 1'),
						  array('function'=>'hmms','isActive'=>1,'tabName' => 'HMM','dbTable' => 'Hmm','sorlField' => 'hmm_id','rootLevel'=>'TIGR'),
						  array('function'=>'commonNames','isActive'=>1,'tabName' => 'Common Names','dbTable' => null,'sorlField' => 'com_name','rootLevel'=>10));
			}
			else if($datasetPipelines[PIPELINE_JCVI_META_VIRAL]) {	
			$tabs = array(array('function'=>'blastTaxonomy','isActive'=>1,'tabName' => 'Taxonomy (Blast)','dbTable' => 'Taxonomy','sorlField' => 'blast_tree','rootLevel'=>'root'),
						  array('function'=>'geneOntology','isActive'=>1,'tabName' => 'Gene Ontology','dbTable' => 'GeneOntology','sorlField' => 'go_tree','rootLevel'=>1),
						  array('function'=>'keggPathwaysEc','isActive'=>1,'tabName' => 'Kegg Pathway (EC)','dbTable' => null,'sorlField' => 'ec_id','rootLevel'=>'super-pathway'),
						  array('function'=>'metacycPathways','isActive'=>1,'tabName' => 'Metacyc Pathway (EC)','dbTable' => null,'sorlField' => 'ec_id','rootLevel'=>'super-pathway'),
						  array('function'=>'enzymes','isActive'=>1,'tabName' => 'Enzyme','dbTable' => 'Enzymes','sorlField' => 'enzyme_id','rootLevel'=>'level 1'),
						  array('function'=>'hmms','isActive'=>1,'tabName' => 'HMM','dbTable' => 'Hmm','sorlField' => 'hmm_id','rootLevel'=>'TIGR'),
						  array('function'=>'commonNames','isActive'=>1,'tabName' => 'Common Names','dbTable' => null,'sorlField' => 'com_name','rootLevel'=>10),
 						  array('function'=>'environmentalLibraries','isActive'=>1,'tabName' => 'Environmental Libraries','dbTable' => 'environemental_libraries','sorlField' => 'env_lib','rootLevel'=>'level1'));						  							
			}			
			else if($datasetPipelines[PIPELINE_HUMANN]) {	
			$tabs = array(array('function'=>'blastTaxonomy','isActive'=>1,'tabName' => 'Taxonomy (Blast)','dbTable' => 'Taxonomy','sorlField' => 'blast_tree','rootLevel'=>'root'),
							  //array('function'=>'keggOrtholog','isActive'=>1,'tabName' => 'Kegg Ortholog','dbTable' => 'KeggOrtholog','sorlField' => 'ko_id'),
							  array('function'=>'geneOntology','isActive'=>1,'tabName' => 'Gene Ontology','dbTable' => 'GeneOntology','sorlField' => 'go_tree','rootLevel'=>1),
							  array('function'=>'keggPathwaysKo','isActive'=>1,'tabName' => 'Kegg Pathway (KO)','dbTable' => null,'sorlField' => 'ec_id','rootLevel'=>'level 1'),
							  array('function'=>'keggPathwaysEc','isActive'=>1,'tabName' => 'Kegg Pathway (EC)','dbTable' => null,'sorlField' => 'ec_id','rootLevel'=>'super-pathway'),
							  array('function'=>'metacycPathways','isActive'=>1,'tabName' => 'Metacyc Pathway (EC)','dbTable' => null,'sorlField' => 'ec_id','rootLevel'=>'super-pathway'),
							  array('function'=>'enzymes','isActive'=>1,'tabName' => 'Enzymes','dbTable' => 'Enzyme','sorlField' => 'enzyme_id','rootLevel'=>'level 1'),
							  );				
			}			
			//tabs that are valid for all pipelines
			else {
				$tabs = array(array('function'=>'blastTaxonomy','isActive'=>1,'tabName' => 'Taxonomy (Blast)','dbTable' => 'Taxonomy','sorlField' => 'blast_tree','rootLevel'=>'root'),
							 // array('function'=>'keggOrtholog','isActive'=>1,'tabName' => 'Kegg Ortholog','dbTable' => 'KeggOrtholog','sorlField' => 'ko_id'),
							  array('function'=>'geneOntology','isActive'=>1,'tabName' => 'Gene Ontology','dbTable' => 'GeneOntology','sorlField' => 'go_tree','rootLevel'=>1),
							  array('function'=>'keggPathwaysEc','isActive'=>1,'tabName' => 'Kegg Pathway (EC)','dbTable' => null,'sorlField' => 'ec_id','rootLevel'=>'super-pathway'),
							  array('function'=>'metacycPathways','isActive'=>1,'tabName' => 'Metacyc Pathway (EC)','dbTable' => null,'sorlField' => 'ec_id','rootLevel'=>'super-pathway'),
							  array('function'=>'enzymes','isActive'=>1,'tabName' => 'Enzymes','dbTable' => 'Enzyme','sorlField' => 'enzyme_id','rootLevel'=>'level 1'),
				);				
			}

			$optionalDatatypes  = $this->Project->checkOptionalDatatypes($selectedDatasets);
						  
			//set optional data types for JCVI-only installation					  
			if(JCVI_INSTALLATION) {			  							  
				if($optionalDatatypes['apis']) {
					array_push($tabs,array('function'=>'apisTaxonomy','isActive'=>1,'tabName' => 'Taxonomy (Apis)','dbTable' => 'Taxonomy','sorlField' => 'apis_tree','rootLevel' =>'root'));
				}
				else {
					//array_push($tabs,array('function'=>'apisTaxonomy','isActive'=>0,'tabName' => 'Taxonomy (Apis)','dbTable' => 'Taxonomy','sorlField' => 'apis_tree'));
				}	
				if($optionalDatatypes['clusters']) {
					array_push($tabs,array('function'=>'clusters','isActive'=>1,'tabName' => 'Clusters','dbTable' => null,'sorlField' => 'cluster_id','rootLevel' =>'CAM_CR'));
				}	
				else {
					//array_push($tabs,array('function'=>'clusters','isActive'=>0,'tabName' => 'Clusters','dbTable' => null,'sorlField' => 'cluster_id'));
				}		
				if($optionalDatatypes['viral']) {					
					array_push($tabs,array('function'=>'environmentalLibraries','isActive'=>1,'tabName' => 'Environmental Libraries','dbTable' => 'environemental_libraries','sorlField' => 'env_lib'));
				}					  					  
			}
						
			## set default variables
			if(empty($option)) {
				$option = ABSOLUTE_COUNTS;
			}
			if(empty($filter)) {
				$filter = 	"*:*";
			}
			if(empty($minCount)) {
				$minCount = 0;
			}
			if($this->Session->check('heatmapColor')) {
				$heatmapColor = $this->Session->read('heatmapColor');
			}
			else {
				$heatmapColor = HEATMAP_COLOR_YELLOW_RED;
			}
			$colorGradient =  $this->Color->gradient($heatmapColor);			
			
			## handle tests
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
					
			## handle plot exception (fewer than 3 datasets)
			if(count($selectedDatasets) < 3 && $option > 6 ) {				
				$this->set('multiSelectException','Please select at least 3 datasets for this plot option.');
				$this->set('filter',$filter);
				$this->render('/compare/result_panel','ajax');
			}		
			
			//get associative array of total counts
			$totalCounts = $this->getTotalCounts($filter,$selectedDatasets);
			
			//reset level sessions
			foreach($tabs as $tab) {
				$this->Session->write("{$tab['function']}.level",$tab['rootLevel']);
			}
			
			//write variables to sessions				
			$this->Session->write('option',$option);
			$this->Session->write('minCount',$minCount);
			$this->Session->write('filter',$filter);
			$this->Session->write('selectedDatasets',$selectedDatasets);
			$this->Session->write('optionalDatatypes',$optionalDatatypes);
			$this->Session->write('tabs',$tabs);
			$this->Session->write('flipAxis',0);
			$this->Session->write('totalCounts',$totalCounts);
			$this->Session->write('heatmapColor',$heatmapColor);	
			$this->Session->write('colorGradient',$colorGradient);		
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
		
		$this->loadModel('Taxonomy');
		$this->loadModel('Project');
		$this->loadModel('Population');
				
		if($facetField === 'blast_tree') {
			$mode = 'blastTaxonomy';
		}
		if($facetField === 'apis_tree') {
			$mode = 'apisTaxonomy';
		}		
		
		$counts	= array();
		$level  = 'root';
		
		//read post data
		if(!empty($this->data['Post']['level'])) {
			$level = $this->data['Post']['level'];
		}
		else {
			if($this->Session->check("$mode.level")) {
				$level = $this->Session->read("$mode.level");
			}	
		}

		$levels = $this->taxonomyLevels;
						
		//read session variables
		$option 			= $this->Session->read('option');
		$minCount 			= $this->Session->read('minCount');
		$filter 			= $this->Session->read('filter');
		$selectedDatasets	= $this->Session->read('selectedDatasets');
		$totalCounts		= $this->Session->read('totalCounts');
		$plotLabel			= $this->Session->read('plotLabel');
		$clusterMethod		= $this->Session->read('clusterMethod');
		$distanceMatrix		= $this->Session->read('distanceMatrix');
		$datasetPipelines	= $this->Session->read('datasetPipelines');
		$optionalDatatypes	= $this->Session->read('optionalDatatypes');
		
		if($option == METASTATS || $option == WILCOXON) {
			$totalCounts = $this->transformPopulationsIntoLibraries($selectedDatasets,$filter);
		}		
	
		//write session variables
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
		$this->Solr->multiSearch($counts,$selectedDatasets,$facetQueries,$filter,$minCount);
		$this->Matrix->formatCounts($option,$filter,$minCount,$selectedDatasets,$totalCounts,$counts,$plotLabel,$clusterMethod,$distanceMatrix);
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
		$this->loadModel('GoGraph');
		$this->loadModel('Project');
		$this->loadModel('Population');
			
		$mode 			= __FUNCTION__;
		$counts			= array();
		$subontology 	= 'universal';
		$ancestor  		= 'all';
		$levelLabel 	= 1;
		$level			= 1;

		//drop down menu information
		$levels = $this->geneOntologyLevels;

		//read post data
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
		$totalCounts		= $this->Session->read('totalCounts');
		$plotLabel			= $this->Session->read('plotLabel');
		$clusterMethod		= $this->Session->read('clusterMethod');
		$distanceMatrix		= $this->Session->read('distanceMatrix');
		$datasetPipelines	= $this->Session->read('datasetPipelines');
		$optionalDatatypes	= $this->Session->read('optionalDatatypes');		
		
		if($option == METASTATS || $option == WILCOXON) {
			$totalCounts = $this->transformPopulationsIntoLibraries($selectedDatasets,$filter);
		}			
		
		#write session variables
		$this->Session->write('levels',$levels);
		$this->Session->write('mode',$mode);
		$this->Session->write("$mode.level", $levelLabel);

		//find GO children
		$goChildren = $this->GoGraph->find('all', array('fields' => array('Descendant.acc','Descendant.name'),'conditions' => array('Ancestor.acc' => $ancestor,'distance'=>$level, 'Ancestor.term_type'=>$subontology,'Descendant.is_obsolete'=>0)));

		$facetQueries = array();
		
		//set up count matrix
		foreach($goChildren as $goChild) {
			$goAcc	 = $goChild['Descendant']['acc'];
			$category= $goChild['Descendant']['acc'];
			$tmp  = split("\\:",$goAcc);
			$category = ltrim($tmp[1], "0");
			$counts[$category]['name'] = $goChild['Descendant']['name'];
			$counts[$category]['sum'] = 0;			
			array_push($facetQueries,"go_tree:$category");	
		}
		unset($goChildren);
		
		$this->Solr->multiSearch($counts,$selectedDatasets,$facetQueries,$filter,$minCount);
		$this->Matrix->formatCounts($option,$filter,$minCount,$selectedDatasets,$totalCounts,$counts,$plotLabel,$clusterMethod,$distanceMatrix);
		$this->Session->write('counts',$counts);
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
		$this->loadModel('Enzymes');
		$this->loadModel('Project');
		$this->loadModel('Population');
		
		$mode = __FUNCTION__;
		$counts	= array();
		$level	='level 1';

		$levels = $this->enzymeLevels;
			
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
		$totalCounts		= $this->Session->read('totalCounts');
		$plotLabel			= $this->Session->read('plotLabel');
		$clusterMethod		= $this->Session->read('clusterMethod');
		$distanceMatrix		= $this->Session->read('distanceMatrix');
		$datasetPipelines	= $this->Session->read('datasetPipelines');
		$optionalDatatypes	= $this->Session->read('optionalDatatypes');	
		
		if($option == METASTATS || $option == WILCOXON) {
			$totalCounts = $this->transformPopulationsIntoLibraries($selectedDatasets,$filter);
		}	

		#write session variables
		$this->Session->write('levels',$levels);
		$this->Session->write('mode',$mode);
		$this->Session->write("$mode.level", $level);		
		

		$facetQueries = array() ;
		$facetQueryMapping =array();
		
		#datastructure of dataset names and ids
		$enzymeResults 	= $this->Enzymes->find('all', array('conditions' => array('Enzymes.rank' => $level)));
		
		//set up count matrix
		foreach($enzymeResults as $enzymeResult) {

			$ecId = $enzymeResult['Enzymes']['ec_id'];
			
			//add fuzzy matching to handle higher level enzyme classifications, 
			//e.g. 1.3.-.- becomes 1.3.*.*
			$solrEcId = "ec_id:".str_replace("-","*",$ecId);
			array_push($facetQueries,$solrEcId);
			$facetQueryMapping[$solrEcId] = $ecId;	
											
			$name 	= $enzymeResult['Enzymes']['name'];

			//init count data
			$counts[$ecId]['name'] 	= $name;	
			$counts[$ecId]['sum'] 	= 0;	
		}
				
		unset($enzymeResults);
		
		$this->Solr->multiSearch($counts,$selectedDatasets,$facetQueries,$filter,$minCount,$facetQueryMapping);		
		$this->Matrix->formatCounts($option,$filter,$minCount,$selectedDatasets,$totalCounts,$counts,$plotLabel,$clusterMethod,$distanceMatrix);
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
		$this->loadModel('Hmm');
		$this->loadModel('Population');
		
		$mode = __FUNCTION__;
		$counts	= array();
		
		//read session variables
		$option 			= $this->Session->read('option');
		$minCount 			= $this->Session->read('minCount');
		$filter 			= $this->Session->read('filter');
		$selectedDatasets	= $this->Session->read('selectedDatasets');
		$optionalDatatypes  = $this->Session->read('optionalDatatypes');	
		$totalCounts		= $this->Session->read('totalCounts');	
		$plotLabel			= $this->Session->read('plotLabel');
		$clusterMethod		= $this->Session->read('clusterMethod');
		$distanceMatrix		= $this->Session->read('distanceMatrix');
				
		//drop down selection
		$levels = $this->hmmLevels;

		//handle ACLAME HMMs for viral annotations
		if(JCVI_INSTALLATION && $optionalDatatypes['viral']) {
			$levels['AC'] = 'ACLAME';
			$level = 'AC';
		}	
		else {
			$level	='TIGR';
		}	
		
		//read post data
		if(!empty($this->data['Post']['level'])) {
			$level = $this->data['Post']['level'];
		}
		else {
			if($this->Session->check("$mode.level")) {
				$level = $this->Session->read("$mode.level");
				
				//handle ACLAME HMMs for viral annotations
				if(JCVI_INSTALLATION && !$optionalDatatypes['viral']) {
					$level	='TIGR';
				}
			}	
		}
		
		if($option == METASTATS || $option == WILCOXON) {
			$totalCounts = $this->transformPopulationsIntoLibraries($selectedDatasets,$filter);
		}			
		
		//write session variables
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
				$this->redirect('/projects/index',null,true);
			}
				
			$facets = $result->facet_counts->facet_fields->hmm_id;
				
			foreach($facets as $category => $count) {
				$counts[$category][$dataset] = $count;
				$counts[$category]['sum'] += $count;
			}				
		}

		$this->Matrix->formatCounts($option,$filter,$minCount,$selectedDatasets,$totalCounts,$counts,$plotLabel,$clusterMethod,$distanceMatrix);

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
		$this->loadModel('Population');
		$this->loadModel('Cluster');
		
		$mode   = __FUNCTION__;
		$counts	= array();
		$level	='CAM_CR';

		//drop down selection
		$levels = $this->clusterLevels;
			
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
		$totalCounts		= $this->Session->read('totalCounts');
		$plotLabel			= $this->Session->read('plotLabel');
		$clusterMethod		= $this->Session->read('clusterMethod');
		$distanceMatrix		= $this->Session->read('distanceMatrix');
		
		if($option == METASTATS || $option == WILCOXON) {
			$totalCounts = $this->transformPopulationsIntoLibraries($selectedDatasets,$filter);
		}			
		
		if($minCount==0) {
			$minCount=5;
		}

		//write session variables
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
				$this->redirect('/projects/index',null,true);
			}
				
			$facets = $result->facet_counts->facet_fields->cluster_id;
			
			foreach($facets as $category => $count) {

				$counts[$category]['name'] = $this->Cluster->getDescription($category);
				
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

		$this->Matrix->formatCounts($option,$filter,$minCount,$selectedDatasets,$totalCounts,$counts,$plotLabel,$clusterMethod,$distanceMatrix);

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
		$this->loadModel('EnvironmentalLibrary');
		$this->loadModel('Population');
		
		$mode   = __FUNCTION__;
		$counts	= array();
		
		$level	='level1';
		
		//drop down selection
		$levels = $this->environmentalLibrariesLevels;
	
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

			//escape lucene special characters
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
		$totalCounts		= $this->Session->read('totalCounts');
		$plotLabel			= $this->Session->read('plotLabel');
		$clusterMethod		= $this->Session->read('clusterMethod');
		$distanceMatrix		= $this->Session->read('distanceMatrix');
		
		if($option == METASTATS || $option == WILCOXON) {
			$totalCounts = $this->transformPopulationsIntoLibraries($selectedDatasets,$filter);
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


		$this->Matrix->formatCounts($option,$filter,$minCount,$selectedDatasets,$totalCounts,$counts,$plotLabel,$clusterMethod,$distanceMatrix);

		$this->Session->write('counts',$counts);

		$this->render('/compare/result_panel','ajax');
	}
	
	
	function keggPathwaysEc() {
		$this->pathways(KEGG_PATHWAYS);
	}
	function keggPathwaysKo() {
		$this->pathways(KEGG_PATHWAYS_KO);
	}		
	function metacycPathways() {
		$this->pathways(METACYC_PATHWAYS);
	}	
	
	/**
	 * Compare pathways across selected datasets
	 * 
	 * @return void
	 * @access private
	 */
	private function pathways($pathwayModel) {
		
		$this->loadModel('Pathway');
		$this->loadModel('Population');
		$mode = $this->underscoreToCamelCase($pathwayModel);
		
		$counts	= array();
		$facetQueries = array();
		$facetQueryMapping =array();
		
		if($pathwayModel === KEGG_PATHWAYS || $pathwayModel === METACYC_PATHWAYS) {
			$levels = $this->pathwayLevelsEc;
		}
		if($pathwayModel === KEGG_PATHWAYS_KO) {
			$levels = $this->pathwayLevelsKo;		
		}		
		
		//read post data
		if(!empty($this->data['Post']['level'])) {
			$level = $this->data['Post']['level'];
		}
		else {
			if($this->Session->check("$mode.level")) {
				$level = $this->Session->read("$mode.level");
			}
			else {
				if($pathwayModel === KEGG_PATHWAYS || $pathwayModel === METACYC_PATHWAYS) {
					$level	= 'super-pathway';	
				}				
				else if($pathwayModel === KEGG_PATHWAYS_KO) {
					$level	= 'level 1';		
				}							
			}	
		}
	
		//read session variables
		$option 			= $this->Session->read('option');
		$minCount 			= $this->Session->read('minCount');
		$filter 			= $this->Session->read('filter');
		$selectedDatasets	= $this->Session->read('selectedDatasets');
		$totalCounts		= $this->Session->read('totalCounts');
		$plotLabel			= $this->Session->read('plotLabel');
		$clusterMethod		= $this->Session->read('clusterMethod');
		$distanceMatrix		= $this->Session->read('distanceMatrix');
		$datasetPipelines	= $this->Session->read('datasetPipelines');
		$optionalDatatypes	= $this->Session->read('optionalDatatypes');	
		
		if($option == METASTATS || $option == WILCOXON) {
			$totalCounts = $this->transformPopulationsIntoLibraries($selectedDatasets,$filter);
		}			
			
		//write session variables
		$this->Session->write("$mode.level", $level);
		$this->Session->write('levels',$levels);
		$this->Session->write('mode',$mode);
			
		$pathways = $this->Pathway->getCategories($level,$pathwayModel);
		
		foreach($pathways as $pathwayId=>$entry) {
			array_push($facetQueries,$entry['query']);
			$facetQueryMapping[$entry['query']] = $pathwayId;
		}
		
		$counts = $pathways;
		
		$this->Solr->multiSearch($counts,$selectedDatasets,$facetQueries,$filter,$minCount,$facetQueryMapping);		
		$this->Matrix->formatCounts($option,$filter,$minCount,$selectedDatasets,$totalCounts,$counts,$plotLabel,$clusterMethod,$distanceMatrix);
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
		$this->loadModel('Population');
		
		$mode   = __FUNCTION__;
		$counts = array();
		$level	= 10;
		
		$levels = $this->commonNamelevels;

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
		$totalCounts		= $this->Session->read('totalCounts');
		$plotLabel			= $this->Session->read('plotLabel');
		$clusterMethod		= $this->Session->read('clusterMethod');
		$distanceMatrix		= $this->Session->read('distanceMatrix');
		
		if($option == METASTATS || $option == WILCOXON) {
			$totalCounts = $this->transformPopulationsIntoLibraries($selectedDatasets,$filter);
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
				$this->redirect('/projects/index',null,true);
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

		$this->Matrix->formatCounts($option,$filter,$minCount,$selectedDatasets,$totalCounts,$counts,$plotLabel,$clusterMethod,$distanceMatrix);
		
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
		$clusterMethod		= $this->Session->read('clusterMethod');
		$distanceMatrix		= $this->Session->read('distanceMatrix');
		$maxPvalue			= $this->Session->read('maxPvalue');

		if($option == CHISQUARE) {
			$title = 'Comparison Results - Chi-Square Test of Independence';
			$content = $this->Format->infoString($title,$selectedDatasets,$filter,$minCount);
			$content.= $this->Format->comparisonResultsToDownloadString($counts,$selectedDatasets,$option);
		}
		elseif($option == FISHER) {			
			$title = "Comparison Results - Fisher's Exact Test";
			$content = $this->Format->infoString($title,$selectedDatasets,$filter,$minCount);
			$content.= $this->Format->comparisonResultsToDownloadString($counts,$selectedDatasets,$option);
		}
		elseif($option == METASTATS) {
			$title = "Comparison Results - METASTATS non-parametric t-test";
			$content = $this->Format->infoString($title,$selectedDatasets,$filter,$minCount);
			$content.= $this->Format->metatstatsResultsToDonwloadString($counts,$selectedDatasets,$maxPvalue);
		}	
		elseif($option == WILCOXON) {
			$title = "Comparison Results - Wilcoxon Signed Rank Test";
			$content = $this->Format->infoString($title,$selectedDatasets,$filter,$minCount);
			$content.= $this->Format->wilcoxonResultsToDonwloadString($counts,$selectedDatasets,$maxPvalue);
		}	
		//plot options
		elseif($option > 6) {
			$title = "Comparison Results - $distanceMatrix Distance Matrix";
			$content = $this->Format->infoString($title,$selectedDatasets,$filter,$minCount);
			$content .= $this->Session->read('distantMatrices');		
		}				
		else{
			$title = "Comparison Results";
			$content = $this->Format->infoString($title,$selectedDatasets,$filter,$minCount);
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

		$option = $this->Session->read('option');
		
		if ($option == HEATMAP) {
			if(!empty($this->data['Post']['heatmap'])) {
				$heatmapColor = $this->data['Post']['heatmap'];	
				$colorGradient =  $this->Color->gradient($heatmapColor);			
				$this->Session->write('heatmapColor',$heatmapColor);
				$this->Session->write('colorGradient',$colorGradient);
			}
		}
		else if($option == HEATMAP_PLOT) {					
			if(!empty($this->data['Post']['heatmap'])) {
				$this->Session->write('heatmapColor',$this->data['Post']['heatmap']);
				
				$selectedDatasets	= $this->Session->read('selectedDatasets');
				$counts				= $this->Session->read('counts');
				$option 			= $this->Session->read('option');
				$plotLabel			= $this->Session->read('plotLabel');		
				$clusterMethod		= $this->Session->read('clusterMethod');		
				$distanceMatrix		= $this->Session->read('distanceMatrix');
				
				$this->Matrix->updatePlot($selectedDatasets,$counts,$option,$plotLabel,$clusterMethod,$distanceMatrix);		
			}
		}

		$this->render('/compare/result_panel','ajax');
	}

	function changePlotLabel() {
		$selectedDatasets	= $this->Session->read('selectedDatasets');
		$counts				= $this->Session->read('counts');
		$option 			= $this->Session->read('option');
		$minCount 			= $this->Session->read('minCount');
		$filter 			= $this->Session->read('filter');
		$totalCounts		= $this->Session->read('totalCounts');
		$distanceMatrix		= $this->Session->read('distanceMatrix');
		$clusterMethod		= $this->Session->read('clusterMethod');
		
		if($this->Session->check('plotLabel')) {
			$previousPlotLabel	= $this->Session->read('plotLabel');
		}
		else {
			$previousPlotLabel = PLOT_LIBRARY_NAME;
		}
		
		if(!empty($this->data['Post']['plotLabel'])) {
			$selectedPlotLabel = $this->data['Post']['plotLabel'];
			
			if($selectedPlotLabel != $previousPlotLabel) {
				$this->Session->write('plotLabel',$selectedPlotLabel);
				$this->Matrix->updatePlot($selectedDatasets,$counts,$option,$selectedPlotLabel,$clusterMethod,$distanceMatrix);			
			}		
		}
		
		$this->render('/compare/result_panel','ajax');
	}

	function changePvalue() {
		$selectedDatasets	= $this->Session->read('selectedDatasets');
		$counts				= $this->Session->read('counts');
		$option 			= $this->Session->read('option');
		$minCount 			= $this->Session->read('minCount');
		$filter 			= $this->Session->read('filter');
		$totalCounts		= $this->Session->read('totalCounts');
		$distanceMatrix		= $this->Session->read('distanceMatrix');
		$clusterMethod		= $this->Session->read('clusterMethod');
		
		if($this->Session->check('maxPvalue')) {
			$maxPvalue	= $this->Session->read('maxPvalue');
		}
		else {
			$maxPvalue = PVALUE_ALL;
		}
		
		
		if(!empty($this->data['Post']['maxPvalue'])) {
			$selectedMaxPvalue = $this->data['Post']['maxPvalue'];
			
			if($selectedMaxPvalue != $maxPvalue) {
				$this->Session->write('maxPvalue',$selectedMaxPvalue);	
			}		
		}
		
		$this->render('/compare/result_panel','ajax');
	}	
	
	function changeDistanceMatrix() {
		
		$selectedDatasets	= $this->Session->read('selectedDatasets');
		$counts				= $this->Session->read('counts');
		$option 			= $this->Session->read('option');
		$minCount 			= $this->Session->read('minCount');
		$filter 			= $this->Session->read('filter');
		$totalCounts		= $this->Session->read('totalCounts');
		$plotLabel			= $this->Session->read('plotLabel');
		$clusterMethod		= $this->Session->read('clusterMethod');
		
		if($this->Session->check('distanceMatrix')) {
			$previousPlotDistanceMatrix	= $this->Session->read('distanceMatrix');
		}
		else {
			$previousPlotDistanceMatrix = DISTANCE_BRAY;
		}
		
		if(!empty($this->data['Post']['distanceMatrix'])) {
			
			$selectedPlotDistanceMatrix = $this->data['Post']['distanceMatrix'];
			
			if($selectedPlotDistanceMatrix != $previousPlotDistanceMatrix) {
				$this->Session->write('distanceMatrix',$selectedPlotDistanceMatrix);
				
				$this->Matrix->updatePlot($selectedDatasets,$counts,$option,$plotLabel,$clusterMethod,$selectedPlotDistanceMatrix);			
			}		
		}
		
		$this->render('/compare/result_panel','ajax');
	}	
	
	function changeClusterMethod() {
		
		$selectedDatasets	= $this->Session->read('selectedDatasets');
		$counts				= $this->Session->read('counts');
		$option 			= $this->Session->read('option');
		$minCount 			= $this->Session->read('minCount');
		$filter 			= $this->Session->read('filter');
		$totalCounts		= $this->Session->read('totalCounts');
		$plotLabel			= $this->Session->read('plotLabel');
		$distanceMatrix		= $this->Session->read('distanceMatrix');
		
		if($this->Session->check('clusterMethod')) {
			$previousClusterMethod	= $this->Session->read('clusterMethod');
		}
		else {
			$previousClusterMethod = CLUSTER_AVERAGE;
		}
		
		if(!empty($this->data['Post']['clusterMethod'])) {
			$selectedClusterMethod = $this->data['Post']['clusterMethod'];
			
			if($selectedClusterMethod != $previousClusterMethod) {
				$this->Session->write('clusterMethod',$selectedClusterMethod);
				
				$this->Matrix->updatePlot($selectedDatasets,$counts,$option,$plotLabel,$selectedClusterMethod,$distanceMatrix);			
			}		
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
	
	//split the two populations into their libraries; store population 
	//names and store position of second population; return adjusted total count array
	private function transformPopulationsIntoLibraries(&$selectedDatasets,$filter) {
		$this->Session->write('populations',$selectedDatasets);
		
		$librariesA = $this->Population->getLibraries($selectedDatasets[0]);
		$librariesB = $this->Population->getLibraries($selectedDatasets[1]);
		
		$countA = count($librariesA);
		$countB = count($librariesB);
		
		$selectedDatasets = array_merge($librariesA,$librariesB);
		$totalCounts = $this->getTotalCounts($filter,$selectedDatasets);
		
		$this->Session->write('startIndexPopulationB',count($librariesA)+1);
		$this->Session->write('libraryCountPopulationA',$countA);
		$this->Session->write('libraryCountPopulationB',$countB);

		return $totalCounts;		
	}
	
	#returns associative array containing the total peptide counts for all selected datasets
	#counts are used to generate relative and relative row counts
	private function getTotalCounts($filter,$datasets) {
		$totalCounts = array();
		
		#loop through datasets
		foreach($datasets as $dataset) {
			try	{				
				$totalCounts[$dataset] =  $this->Solr->count($dataset,$filter);
			}
			catch (Exception $e) {
				throw new Exception($e);
			}
		}
		return $totalCounts;
	}
}
?>