<?php

/***********************************************************
*  File: view_controller.php
*  Description: Controller to handle basic fact requests based
*  on the full dataset.
*
*  Author: jgoll
*  Date:   Feb 16, 2010
************************************************************/

class ViewController extends AppController {
	var $name = 'View';

	var $limit = 10;

	//top number of classification returned
	var $numFacetCounts=10;

	var $helpers = array('LuceneResultPaginator','Facet','Ajax');
	
	var $uses = array('Project','Population','Library','GoTerm','GoGraph','Enzymes','Hmm','Pathway');
	
	var $components = array('Solr','Format');
	
	//this function lets us view all detail of the lucene index
	function index($dataset='CBAYVIR',$page=1) {
	
		
		$this->Project->unbindModel(array('hasMany' => array('Population'),),false);
		$this->Project->unbindModel(array('hasMany' => array('Library'),),false);
		
		$optionalDatatypes  = $this->Project->checkOptionalDatatypes(array($dataset));
		
		$displayLimit 	= 20;
		$facetMaxCount 	= 50;
				
		//specify facet default behaviour
		$solrArguments = array(	'fl' => 'peptide_id com_name com_name_src blast_species blast_evalue go_id go_src ec_id ec_src hmm_id');
		
		$result= $this->Solr->search($dataset,"*:*", ($page-1)*$displayLimit,$displayLimit,$solrArguments);
			
		$numHits= (int) $result->response->numFound;
		$facets = $result->facet_counts;
		$hits 	= $result->response->docs;
		
		$this->Session->write('optionalDatatypes',$optionalDatatypes);
		
		$this->set('projectId', $this->Project->getProjectId($dataset));
		$this->set('projectName', $this->Project->getProjectName($dataset));
		$this->set('hits',$hits);
		$this->set('dataset',$dataset);
		$this->set('numHits',$numHits);
		$this->set('facets',$facets);
		$this->set('page',$page);
		$this->set('limit',$this->limit);
	}
	
	function facet($dataset='CBAYVIR',$facetField,$prefix ='',$limit=20) {		
	
		#if post data has not been selected use default level
		if(!empty($this->data['Post']['limit'])) {
			$limit = $this->data['Post']['limit'];
		}		
		
		$facetArray = array($facetField);
		
		//specify facet default behaviour
		$solrArguments = array(	"facet" => "true",
						'facet.field' => $facetArray,
						'facet.mincount' => 1,
						"facet.limit" => $limit);
		
		if($prefix) {
			$solrArguments['facet.prefix'] =$prefix;
		}
		
		try {
			$result= $this->Solr->search($dataset,"*:*", 0,0,$solrArguments,true);
		}
		catch(Exception $e) {
			$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
			$this->redirect('/projects/index');
		}
			
		$numHits= (int) $result->response->numFound;
		$facets = $result->facet_counts;
				
		//store facet for download
		$this->Session->write('facet',$facets);		
		
		$this->set('projectName', $this->Project->getProjectName($dataset));
	
		$this->set('dataset',$dataset);
		$this->set('numHits',$numHits);
		$this->set('facets',$facets);
		$this->set('limit',$limit);
		$this->set('facetField',$facetField);
		$this->render('facet_panel','ajax');
	}
	
	function pathways($dataset,$facetField,$prefix ='',$limit=20) {	
		
		#if post data has not been selected use default level
		if(!empty($this->data['Post']['limit'])) {
			$level= $this->data['Post']['limit'];
		}
		
		//specify facet default behaviour
		$solrArguments = array(	"facet" => "true",
						'facet.field' => 'ec_id',
						'facet.mincount' => 1,
						"facet.limit" => -1);		
		try {
			$result= $this->Solr->search($dataset,"*:*", 0,0,$solrArguments,false);
		}
		catch(Exception $e) {
			$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
			$this->redirect('/projects/index');
		}
			
		$numHits= (int) $result->response->numFound;
		
		$facets = $result->facet_counts->facet_fields->ec_id;
		
		$pathways 	  = array();
		$solrEcIdHash = array();
				
		#write ec facets into hash
		foreach($facets as $ecId=>$count) {
			$solrEcIdHash[$ecId]=$count;
		}
		#debug($solrEcIdHash);
		#store found ecs in array for fuzzy matching
		$solrEcIds = array_keys($solrEcIdHash);
				
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

				$results= $this->Solr->getPathwayCount('*:*',$dataset,$pathwayLevel,$pathwayId,$pathwayEnzymeCount,null);
				
				
				#$enzymeResults= $this->Pathway->find('all', array('fields'=> array('ec_id'),'conditions' => array('parent_id' =>$pathwayId)));
							
//				foreach($enzymeResults as $enzymeResult) {
//					$pathwayEcId = $enzymeResult['Pathway']['ec_id'];
//						
//					#do fuzzy enzyme id matching for pathway enzymes that are defined at a higher level, e.g. contain '-'
//					if(preg_match('/-/',$pathwayEcId)) {
//						$hasSolrMatch=false;
//						#$tmp = explode('-',$pathwayEcId);
//						#$pathwayEcId = $tmp[0];
//
//						$matchEcId = str_replace('.','\.',$pathwayEcId);
//						$matchEcId = "/".str_replace('-','.*',$matchEcId)."/";
//							debug($matchEcId);					
//						foreach($solrEcIds as $solrEcId) {
//							
//							if(preg_match($matchEcId,$solrEcId)) {
//							
//							#if solr ec id starts with pathway enzyme id
////							if(strpos($solrEcId, $pathwayEcId) === 0) {
////								$hasSolrMatch = true;
//								#debug("$solrEcId:$pathwayEcId");
//								$numPeptides  += $solrEcIdHash[$solrEcId];
//							}
//						}
//						if($hasSolrMatch) {
//							$pathwayLink.="+$pathwayEcId";
//							$numFoundEnzymes ++;
//						}
//					}
//					else {
//						if(array_key_exists($pathwayEcId ,$solrEcIdHash)) {
//							$numPeptides  += $solrEcIdHash[$pathwayEcId];
//							$pathwayLink.="+$pathwayEcId";
//							$numFoundEnzymes ++;
//						}
//					}
//				}

				$percentEnzymes = round($numFoundEnzymes/$pathwayEnzymeCount,4)*100;
				
				array_push($level2Results,array('id'=>$pathwayKeggId,
												'pathway'=>$pathwayName,
												'link'=>$results['pathwayLink'],
												'numPathwayEnzymes'=>$pathwayEnzymeCount,
												'numFoundEnzymes'=>$results['numFoundEnzymes'],
												'percFoundEnzymes'=>$results['percFoundEnzymes'],
												'numPeptides'=>$results['count'],
				));
			}
			
			#usort($level2Results, array('ViewController','comparePercentEnzymes'));
			#usort($level2Results, array('ViewController','comparePeptideCount'));
			
			$pathways[$level2Name]=$level2Results;		
		}
		
		$this->Session->write('pathways',$pathways);				
		$this->set('projectName', $this->Project->getProjectName($dataset));
		$this->set('dataset',$dataset);
		$this->set('numHits',$numHits);
		$this->set('pathways',$pathways);
		$this->set('limit',$limit);
		$this->set('facetField',$facetField);
		$this->render('facet_panel','ajax');
	}
	
	#function comparePercentEnzymes($a, $b) { return strnatcmp($b['percFoundEnzymes'], $a['percFoundEnzymes']); } 
	function comparePeptideCount($a, $b) { return strnatcmp($b['numPeptides'], $a['numPeptides']); } 
	
	function apis($projectId,$link) {
		$link = base64_decode($link);
		$this->set('link',$link);
		$this->set('projectId',$projectId);
		$this->render('apis','empty');
	}

	function ftp($projectId,$dataset) {
		
		$fileName = "$dataset.tgz";
		$filePath = "ftp://metarep:k54raCRepene@ftp.jcvi.org/$projectId/$fileName";
		
		//open binary file
		$fp = fopen($filePath,"rb");
		
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
						
		header('Content-Type: application/x-compressed');
		header("Content-Transfer-Encoding: binary");
		
		if (strstr($userAgent,'IE')) {
			header("Content-Disposition: inline; filename=\"" . $fileName ."\"");			
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
		} else {
			header("Content-Disposition: attachment; filename=\"" . $fileName ."\"");
			header('Pragma: no-cache');
		}
		
		fpassthru($fp);
		exit();
	}	
	
	function download($dataset,$facetField,$numHits,$limit) {
		$this->autoRender=false; 
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
		
		#pathway data has to handled differently since it is not a lucene facet data type
		if($facetField === 'pathway_id') {				
			$pathways = $this->Session->read('pathways');
			$content = $this->Format->infoString("$facetName Categories ",$dataset,'*:*',$numHits);	
			$content.= $this->Format->pathwayToDownloadString($pathways,$numHits);
		}
		else {			
			$facets = $this->Session->read('facet');		
			$content = $this->Format->infoString("Top $limit $facetName Categories ",$dataset,'*:*',$numHits);		
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