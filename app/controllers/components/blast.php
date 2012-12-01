<?php
/***********************************************************
* File: blast.php
* Wrapper for blast related tools 
* blastall 2.2.15
* fastacmd 2.2.15
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
* @lastmodified 2012-06-19
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

class BlastComponent extends BaseModelComponent {

	var $numBlastCpu = 2;
	
	public function writeBlastpAlnHtml($datasets,$input,$output,$minEvalue) {
		return $this->runBlast('blastp',$datasets,$input,$output,$minEvalue,'-T T');
	}	

	public function writeBlastpAlnText($datasets,$input,$output,$minEvalue) {
		return $this->runBlast('blastp',$datasets,$input,$output,$minEvalue);
	}

	public function writeBlastpTab($datasets,$input,$output,$minEvalue) {
		return $this->runBlast('blastp',$datasets,$input,$output,$minEvalue,'-m 8');
	}
	
	public function getSubjectIdQueryBySequence($datasets,$sequence,$evalue) {
		
		// write sequence to file
		$fastaFileName 	= uniqid('jcvi_metagenomics_report_').'.fasta';
		$fastaFilePath  = METAREP_TMP_DIR."/$fastaFileName";
		$fh = fopen($fastaFilePath, 'w');
		fwrite($fh, $sequence);
		fclose($fh);		

		// prepare tabe file
		$tabFileName  = uniqid('jcvi_metagenomics_report_').'.tab';
		$tabFilePath  = METAREP_TMP_DIR."/$tabFileName";			
				
		$ids = $this->getIds($datasets,$fastaFilePath,$tabFilePath,$evalue);
		
		$result['query'] = 'peptide_id:('. join(' OR ',array_keys($ids)).")";
		$result['hits'] = sizeof($ids);
		$result['suggestions'] = '';
 		return $result;
	}
	
	private function getIds($datasets,$fastaFilePath,$tabFilePath,$evalue) {	
		$this->writeBlastpTab($datasets,$fastaFilePath,$tabFilePath,$evalue);
		$tabResult = file($tabFilePath);
			
		$ids = array();
		## store best HSP in protein hash
		foreach($tabResult as $row) {
					
			$fields = explode("\t",trim($row),3);
			$proteinId = str_replace('@','|',$fields[1]);
			
			## only add first HSP
			if(!array_key_exists($proteinId,$ids)) {
				$ids[$proteinId] = null;
			}
		}	
		
		unlink($fastaFilePath);	
		unlink($tabFilePath);		
		return $ids;
	}
	
	private function runBlast($program,$datasets,$input,$output,$minEvalue,$outputFormat='') {
		$this->Project =& ClassRegistry::init('Project');
		$allDatasets = $this->Project->populationsToDatasets($datasets);	
		$databaseString = $this->getDatabaseString($allDatasets);
		
		$blastCmd = BLASTALL_PATH." -p $program -a {$this->numBlastCpu} -d \"$databaseString\" -i $input";
		
		if(!empty($minEvalue)) {
			$blastCmd .= " -e $minEvalue";
		}
		if(!empty($outputFormat)) {
			$blastCmd .= " $outputFormat";
		}		
		$blastCmd .= " > $output";
		exec($blastCmd);
		
		$this->correctNonNcbiConformIds($output); 
	}
	
	##TODO change hardcoded project 42
	## generate database string; concatenate all datasets	
	private function getDatabaseString($datasets) {
		$this->Project =& ClassRegistry::init('Project');
		$databaseString = '';
		
		if(sizeof($datasets) == 1 && !is_array($datasets)) {
			$datasets = array($datasets);
		}
		
		$projectId = $this->Project->getProjectId($datasets[0]);
		
		foreach($datasets as $dataset) {
			$databaseString .= SEQUENCE_STORE_PATH."/$projectId/$dataset/$dataset ";
		}
		
		return trim($databaseString);
	}
	
	## pipes in IDs were replaced during indexing with @ symbols
	private function correctNonNcbiConformIds($file) {
		exec(LINUX_BINARY_PATH."/sed -i 's/@/|/g' $file");
	}
}
?>