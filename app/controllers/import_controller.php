<?php
/***********************************************************
 * File: import_controller.php
 * Description: imports user-defined files, shows a validation
 * summary, and creates a Lucene/Solr
 * index file.
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

class ImportController extends AppController {

	var $name 		= 'Import';
	var $uses 		= array();
	var $components = array('Solr','Format','RequestHandler','Session');
	var $helpers 	= array('LuceneResultPaginator');
	var $fieldNames = array(
						'Peptide Id',
						'Library ID',
						'Common Name',
						'Commmon Name Source',
						'GO ID',
						'Go Source',
						'EC ID',
						'EC Source',
						'HMM ID',
						'NCBI Taxon',
						'Blast E-Value',
						'Blast %Identity',
						'Blast Coverage',
						'Filter',
						'KO ID',
						'KO Source',
						'Weight');

	/**
	 * Allow users to upload files
	 *
	 * @return void
	 * @access public
	 */
	function index($projectId) {		
		$this->loadModel('Project');
		$projectName = $this->Project->getProjectNameById($projectId);
		$this->set('projectId',$projectId);
		$this->set('projectName',$projectName);
		$this->Session->write('projectName',$projectName);
	}

	/**
	 * validate entries with pagination support
	 * 
	 * @param  int $startPage integer to indicate the page index for pagination 
	 * @param  int $projectId project identifier  
	 * @return void
	 * @access public
	 */
	function validation($startPage=1,$projectId) {
		
		## check if project ID was provided
		if(empty($projectId)) {
			$this->Session->setFlash("There was a problem executing your request. Missing project ID");
			$this->redirect("/projects/index",null,true);			
		}
		
		## upload file
		if(!empty($this->data)) {							
			$fileName = $this->data['Import']['File']['name'];
			$fileSize = $this->data['Import']['File']['size'];
			$sourceFilePath = $this->data['Import']['File']['tmp_name'];			
			
			## create _import subdirectory by project id			
			$targetDir = METAREP_TMP_DIR."/".$projectId."_imports";	
			$targetFilePath = $targetDir.'/'.$fileName;
									
			## remove existing results
			if(is_dir($targetDir)) {
				exec("rm $targetDir/*");	
			}
			else {
				mkdir($targetDir);
			}						
			
			## upload file
			move_uploaded_file($sourceFilePath, $targetFilePath);
			
			if(!file_exists($targetFilePath)) {
				$this->Session->setFlash("There was a problem with uploading your file to the server.");
				$this->redirect("/projects/view/$projectId",null,true);						
			}
			else {			
				$this->Session->write('filePath',$targetFilePath);
				$fileLineCount = exec("wc -l $targetFilePath");
				$fileLineCount = str_replace($targetFilePath,'',$fileLineCount);
				$fileLineCount = trim($fileLineCount);
				$this->Session->write('fileLineCount',$fileLineCount);
			}
		}		
		## for pagination read file location from session
		else {			
			$targetFilePath = $this->Session->read('filePath');
			$fileLineCount  = $this->Session->read('fileLineCount');
		}
		
		$projectName 	= $this->Session->read('projectName');
		
		$entries = array();
		$handle = fopen($targetFilePath, "r");

		## 0 index based start page
		$startEntry = (($startPage-1)*20);
		$i = $startEntry;
		
		## set maximum start entry
		if($startEntry > $fileLineCount-1) {
			$startEntry = $fileLineCount-1;
		}
		
		## read the top 20 entries in the file
		for($i=0;sizeof($entries) < 20 || feof($handle);$i++) {
			$line = trim(fgets($handle, 1000000));
			
			if($i>=$startEntry) { 
				$fields = explode("\t",$line);
	
				foreach($fields as &$field) {
					trim($field);
				}
				## fill-in empty fields
				for($f = sizeof($fields);$f-1 < 17;$f++) {
					$fields[$f]="";
				}
				
				array_push($entries,implode("\t",$fields));
			}
		}

		## init validation array
		$validation = array_fill(0,sizeof($entries),array_fill(0,17,"valid entry"));
		$validationProblems = 0;
		
		## validate the top 20 entries
		for($i =0; $i<sizeof($entries);$i++) {
			$entry = $entries[$i];
			$fields = explode("\t",$entry);

			## check required fields
			$requiredFields = array(0,1);
			foreach($requiredFields as $requiredField) {
				if(empty($fields[$requiredField])) {
					$validation[$i][$requiredField] ='non-empty value required';					
				}
			}		
				
			## check numeric positive fields
			$numericFields = array(10,11,12,13,17);
			foreach($numericFields as $numericField) {
				if(!is_numeric(trim($fields[$numericField])) && !empty($fields[$numericField])) {
					$validation[$i][$numericField]='numeric value required';
					$validationProblems++;
				}
				else if(trim($fields[$numericField]) < 0 && !empty($fields[$numericField])) {
					$validation[$i][$numericField]='positive numeric value required';
					$validationProblems++;
				}
			}
				
			$singleValuedFields = array(0,1,10,11,12,17);
			foreach($singleValuedFields as $singleValuedField) {
				if(preg_match('/\|\|/',$fields[$singleValuedField]) && !empty($fields[$singleValuedField])) {
					$validation[$i][$singleValuedField]='single value required';
					$validationProblems++;
				}
			}
				
			## check GO field
			if(!preg_match('/^GO:\d{7}/',$fields[4]) && !empty($fields[4])) {
				$validation[$i][4] ='mal-formatted GO accession';
				$validationProblems++;
			}
			## check HMM field
			if(!preg_match('/^PF\d{5}|^TIGR\d{5}|^SSF\d{5}/',$fields[8]) && !empty($fields[8])) {
				$validation[$i][8]='unsupported hmm accession';
				$validationProblems++;
			}
				
			## check integer field
			if(!ctype_digit($fields[9]) && !empty($fields[9])) {
				$validation[$i][9]='integer value required';
				$validationProblems++;
			}
				
			## check proportion fields
			if($fields[11] > 1 && !empty($fields[11])) {
				$validation[$i][11]='value needs to be between 0-1';
				$validationProblems++;
			}
			if($fields[12] > 1 && !empty($fields[11])) {
				$validation[$i][12]='value needs to be between 0-1';
				$validationProblems++;
			}

			## check enzyme field
			if(!preg_match('/^.*\..*\..*\..*$/',$fields[6]) && !empty($fields[6])) {
				$validation[$i][6] ='mal-formatted enzyme accession';
				$validationProblems++;
			}
			$library = $fields[1];
			
		} //end foreach
		
		$this->set('entries',$entries);
		$this->set('validation',$validation);
		$this->set('validationProblems',$validationProblems);
		$this->set('projectId',$projectId);
		$this->set('fieldNames',$this->fieldNames);	
		$this->set('fileLineCount',$fileLineCount);	
		$this->set('library',$library);	
		$this->set('page',$startPage);	
		$this->set('projectName',$projectName);	
			
	} //end function
	
	
	/**
	 * Create Lucene/Solr index; show spinner
	 * 
	 * @param  int $startPage integer to indicate the page index for pagination 
	 * @param  int $projectId project identifier  
	 * @return void
	 * @access public
	 */
	function import($projectId,$library) {
		$this->loadModel('Library');
		
				
		## set parameters
		$projectName 	= $this->Session->read('projectName');
		$targetFilePath = $this->Session->read('filePath');
		$fileLineCount  = $this->Session->read('fileLineCount');
		$projectDir		= METAREP_TMP_DIR."/$projectId".'_imports';
		$webRoot		= METAREP_WEB_ROOT;
		$sqlitePath		= (defined('METAREP_SQLITE_DB_PATH')) ? METAREP_SQLITE_DB_PATH : "$webRoot/db/metarep.sqlite3.db";			
		$solrMasterUrl	= SOLR_MASTER_HOST.":".SOLR_PORT;
		$solrSlaveUrl	= SOLR_SLAVE_HOST.":".SOLR_PORT;
		$solrInstanceDir= SOLR_INSTANCE_DIR;
		$solrHomeDir	= SOLR_HOME_DIR;	
		$dbConfig		= get_class_vars('DATABASE_CONFIG');
		$tmpDir 		= METAREP_TMP_DIR;				
		
		## execute systems command to start index process	
		$cmd = PERL_PATH." $webRoot/scripts/perl/metarep_loader.pl 
				--project_id=$projectId 
				--project_dir=$projectDir
				--format=tab 
				--sqlite_db=$sqlitePath
				--solr_url=http://$solrMasterUrl 
				--solr_slave_url=http://$solrSlaveUrl 				
				--solr_instance_dir=$solrInstanceDir
				--mysql_host={$dbConfig['default']['host']}
				--mysql_db={$dbConfig['default']['database']}
				--mysql_username={$dbConfig['default']['login']}
				--mysql_password={$dbConfig['default']['password']}
				--tmp_dir $tmpDir";	
		
		
		
		$cmd = preg_replace("/\s++/",' ',$cmd);
		
		$descriptorspec = array(
		   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		   2 => array("pipe", "w") // stderr is a pipe that the child will read from
		);
		//flush();
		
		$process = proc_open($cmd, $descriptorspec, $pipes, './', array());
		//echo "<pre>";
		if (is_resource($process)) {
		    while ($s = fgets($pipes[1])) {
		       // print $s;
		       //flush();
		    }
		}
		
		$result = $this->Library->findByName($library);

		$this->redirect("/libraries/edit/{$result['Library']['id']}/$projectId",null,true);		
	}		
} // end class
?>