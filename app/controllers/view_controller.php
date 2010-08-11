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
* @version METAREP v 1.0.1
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/
class ViewController extends AppController {
	
	var $name 		= 'View';
	var $helpers 	= array('LuceneResultPaginator','Facet','Ajax');
	var $uses 		= array('Project','Population','Library','GoTerm','GoGraph','Enzymes','Hmm','Pathway');
	var $components = array('Solr','Format');
	
	//this function lets us view all detail of the lucene index
	function index($dataset='CBAYVIR',$page=1) {
			
		//unset existing filter session variables		
		if($this->Session->check('view.filter')) {
			$this->Session->delete('view.filter');
		}
				
		$this->Project->unbindModel(array('hasMany' => array('Population'),),false);
		$this->Project->unbindModel(array('hasMany' => array('Library'),),false);
		
		$optionalDatatypes  = $this->Project->checkOptionalDatatypes(array($dataset));
		
		$displayLimit 	= 20;
				
		//Solr query to fetch data rows (data tab)
		$solrArguments = array(	'fl' => 'peptide_id com_name com_name_src blast_species blast_evalue go_id go_src ec_id ec_src hmm_id');		
		$result = $this->Solr->search($dataset,"*:*", ($page-1)*$displayLimit,$displayLimit,$solrArguments);			
		$numHits = (int) $result->response->numFound;
		$hits = $result->response->docs;
						
		$filters = $this->Solr->facet($dataset,'filter');
		
		if(count($filters) > 1) {
			foreach($filters as $filter=>$numPeptides) {
				$filters[$filter] = "$filter (".number_format($numPeptides)." hits)";
			}
			$this->Session->write('view.filters',$filters);
		}
		else {
			unset($filters);
			if($this->Session->check('view.filters')) {
				$this->Session->delete('view.filters');
			}	
		}
		
		//write session variables
		$this->Session->write('view.optionalDatatypes',$optionalDatatypes);	
		$this->Session->write('projectId',$this->Project->getProjectId($dataset));
		$this->Session->write('projectName',$this->Project->getProjectName($dataset));
		$this->Session->write('view.numHits',$numHits);
		$this->Session->write('view.hits',$hits);

		//set post variables
		$this->set('dataset',$dataset);
		$this->set('page',$page);
	}
	
	function facet($dataset='CBAYVIR',$facetField,$prefix ='',$limit=20) {		
	
		if(empty($this->data['Post'])) {
			if($this->Session->check('view.limit')) {
				$limit = $this->Session->read('view.limit');
			}
			if($this->Session->check('view.filter')) {
				$filter = $this->Session->read('view.filter');
			}			
		}
		else {
			if(!empty($this->data['Post']['limit'])) {
				$limit = $this->data['Post']['limit'];
				$this->Session->write('view.limit',$limit);
			}
			//reset filter variables if no option '-select filter--' has been selected
			if(empty($this->data['Post']['filter'])) {	
				if($this->Session->check('view.filter')) {
					$this->Session->delete('view.filter');
				}
			}
			elseif(!empty($this->data['Post']['filter'])) {
				$filter = $this->data['Post']['filter'];
				$this->Session->write('view.filter',$filter);
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
			
		if(isset($prefix)) {
			$solrArguments['facet.prefix'] =$prefix;
		}
		
		try {
			$result= $this->Solr->search($dataset,$query, 0,0,$solrArguments,true);
		}
		catch(Exception $e) {
			$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
			$this->redirect('/projects/index');
		}
			
		$numHits= (int) $result->response->numFound;
		$facetCounts = $result->facet_counts;
				
		//write session variables
		$this->Session->write('view.facetCounts',$facetCounts);		
		$this->Session->write('view.limit',$limit);
		$this->Session->write('view.numHits',$numHits);	
						
		$this->set('dataset',$dataset);
		$this->set('facetField',$facetField);
		$this->render('result_panel','ajax');
	}
	
	function pathways($dataset,$facetField) {	
		
		$numHits  = $this->Session->read('view.numHits');
		
		if(empty($this->data['Post'])) {
			if($this->Session->check('view.filter')) {
				$filter = $this->Session->read('view.filter');
			}			
		}
		else {
			//reset filter variables if no option '-select filter--' has been selected
			if(empty($this->data['Post']['filter'])) {	
				if($this->Session->check('view.filter')) {
					$this->Session->delete('view.filter');
				}
			}
			elseif(!empty($this->data['Post']['filter'])) {
				$filter = $this->data['Post']['filter'];
				$this->Session->write('view.filter',$filter);
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
						'facet.field' => 'ec_id',
						'facet.mincount' => 1,
						"facet.limit" => -1);		
		try {
			$result= $this->Solr->search($dataset,$query,0,0,$solrArguments,false);
		}
		catch(Exception $e) {
			$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
			$this->redirect('/projects/index');
		}
			
		$numHits= (int) $result->response->numFound;
		
		$solrEcIdHash = (array) $result->facet_counts->facet_fields->ec_id;
		
		$pathways 	  = array();
	
		$solrEcIds 	  = array_keys($solrEcIdHash);
		
				
		$level2Results= $this->Pathway->find('all', array('fields'=> array('id','name'),'conditions' => array('level' => 'level 2')));
		
		foreach($level2Results as $level2Result) {
			$level2Results 	= array();
			
			$level2Id 		= $level2Result['Pathway']['id'];
			$level2Name		= $level2Result['Pathway']['name'];
				
			$level3Results= $this->Pathway->find('all', array('fields'=> array('id','name','kegg_id','child_count','level'),'conditions' => array('parent_id' =>$level2Id,'child_count >'=>'0')));
			
			foreach($level3Results as $level3Result) {

				#for each pathway in level 2 determine the number of enzymes in the dataset
				$numFoundEnzymes 	= 0;
				$numPeptides 		= 0;
				
				$pathwayId 			= 	$level3Result['Pathway']['id'];
				$pathwayKeggId 		=   str_pad($level3Result['Pathway']['kegg_id'],5,0,STR_PAD_LEFT);			
				
				
				$pathwayName		= $level3Result['Pathway']['name'];
				$pathwayEnzymeCount	= $level3Result['Pathway']['child_count'];
				$pathwayLevel		= $level3Result['Pathway']['level'];

				$results= $this->Solr->getPathwayCount($query,$dataset,$pathwayLevel,$pathwayId,$pathwayEnzymeCount,null);

				$percentEnzymes = round($numFoundEnzymes/$pathwayEnzymeCount,4)*100;
				
				$percentPeptides = round($results['count']/$numHits,4)*100;	
				
				array_push($level2Results,array('id'=>$pathwayKeggId,
												'pathway'=>$pathwayName,
												'link'=>$results['pathwayLink'],
												'numPathwayEnzymes'=>$pathwayEnzymeCount,
												'numFoundEnzymes'=>$results['numFoundEnzymes'],
												'percFoundEnzymes'=>$results['percFoundEnzymes'],
												'numPeptides'=>$results['count'],
												'percPeptides'=>$percentPeptides,
				));
			}
			
			#usort($level2Results, array('ViewController','comparePercentEnzymes'));
			#usort($level2Results, array('ViewController','comparePeptideCount'));
			
			$pathways[$level2Name]=$level2Results;		
		}
		
		$this->Session->write('view.pathways',$pathways);					
		$this->set('dataset',$dataset);
		$this->set('facetField',$facetField);
		$this->render('result_panel','ajax');
	}
	
	#function comparePercentEnzymes($a, $b) { return strnatcmp($b['percFoundEnzymes'], $a['percFoundEnzymes']); } 
	private function comparePeptideCount($a, $b) { return strnatcmp($b['numPeptides'], $a['numPeptides']); } 
	
	function download($dataset,$facetField) {
		$this->autoRender=false; 
		
		$numHits = $this->Session->read('view.numHits');
		$limit = $this->Session->read('view.limit');
		
		$facetName = '';
		
		switch ($facetField) {
			case 'blast_species':
				$facetName='Blast Species';
				break;
			case 'com_name':
				$facetName="Common Name";
				break;
			case 'go_id':
				$facetName="Gene Ontology";
				break;
			case 'ec_id':
				$facetName="Enzyme";
				break;	
			case 'hmm_id':
				$facetName="HMM";
				break;		
			case 'cluster_id':
				$facetName="Cluster";
				break;	
			case 'pathway_id':
				$facetName="Pathway";
				break;	
			case 'filter_id':
				$facetName="Filter";
				break;				
		}	
		
		if($this->Session->check('view.filter')) {
			$filter = $this->Session->read('view.filter');
			$query = "filter:$filter";	
		}
		else {
			$query = '*:*';
		}		
		
		#pathway data has to handled differently since it is not a lucene facet data type
		if($facetField === 'pathway_id') {				
			$pathways = $this->Session->read('view.pathways');
			$content = $this->Format->infoString("$facetName Categories ",$dataset,$query,0,$numHits);	
			$content.= $this->Format->pathwayToDownloadString($pathways,$numHits);
		}
		else {			
			$facets = $this->Session->read('view.facetCounts');		
			$content = $this->Format->infoString("Top $limit $facetName Categories ",$dataset,$query,0,$numHits);		
			$content.= $this->Format->facetToDownloadString($facetName,$facets->facet_fields->{$facetField},$numHits);	
		}

		#generate download file name
		$fileName = "jcvi_metagenomics_report_".time().'.txt';
		
		#prepare for download
        header("Content-type: text/plain"); 
        header("Content-Disposition: attachment;filename=$fileName");
       
        echo $content;
	}
}
?>