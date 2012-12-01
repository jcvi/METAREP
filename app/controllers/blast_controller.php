<?php
/***********************************************************
* File: blast_controller.php
* Description: The Blast controller allows users to blast
* an input sequence against a selection of datasets.
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
* @version METAREP v 1.3.2
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

class BlastController extends AppController {

	var $name 		= 'Blast';	
	var $helpers 	= array('Matrix','Dialog','Ajax','Html');
	var $uses 		= array();
	var $components = array('Solr','RequestHandler','Session','Format','Download','Blast'); 
	
	var $annotationFields = array(
								'peptide_id'=>'Peptide ID',
								'com_name'=>'Common Name',
								#'com_name_src'=>'Common Name Source',
								'blast_species'=>'Blast Species',
								#'blast_evalue'=>'Blast E-Value',
								#'go_id'=>'GO ID',
								#'go_src'=>'GO Source',
								'ec_id'=>'EC ID',
								#'ec_src'=>'EC Source',
								'hmm_id'=>'HMM',
								);	

	var $blastFields = array(
								'query_id'=>'Query ID',
								'subject_id'=>'Subject ID',
								'identity'=>'% Identity',
								'alignment'=>'Aln. Length',
								'miss'=>'Missmatches',
								'gap'=>'Gap Openings',
								'q_start'=>'Q. Start',
								'q_end'=>'Q. End',
								's_start'=>'S. Start',
								's_end'=>'S. End',	
								'evalue'=>'E-Value',
								'bit_score'=>'Bit Score',
								);	

	## defines the blast fields that are shown in the annotation tab						
	var $annotationBlastFieldIndex = array(2,10,11);
							
	/**
	 * Initializes Blast index page
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
		set_time_limit(0);
				
		$this->pageTitle = 'Blast Against Datasets';

		//set default values
		if(!$this->Session->check('filter')) {
			$this->Session->write('filter',"*:*");
		}	
		if(!$this->Session->check('option')) {
			$this->Session->write('option',ABSOLUTE_COUNTS);
		}						
		if(!$this->Session->check('sequence')) {
			$this->Session->write('sequence',"");
		}
		if(!$this->Session->check('wordCount')) {
			$this->Session->write('wordCount',3);
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
		$this->Session->write('evalue','1E-5');
		
		$this->set('projectName',$this->Project->getProjectName($dataset));
		$this->set('projectId', $projectId);
		$this->set('selectedDatasets', $selectedDatasets);
		$this->set('dataset', $dataset);
		
		$this->set('mode', $mode);	
	}
	
	/**
	 * Function activates the tab panel though an ajax call. The function is executed via an ajax call 
	 * when the use clicks on the update button on the Blast index page
	 * 
	 * @return void
	 * @access public
	 */		
	function ajaxTabPanel() {
		$this->loadModel('Project');
		
		$sequence			= trim($this->data['Blast']['sequence']);		
		$option				= $this->data['Blast']['option'];
		$filter				= $this->data['Blast']['filter'];
		$evalue				= $this->data['Blast']['evalue'];		
		
		
		$tabs = array(
					array('function'=>'annotation','isActive'=>1,'tabName' => 'Annotation'),	
					array('function'=>'alignment','isActive'=>1,'tabName' => 'Alignment'),
					array('function'=>'tab','isActive'=>1,'tabName' => 'Tabular'),				
								
		);		

		## validation of sequence 
		if(empty($sequence)) {				
			$this->set('multiSelectException','Please enter a sequence.');
			$this->set('filter',$filter);
			$this->render('/compare/result_panel','ajax');
		}		
		else {			
			if(substr_count($sequence,'>') > 1) {				
				$this->set('multiSelectException','Please enter only one sequence.');
				$this->set('filter',$filter);
				$this->render('/compare/result_panel','ajax');
			}
		}		
			
		if(isset($this->data['selectedDatasets'])) {		
			
			$selectedDatasets	= $this->data['selectedDatasets'];		
			
			if(empty($option)) {
				$option = ABSOLUTE_COUNTS;
			}
			if(empty($filter)) {
				$filter = 	"*:*";
			}
		
			//write variables to sessions				
			$this->Session->write('sequence',$sequence);
			$this->Session->write('blastFields',$this->blastFields);
			$this->Session->write('option',$option);	
			$this->Session->write('evalue',$evalue);			
			$this->Session->write('filter',$filter);
			$this->Session->write('selectedDatasets',$selectedDatasets);
			$this->Session->write('tabs',$tabs);	
			$this->render('/blast/tab_panel','ajax');
		}
		else {
			#handle select datasets exception
			$this->set('multiSelectException','Please select a dataset.');
			$this->set('filter',$filter);
			$this->render('/blast/tab_panel','ajax');
			#$this->render('/blast/result_panel','ajax');
		}
	}
	
	public function alignment(){
		
		## read session variables
		$sequence 			= $this->Session->read('sequence');
		$option 			= $this->Session->read('option');
		$evalue 			= $this->Session->read('evalue');
		$filter 			= $this->Session->read('filter');
		$selectedDatasets	= $this->Session->read('selectedDatasets');
		$totalCounts		= $this->Session->read('totalCounts');

		## write sequence to file
		$fastaFileName 	= uniqid('jcvi_metagenomics_report_').'.fasta';
		$fastaFilePath  = METAREP_TMP_DIR."/$fastaFileName";
		$fh = fopen($fastaFilePath, 'w');
		fwrite($fh, $sequence);
		fclose($fh);
		
		$htmlFileName  = uniqid('jcvi_metagenomics_report_').'.html';
		$htmlFilePath  = METAREP_TMP_DIR."/$htmlFileName";
		$txtFileName  = uniqid('jcvi_metagenomics_report_').'.txt';
		$txtFilePath  = METAREP_TMP_DIR."/$txtFileName";
		
		## generate both html and text files 
		$this->Blast->writeBlastpAlnHtml($selectedDatasets,$fastaFilePath,$htmlFilePath,$evalue);
		$this->Blast->writeBlastpAlnText($selectedDatasets,$fastaFilePath,$txtFilePath,$evalue);
		unlink($fastaFilePath);
		
		## save result files in session variable
		$this->Session->write('blastTextFileName',$txtFileName);
		$this->Session->write('blastTextFilePath',$txtFilePath);
		$this->Session->write('mode',__FUNCTION__);
		
		$this->set(compact('mode','counts','filter','option','wordCount','selectedDatasets','level','levels','test','sequence','htmlFilePath'));
		$this->render('/blast/alignment_output','ajax');
	}
	
	public function tab(){
		
		//read session variables
		$sequence 			= $this->Session->read('sequence');
		$option 			= $this->Session->read('option');
		$evalue 			= $this->Session->read('evalue');
		$filter 			= $this->Session->read('filter');
		$selectedDatasets	= $this->Session->read('selectedDatasets');
		$totalCounts		= $this->Session->read('totalCounts');

		// write sequence to file
		$fastaFileName 	= uniqid('jcvi_metagenomics_report_').'.fasta';
		$fastaFilePath  = METAREP_TMP_DIR."/$fastaFileName";
		$fh = fopen($fastaFilePath, 'w');
		fwrite($fh, $sequence);
		fclose($fh);
		
		$tabFileName  = uniqid('jcvi_metagenomics_report_').'.tab';
		$tabFilePath  = METAREP_TMP_DIR."/$tabFileName";	
	
		$this->Blast->writeBlastpTab($selectedDatasets,$fastaFilePath,$tabFilePath,$evalue);
		unlink($fastaFilePath);
		
		$tabResult = file($tabFilePath);
		
		$this->Session->write('blastTextFileName',$tabFileName);
		$this->Session->write('blastTextFilePath',$tabFilePath);
		$this->Session->write('mode',__FUNCTION__);
		
		$blastFields = $this->blastFields;
		
		$this->set(compact('mode','counts','filter','option','wordCount','selectedDatasets','blastFields','level','levels','test','sequence','tabResult'));
		$this->render('/blast/tab_output','ajax');
	}	

	public function annotation(){
		$this->loadModel('Project');
		//read session variables
		
		$sequence 			= $this->Session->read('sequence');
		$option 			= $this->Session->read('option');
		$evalue 			= $this->Session->read('evalue');
		$filter 			= $this->Session->read('filter');
		$selectedDatasets	= $this->Session->read('selectedDatasets');
		$totalCounts		= $this->Session->read('totalCounts');

		$fastaFileName 	= uniqid('jcvi_metagenomics_report_').'.fasta';
		$fastaFilePath  = METAREP_TMP_DIR."/$fastaFileName";
		$fh = fopen($fastaFilePath, 'w');
		fwrite($fh, $sequence);
		fclose($fh);
		
		$tabFileName  = uniqid('jcvi_metagenomics_report_').'.tab';
		$tabFilePath  = METAREP_TMP_DIR."/$tabFileName";	
		
		$this->Blast->writeBlastpTab($selectedDatasets,$fastaFilePath,$tabFilePath,$evalue);
		unlink($fastaFilePath);
		
		$tabResult = file($tabFilePath);
		
		if(empty($tabResult)) {
			$this->set('message','No hits found. Please try again with different options.');
			$this->render('/blast/annotation_output','ajax');
			exit;			
		}
		
		$query = 'peptide_id:(';
		$annotationResult = array();
		$blastResults = array();
		
		$annotationFields = $this->annotationFields; 

		$blastFields = $this->blastFields;
		
		## add KO if part of index
		$optionalDatatypes  = $this->Project->checkOptionalDatatypes($selectedDatasets);
		if($optionalDatatypes['ko']) {					
			$annotationFields['ko_id']='KEGG Ortholog';
		}	
					
		$blastFieldKeys = array_keys($blastFields);
		
		## store best HSP in protein hash
		foreach($tabResult as $row) {			
			$fields = explode("\t",trim($row),3);
			$proteinId = str_replace('@','|',$fields[1]);
			
			## only add first HSP
			if(!array_key_exists($proteinId,$blastResults)) {
				$blastResults[$proteinId] = trim($row);
			}
		}
			
		$query .= join(' OR ',array_keys($blastResults)).") AND ($filter)";
		
		
		$solrFields = join(',',array_keys($annotationFields));
		
		$allAnnotations = array();		
		
		foreach($selectedDatasets as $dataset) {
			try {
				$count = $this->Solr->documentCount($dataset,$query);
				$annotations = $this->Solr->fetch($dataset,$query,$solrFields,0,$count);
			}
			catch (Exception $e) {
				$this->Session->setFlash("METAREP Lucene Query Exception. Please correct your query and try again.");
				$this->redirect(array('action' => 'index'),null,true);
			}
			
			if(!empty($annotations)) {					
				foreach($annotations as $annotation) {
					
					$blastFields = explode("\t",$blastResults[$annotation->peptide_id]);		
					foreach($this->annotationBlastFieldIndex as $selectedBlastFieldIndex) {								
						$annotation->{$blastFieldKeys[$selectedBlastFieldIndex]} = $blastFields[$selectedBlastFieldIndex];
					} 		
					$annotation->{'dataset'} = $dataset;
					array_push($allAnnotations,$annotation);
				}								
			}
		}
		$annotations = $allAnnotations;
		
		$annotationFields['dataset'] = 'Dataset';
		foreach($this->annotationBlastFieldIndex as $selectedBlastFieldIndex) {	
			$annotationFields[$blastFieldKeys[$selectedBlastFieldIndex]] = $this->blastFields[$blastFieldKeys[$selectedBlastFieldIndex]];
		}	
		$this->Session->write('numHits',sizeof($annotations));	
		$this->Session->write('annotations',$annotations);
		$this->Session->write('annotationFields',$annotationFields);
		$this->Session->write('blastTextFileName',$tabFileName);
		$this->Session->write('blastTextFilePath',$tabFilePath);
		$this->Session->write('mode',__FUNCTION__);
		
		
		$this->set(compact('mode','counts','filter','option','wordCount','selectedDatasets','sequence','annotations','annotationFields'));
		$this->render('/blast/annotation_output','ajax');
	}	
	
	public function download() {
		
		$mode 				= $this->Session->read('mode');			
		$sequence 			= $this->Session->read('sequence');
		$option 			= $this->Session->read('option');
		$evalue 			= $this->Session->read('evalue');
		$filter 			= $this->Session->read('filter');
		$numHits 			= $this->Session->read('numHits');
		$selectedDatasets	= $this->Session->read('selectedDatasets');
		$totalCounts		= $this->Session->read('totalCounts');
		$annotations		= $this->Session->read('annotations');
		$annotationFields		= $this->Session->read('annotationFields');
		
		if($mode == 'annotation') {
			$title = "Blast Results - Annotations";
			$content  = $this->Format->infoString($title,$selectedDatasets,$filter,0,$numHits,'',$evalue,$sequence);
			$content .= $this->Format->blastAnnotationsToDownloadString($annotations,$annotationFields);
			$fileName = uniqid('jcvi_metagenomics_report_').'.txt';
			$this->Download->string($fileName,$content);					
		}
		else {			
			$textFileName = $this->Session->read('blastTextFileName');
			$textFilePath = $this->Session->read('blastTextFilePath');
			$this->Download->textFile($textFileName,$textFilePath);
		}			
	}
}
?>