<?php

/***********************************************************
* File: view_controller.php
* Description: The view pages provide high level summaries 
* of metagenomics datasets. Summaries include top species, 
* KEGG metabolic pathways, Gene Ontology terms, Enzyme Classi-
* fication IDs, HMMs (such as TIGRFAM and Pfam HMMs) and 
* functional names. For each of these data types, a tab with a
* ranked list and a bar chart with the relative frequencies 
* for the respective attribute is displayed. Users can adjust
* the number of ranks displayed (up to 1,000 ranks)and download
* the data in tab delimited format.
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
class ViewController extends AppController {
	
	var $name 		= 'View';
	var $helpers 	= array('LuceneResultPaginator','Facet','Html');
	var $uses 		= array();
	var $components = array('Solr','Download','Format');
	
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
							
								
	var $tabs 	= array(
						'summary'=>array('name'=>'Summary','facetField'=>'summary','action'=>'summary','isActive'=>1),					
						'blast_species'=>array('name'=>'Species (Blast)','facetField'=>'blast_species','action'=>'facet','isActive'=>1),
						'com_name'=>array('name'=>'Common Name','facetField'=>'com_name','action'=>'facet','isActive'=>1),
						'go_id'=>array('name'=>'Gene Ontology','facetField'=>'go_id','action'=>'facet','isActive'=>1),
						'ec_id'=>array('name'=>'Enzyme','action'=>'facet','facetField'=>'ec_id','isActive'=>1),
						'hmm_id'=>array('name'=>'HMM','action'=>'facet','facetField'=>'hmm_id','isActive'=>1),
						 KEGG_PATHWAYS =>array('name'=>'Kegg Pathway (EC)','facetField'=>'ec_id','action'=>'pathway','isActive'=>1),
						 METACYC_PATHWAYS =>array('name'=>'Metacyc Pathway (EC)','facetField'=>'ec_id','action'=>'pathway','isActive'=>1),
						  );															
						  
	//this function lets us view all detail of the lucene index
	function index($dataset,$page=1) {	
		
		//create unique session id
		$viewSessionId = 'view.'.time();
		
		$this->loadModel('Project');
						
		$optionalDatatypes  = $this->Project->checkOptionalDatatypes(array($dataset));

		if(JCVI_INSTALLATION) {	
			if($optionalDatatypes['ko']) {			
				$this->tabs['ko'] =array('name'=>'Kegg Ortholog','action'=>'facet','facetField'=>'ko_id','isActive'=>1);
				$this->resultFields['ko_id'] = 'KO ID';
			}	
			if($optionalDatatypes['clusters']) {
				$this->tabs[CORE_CLUSTERS] = array('name'=>'Core Cluster','action'=>'facet','facetField'=>'cluster_id','facetPrefix'=>CORE_CLUSTERS,'isActive'=>1);
				$this->tabs[FINAL_CLUSTERS] = array('name'=>'Final Cluster','action'=>'facet','facetField'=>'cluster_id','facetPrefix'=>FINAL_CLUSTERS,'isActive'=>1);
			}		
			if($optionalDatatypes['viral']) {
				$this->tabs['env_lib'] = array('name'=>'Environmental Library','action'=>'facet','facetField'=>'env_lib','isActive'=>1);
			}		
			if($optionalDatatypes['population']) {
				$this->tabs['library_id'] = array('name'=>'Library','action'=>'facet','facetField'=>'library_id','isActive'=>1);
			}
			if($optionalDatatypes['filter']) {			
				$this->tabs['filter'] = array('name'=>'Filter','action'=>'facet','facetField'=>'filter','isActive'=>1);				
			}		
		}			
		
		$pipeline	=  $this->Project->getPipeline($dataset);
		
		if($pipeline === PIPELINE_HUMANN) {  
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
								
			$this->tabs  = array(
						'summary'=>array('name'=>'Summary','facetField'=>'summary','action'=>'summary','isActive'=>1),				
						'blast_species'=>array('name'=>'Species (Blast)','facetField'=>'blast_species','action'=>'facet','isActive'=>1),
						'ko_id'=>array('name'=>'Kegg Ortholog','action'=>'facet','facetField'=>'ko_id','isActive'=>1),						
						'go_id'=>array('name'=>'Gene Ontology','action'=>'facet','facetField'=>'go_id','isActive'=>1),
						'ec_id'=>array('name'=>'Enzyme','action'=>'facet','facetField'=>'ec_id','isActive'=>1),						
						 KEGG_PATHWAYS =>array('name'=>'Kegg Pathway (EC)','action'=>'pathway','facetField'=>'ec_id','isActive'=>1),
						 METACYC_PATHWAYS =>array('name'=>'Metacyc Pathway (EC)','action'=>'pathway','facetField'=>'ec_id','isActive'=>1),
						  );						
		}				


		
		//Solr query to fetch data rows (data tab)
		$solrArguments = array(	'fl' => join(' ',array_keys($this->resultFields)),
								'facet'	=> 'true',
								'facet.field' 	=> 'filter',
								'facet.mincount'=> 1,
								'facet.limit' 	=> -1
								);
		try{		
			$result  = $this->Solr->search($dataset,"*:*", ($page-1)*NUM_VIEW_RESULTS,NUM_VIEW_RESULTS,$solrArguments);	
			$numDocs = $this->Solr->documentCount($dataset);	
		}
		catch (Exception $e) {
			$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
			$this->redirect('/projects/index',null,true);
		}
		
		$numHits = (double) $result->response->numFound;
		
		$documents = $result->response->docs;
		$filters = (array) $result->facet_counts->facet_fields->filter;
		
		#$filters = $this->Solr->facet($dataset,'filter');
		
		if(count($filters) > 1) {
			foreach($filters as $filter=>$numPeptides) {
				$filters[$filter] = "$filter (".number_format($numPeptides)." hits)";
			}
			$viewResults['filters'] = $filters;
		}
		else {
			unset($filters);
			if(isset($viewResults['filters'])) {
				unset($viewResults['filters']);
			}
		}
		
		//write session variables
		$viewResults['optionalDatatypes'] = $optionalDatatypes;
		$viewResults['projectId'] 	= $this->Project->getProjectId($dataset);
		$viewResults['projectName'] = $this->Project->getProjectName($dataset);
		$viewResults['numHits'] 	= $numHits;
		$viewResults['numDocs'] 	= $numDocs;
		$viewResults['documents'] 	= $documents;
		
		//store session object
		$this->Session->write($viewSessionId,$viewResults);	
		$this->Session->write($viewSessionId.'tabs',$this->tabs);	
		$this->Session->write($viewSessionId.'resultFields',$this->resultFields);	

		//set view variables
		$this->set('sessionId',$viewSessionId);
		$this->set('dataset',$dataset);
		$this->set('page',$page);
	}
	
	function facet($dataset,$sessionId,$tabId,$limit=20) {	
		$tabs = $this->Session->read($sessionId.'tabs');

		$facetField  = $tabs[$tabId]['facetField'];
			
		$time_start = getmicrotime();
			
		$viewResults = $this->Session->read($sessionId);
			
		if(empty($this->data['Post'])) {
			if(isset($viewResults['limit']))	{ 			
				$limit = $viewResults['limit'];
			}
			if(isset($viewResults['filter']))	{ 			
				$filter = $viewResults['filter'];
			}			
		}
		else {
			if(!empty($this->data['Post']['limit'])) {
				$limit = $this->data['Post']['limit'];
				$viewResults['limit'] = $limit;
			}
			
			//reset filter variables if no option '-select filter--' has been selected
			if(empty($this->data['Post']['filter'])) {	
				if(isset($viewResults['filter'])) {
					unset($viewResults['filter']);
				}
			}
			elseif(!empty($this->data['Post']['filter'])) {
				$filter = $this->data['Post']['filter'];
				$viewResults['filter'] = $filter;
			}
		}

		if(isset($filter)) {
			$query = "filter:$filter";	
		}
		else {
			$query = '*:*';
		}
		
		//specify facet default behaviour
		$solrArguments = array(	"facet" => "true",
						'facet.field' => $facetField,
						'facet.mincount' => 1,
						"facet.limit" => $limit);
			
		if(isset($tabs[$tabId]['facetPrefix'])) {
			$solrArguments['facet.prefix'] = $tabs[$tabId]['facetPrefix'];
		}
		
		try {
			$result= $this->Solr->search($dataset,$query,0,0,$solrArguments,true);			
		}
		catch(Exception $e) {
			$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
			$this->redirect('/projects/index',null,true);
		}
			
		$numHits= (int) $result->response->numFound;
		$facetCounts = $result->facet_counts;
		
		$viewResults['facetCounts'] = $facetCounts;
		$viewResults['limit']  = $limit;
		$viewResults['numHits']= $numHits;

		$this->Session->write($sessionId,$viewResults);	

		$time_end = getmicrotime();
		#debug('Execution time: ' . round($time_end - $time_start,2) .' seconds.');
		
		$this->set('sessionId',$sessionId);			
		$this->set('dataset',$dataset);
		$this->set('tabId',$tabId);
		
		
		$this->render('result_panel','ajax');
	}
		
	function summary($dataset,$sessionId,$tabId) {
		
		$this->loadModel('Project');
		$this->loadModel('Taxonomy');
		
		$summaryCounts = array();
		
		$viewResults = $this->Session->read($sessionId);		

		$pipeline	=  $this->Project->getPipeline($dataset);
		
		if(empty($this->data['Post'])) {
			if(isset($viewResults['filter']))	{ 			
				$filter = $viewResults['filter'];
			}			
		}
		else {
			//reset filter variables if no option '-select filter--' has been selected
			if(empty($this->data['Post']['filter'])) {	
				if(isset($viewResults['filter'])) {
					unset($viewResults['filter']);
				}
			}
			elseif(!empty($this->data['Post']['filter'])) {
				$filter = $this->data['Post']['filter'];
				$viewResults['filter'] = $filter;
			}
		}
		
		if(isset($filter)) {
			$filterQuery = "filter:$filter";	
		}
		else {
			$filterQuery = '*:*';
		}		
		
		$totalCount  = $this->Solr->count($dataset,'*:*',array('fq'=>$filterQuery));	
		
		$optionalDatatypes  = $this->Project->checkOptionalDatatypes(array($dataset));
			
		// evidence summary queries
		$evidenceQueries['Species (Blast)'] = 'NOT blast_species:unassigned AND NOT blast_species:unresolved';
		$evidenceQueries['Gene Ontology'] 	= 'NOT go_id:unassigned';
		$evidenceQueries['Enzyme'] 			= 'NOT ec_id:unassigned';
		
			
		if($pipeline === PIPELINE_HUMANN || $optionalDatatypes['ko']) {  		
			$evidenceQueries['Kegg Ortholog'] = 'NOT ko_id:unassigned';	
		}
		else {
			$evidenceQueries['HMM'] 		= 'NOT hmm_id:unassigned';
		}	

		if($optionalDatatypes['clusters']) {
			$evidenceQueries['Core Clusters'] = "cluster_id:".CORE_CLUSTERS."*";
			$evidenceQueries['Final Clusters']= "cluster_id:".FINAL_CLUSTERS."*";
		}			
		if($optionalDatatypes['viral']) {
			$evidenceQueries['Environmental Libraries'] = 'NOT env_lib:unassigned';
		}		
		if($optionalDatatypes['filter']) {			
			$evidenceQueries['Filter'] = 'NOT filter:unassigned';
		}
		$evidenceQueries['Any Evidence'] 	= '-com_name_src:(CAMERA OR "N/A" OR "No Evidence" OR unassigned)';
		
		$this->addSummaryCounts($dataset,$totalCount,'Evidence Summary',$filterQuery,$evidenceQueries,$summaryCounts);
	
		// common name summaries
		$comNameQueries['unknown protein'] 		= 'com_name:"unknown protein"';
		$comNameQueries['hypothetical protein'] = 'com_name:"hypothetical protein"';
		$comNameQueries['other'] 				= 'NOT com_name:"hypothetical protein" AND  NOT com_name:"unknown protein"';
		
		$this->addSummaryCounts($dataset,$totalCount,'Common Name Summary',$filterQuery,$comNameQueries,$summaryCounts);
		
		if($optionalDatatypes['viral']) {
			
			$viralQueries['PEPSTATS']   		= $filterQuery;
			$viralQueries['UNIREF_PEP'] 		= 'com_name_src:UNIREF*';
			$viralQueries['ACLAME_PEP']  		= 'com_name_src:ACLAME*';
			$viralQueries['PFAM/TIGRFAM_HMM'] 	= 'hmm_id:(PF* OR TIGR*)';
			$viralQueries['NV_NT/SANGER PEP'] 	= 'NOT env_lib:unassigned';
			$viralQueries['COM2GO'] 			= 'go_src:com2go';
			$viralQueries['ACLAME_HMM']  		= 'hmm_id:ACLAME_*';
			$viralQueries['ALLGROUP_PEP'] 		= 'com_name_src:ALLGROUP*';
			$viralQueries['PRIAM']	 			= 'ec_src:PRIAM*';
		
			$this->addSummaryCounts($dataset,$totalCount,'Viral Evidence Summary',$filterQuery,$viralQueries,$summaryCounts);
		}
					
		$taxonResults = $this->Taxonomy->findTopLevelTaxons();

		// taxonomy (Blast) summary
		foreach($taxonResults as $taxon) {			
			$taxonQueries[$taxon['Taxonomy']['name']] = "blast_tree:{$taxon['Taxonomy']['taxon_id']}";
		}
		$this->addSummaryCounts($dataset,$totalCount,'Taxonomy Summary (Blast)',$filterQuery,$taxonQueries,$summaryCounts);

		// taxonomy (Apis) Summary
		if($optionalDatatypes['apis']) {
			
			foreach($taxonResults as $taxon) {			
				$taxonQueries[$taxon['Taxonomy']['name']] = "apis_tree:{$taxon['Taxonomy']['taxon_id']}";
			}
			$this->addSummaryCounts($dataset,$totalCount,'Taxonomy Summary (Apis)',$filterQuery,$taxonQueries,$summaryCounts);
		}	
		
		$viewResults['facetCounts'] = $summaryCounts;
		$viewResults['numHits']		= $totalCount;
		$viewResults['limit'] = null;
		
		$this->Session->write($sessionId,$viewResults);	
		
		$this->set('sessionId',$sessionId);			
		$this->set('dataset',$dataset);
		$this->set('tabId',$tabId);
		$this->render('result_panel','ajax');			
	}
	
	function pathway($dataset,$sessionId,$tabId) {	
		$pathwayModel = $tabId;
		
		$this->loadModel('Pathway');
		
		$time_start = getmicrotime();
		
		$viewResults = $this->Session->read($sessionId);
		
		$numHits  = $viewResults['numHits'];
		
		if(empty($this->data['Post'])) {
			if(isset($viewResults['filter']))	{ 			
				$filter = $viewResults['filter'];
			}			
		}
		else {
			//reset filter variables if no option '-select filter--' has been selected
			if(empty($this->data['Post']['filter'])) {	
				if(isset($viewResults['filter'])) {
					unset($viewResults['filter']);
				}
			}
			elseif(!empty($this->data['Post']['filter'])) {
				$filter = $this->data['Post']['filter'];
				$viewResults['filter'] = $filter;
			}
		}

		if(isset($filter)) {
			$query = "filter:$filter";	
		}
		else {
			$query = '*:*';
		}
			
		$twoLevelSummary = $this->Pathway->getTwoLevelSumary($numHits,$this->Solr,$dataset,$query,$pathwayModel);

		$viewResults['facetCounts'] = $twoLevelSummary;
		$viewResults['numHits']		= $numHits;		
		$viewResults['limit'] = null;
		
		$this->Session->write($sessionId,$viewResults);						
		
		$time_end = getmicrotime();
		#debug('Execution time: ' . round($time_end - $time_start,2) .' seconds.');
		
		$this->set('sessionId',$sessionId);			
		$this->set('dataset',$dataset);
		$this->set('tabId',$tabId);
		$this->render('result_panel','ajax');
	}
	
	#function comparePercentEnzymes($a, $b) { return strnatcmp($b['percFoundEnzymes'], $a['percFoundEnzymes']); } 
	private function comparePeptideCount($a, $b) { return strnatcmp($b['numPeptides'], $a['numPeptides']); } 
	
	function download($dataset,$sessionId,$tabId) {
		$this->autoRender = false; 
		
		$viewResults = $this->Session->read($sessionId);	
		$tabs  = $this->Session->read($sessionId.'tabs');
				
		$facetName	 = $tabs[$tabId]['name'];
		$facetField  = $tabs[$tabId]['facetField'];
		$facetCounts = $viewResults['facetCounts'];		
		$numHits 	 = $viewResults['numHits'];
		
		if(isset($viewResults['filter'])) {
			$filter = $viewResults['filter'];
			$query = "filter:$filter";	
		}
		else {
			$query = '*:*';
		}		
		
		//pathway data has to handled differently 
		if($tabId === 'summary') {	
			$content = $this->Format->infoString("View Summary",$dataset,$query,0,$numHits);
			$content.= $this->Format->summaryToDownloadString($facetCounts,$numHits);					
		}			
		else if($tabId === KEGG_PATHWAYS || $tabId === METACYC_PATHWAYS) {				
			#$pathways = $this->Session->read('view.pathway.counts');
			$content = $this->Format->infoString("$facetName Categories ",$dataset,$query,0,$numHits);	
			$content.= $this->Format->pathwayToDownloadString($facetName,$facetCounts,$numHits);
		}
		else {		
			$limit 	 = $viewResults['limit'];	
			#$facets = $this->Session->read('view.facet.counts');		
			$content = $this->Format->infoString("Top $limit $facetName Categories ",$dataset,$query,0,$numHits);		
			$content.= $this->Format->facetToDownloadString($facetName,$facetCounts->facet_fields->{$facetField},$numHits);	
		}

		#generate download file name
		$fileName = uniqid('jcvi_metagenomics_report_').'.txt';
		$this->Download->string($fileName,$content);
	}
	
	private function addSummaryCounts($dataset,$totalCount,$summaryTitle,$filterQuery,$queries,&$summaryCounts) {
		
		foreach($queries as $name => $query) {
			$assignedCount = $this->Solr->count($dataset,'*:*',array('fq'=>"$query AND ($filterQuery)"));
			$unassignedCount= 	$totalCount-$assignedCount;
			
			$summaryCounts[$summaryTitle][$name]['totalUnassigned']	= $unassignedCount;			
			$summaryCounts[$summaryTitle][$name]['totalAssigned']  	= $assignedCount;
			$summaryCounts[$summaryTitle][$name]['percAssigned'] 	= round($assignedCount/$totalCount,4)*100;
			$summaryCounts[$summaryTitle][$name]['percUnassigned'] 	= round($unassignedCount/$totalCount,4)*100;
			$summaryCounts[$summaryTitle][$name]['total'] 		  	= $totalCount;
			$summaryCounts[$summaryTitle][$name]['name'] 			= $name;	
		}		
	}
}
?>