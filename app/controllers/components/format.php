<?php
/***********************************************************
* File: download.php
* Description: Used for formatting results for download.
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

class FormatComponent extends Object {
	
	public function facetToDownloadString($heading,$facets,$numHits) {			
			$content ="$heading\tAbsolute Count\tRelative Count\n";
			$content .="----------------------------------------------------------\n";
			foreach($facets as $category => $count) {
				//strip off html code
				$category = strip_tags($category);
				$relativeCount= round($count/$numHits,6);		
				$content .= "$category\t$count\t$relativeCount\n";
			}
			return "$content\n\n";
	}
	

	public function pathwayToDownloadString($facetName,$data,$numHits) {
		$content = "";
		
		foreach ($data as $level2=>$pathways) {	
			$content .= "$facetName Class: $level2 \n";
			$content .= "Pathway\tAbsolute Count\tRelative Count\tPathway Enzymes\t#Found Enzymes\t%Found Enzymes\n";
			foreach($pathways as $pathway) {
		
				$content .= $pathway['pathwayName']."\t";
				$content .= $pathway['numPeptidesPathway']."\t";				
				$content .= $pathway['percPeptidesPathway']."\t";			
				$content .= $pathway['numPathwayEnzymes']."\t";
				$content .= $pathway['numFoundEnzymes']."\t";
				$content .= $pathway['percFoundEnzymes']."\t\n";
			}
		}
		return $content;
	}

	public function summaryToDownloadString($summary,$numHits) {
		$content = "";
				
		foreach ($summary as $summaryName=>$summaryEntry) {		
			$content .= "$summaryName \n";
			
			$content .= "Category\tAssigned\tUnassigned\t%Assigned\t%Unassigned\n";
			
			foreach ($summaryEntry as $field=>$entry) {	
				$content .= $entry['name']."\t";		
				$content .= $entry['totalAssigned']."\t";		
				$content .= $entry['totalUnassigned']."\t";		
				$content .= $entry['percAssigned']."\t";		
				$content .= $entry['percUnassigned']."\t\n";			
			}
			$content .="\n";
		}
		return $content;
	}	
	
	public function infoString($title,$dataset,$query,$minCount,$numHits='',$node =''){
		$content  ="#------------------------------------------------------------------------------------------------------\n";
		$content .="# If you use this software please cite:\n";
		$content .="# METAREP: JCVI metagenomics reports - an open source tool for high-performance comparative metagenomics\n";
		$content .="# Bioinformatics (2010) 26(20): 2631-2632\n";
		$content .="# \n";
		
		$timestamp =$today = date("F j, Y, g:i a"); 
		
		$content .= "# Date:\t\t$timestamp\n";
		$content .= "# Query:\t$query\n";
		$content .= "# Instance:\t".METAREP_RUNNING_TITLE."\n";
		$content .= "# Version:\t".METAREP_VERSION."\n";
		$content .= "# Option:\t".$title."\n";
		
		if($minCount != 0) {
			$content .="# Min. Count:\t$minCount\n";		
		}	
		if(!empty($node)) {
			$content .="# Node:\t\t$node\n";
		}
		if(is_array($dataset)) {
			$content .="# Datasets:\n";
			foreach($dataset as $entry) {			
				$content .="# \t\t$entry\n";
			}
		}
		else {
			$content .= "# Dataset:\t$dataset\n";
		}		
		if(!empty($numHits)) {
			$content .= "# Hits:\t\t$numHits\n";
		}
		$content  .="#------------------------------------------------------------------------------------------------------\n";
		return "$content\n";
	}
	
	private function printMultiValue($value){
			if(is_array($value)) {
				return implode('||',$value);
			}
			else {
				return $value;
			}
	}
		
	public function facetListToDownloadString($title,$dataset,$facets,$facetFields,$query,$numHits,$node = '') {	
		$content =$this->infoString($title,$dataset,$query,0,$numHits,$node);	
		
		foreach($facetFields as $fieldId=>$name) {
			$content.=$this->facetToDownloadString($name,$facets->facet_fields->{$fieldId},$numHits);	
		}
		return $content;
	}	
	
	public function facetMetaInformationListToDownloadString($title,$facets,$query,$numHits,$numDatasets,$node = ''){		
		$content =$this->infoString($title,"$numDatasets datasets",$query,$numHits,$node);	
		$content.=$this->facetToDownloadString('Project',$facets['project'],$numHits);	
		$content.=$this->facetToDownloadString('Sample Habitat',$facets['habitat'],$numHits);		
		$content.=$this->facetToDownloadString('Sample Filter',$facets['filter'],$numHits);		
		$content.=$this->facetToDownloadString('Sample Depth',$facets['depth'],$numHits);		
		$content.=$this->facetToDownloadString('Sample Location',$facets['location'],$numHits);
		return $content;
	}	
	
	public function dataRowToDownloadString($row) {
			$content = $row->peptide_id."\n";
//			$content.= $this->printMultiValue($row->com_name)."\t";
//			$content.= $this->printMultiValue($row->com_name_src)."\t";
//			$content.= $row->blast_species."\t";
//			$content.= $row->blast_evalue."\t";	
//			$content.= $this->printMultiValue($row->go_id)."\t";
//			$content.= $this->printMultiValue($row->go_src)."\t";
//			$content.= $this->printMultiValue($row->ec_id)."\t";
//			$content.= $this->printMultiValue($row->ec_src)."\t";
//			$content.= $this->printMultiValue($row->hmm_id)."\n";
			return $content;	
	}
	
	
	public function metatstatsResultsToDonwloadString($counts,$selectedDatasets,$maxPvalue) {
		$this->filterCountsByMaxPvalue($counts,$maxPvalue);
		$content 	= "ID\tCategory\t";
		
		foreach($selectedDatasets as $dataset) {
			$content .= "Total ($dataset)\t";
			$content .= "%Mean ($dataset)\t";
			$content .= "Variance ($dataset)\t";
			$content .= "%SE ($dataset)\t";		
		}
		$content .= "Mean Ratio\t";
		$content .= "P-Value\t";
		$content .= "P-Value (bonferroni)\t";
		$content .= "Q-Value (fdr)\n";
		
		foreach($counts as $category => $row) {				
			if($category != $row['name']) {
				$content .="$category\t{$row['name']}\t";
			}
			else {
				$content .= "NA\t{$row['name']}\t";
			}
			
			foreach($selectedDatasets as $dataset) {
				$content .= $row[$dataset]['total']."\t";								
				$content .= $row[$dataset]['mean']."\t";
				$content .= $row[$dataset]['variance']."\t";
				$content .= $row[$dataset]['se']."\t";
			}
			$content .= $row['mratio']."\t";
			$content .= $row['pvalue']."\t";
			$content .= $row['bvalue']."\t";
			$content .= $row['qvalue']."\n";
		}
		
		return $content;	
	}

	public function wilcoxonResultsToDonwloadString($counts,$selectedDatasets,$maxPvalue) {
		$this->filterCountsByMaxPvalue($counts,$maxPvalue);
		$content 	= "ID\tCategory\t";
		
		foreach($selectedDatasets as $dataset) {
			$content .= "%Median ($dataset)\t";
			$content .= "%MAD ($dataset)\t";
		}
		$content .= "Median Ratio\t";
		$content .= "P-Value\t";
		$content .= "P-Value (Bonf. Corr.)\n";
		
		foreach($counts as $category => $row) {				
			if($category != $row['name']) {
				$content .="$category\t{$row['name']}\t";
			}
			else {
				$content .= "NA\t{$row['name']}\t";
			}
				
			foreach($selectedDatasets as $dataset) {			
				$content .= $row[$dataset]['median']."\t";
				$content .= $row[$dataset]['mad']."\t";
			}
			$content .= $row['mratio']."\t";
			$content .= $row['pvalue']."\t";
			$content .= $row['bvalue']."\n";
		}
		
		return $content;	
	}	

	public function twoWayTestResultsToDownloadString($counts,$selectedDatasets,$maxPvalue) {

		$this->filterCountsByMaxPvalue($counts,$maxPvalue);
		
		$content 	= "ID\tCategory\t";

		foreach($selectedDatasets as $dataset) {
			$content .= "Count ($dataset)\t";
			$content .= "Proportion ($dataset)\t";
		}		
		
		$content .= "Log Odds Ratio\t";
		$content .= "Relative Risk\t";
		$content .= "\tP-Value\t";
		$content .= "\tP-Value (Bonferroni)\t";
		$content .= "\tQ-Value (FDR)\t";
		$content .="\n";

		foreach($counts as $category => $row) {
			if($row['sum'] > 0 ) {

				//strip off html content
				$name = strip_tags($row['name']);
					
				
				
				if($category != $name) {
					$content .="$category\t$name";
				}
				else {
					$content .= "NA\t$name";
				}
					
				$content .="\t".$row[$selectedDatasets[0]];
				$content .="\t".$row['propa'];
				$content .="\t".$row[$selectedDatasets[1]];
				$content .="\t".$row['propb'];
				$content .="\t".$row['oratio'];
				$content .="\t".$row['rrisk'];
				$content .="\t".$row['pvalue'];
				$content .="\t".$row['bvalue'];
				$content .="\t".$row['qvalue'];	
				
			}		
			$content .="\n";	
		}
		return $content;
	}
	
	private function filterCountsByMaxPvalue(&$counts,$maxPvalue) {
						
			if($maxPvalue == PVALUE_ALL) {
				return;
			}
			switch ($maxPvalue) {
				case PVALUE_HIGH_SIGNIFICANCE;
				$cutoff = 0.01;
				$fieldName = 'pvalue';
				break;
				case PVALUE_MEDIUM_SIGNIFICANCE;
				$cutoff = 0.05;
				$fieldName = 'pvalue';
				break;
				case PVALUE_LOW_SIGNIFICANCE;
				$cutoff = 0.1;
				$fieldName = 'pvalue';
				break;
				case PVALUE_BONFERONI_HIGH_SIGNIFICANCE;
				$cutoff = 0.01;
				$fieldName = 'bvalue';
				break;
				case PVALUE_BONFERONI_MEDIUM_SIGNIFICANCE;
				$cutoff = 0.05;
				$fieldName = 'bvalue';
				break;
				case PVALUE_BONFERONI_LOW_SIGNIFICANCE;
				$cutoff = 0.1;
				$fieldName = 'bvalue';
				break;
				case PVALUE_FDR_HIGH_SIGNIFICANCE;
				$cutoff = 0.01;
				$fieldName = 'qvalue';
				break;
				case PVALUE_FDR_MEDIUM_SIGNIFICANCE;
				$cutoff = 0.05;
				$fieldName = 'qvalue';
				break;
				case PVALUE_FDR_LOW_SIGNIFICANCE;
				$cutoff = 0.1;
				$fieldName = 'qvalue';
				break;						
			}
			
			foreach($counts as $category => $row) {	
				if($row[$fieldName]>=$cutoff) {
					unset($counts[$category]);
				}
			}
	}
	
	public function countResultsToDownloadString($counts,$selectedDatasets,$option) {
		$content 	= "ID\tCategory\t";
		
		foreach($selectedDatasets as $dataset) {
			$content .=$dataset."\t";
		}
		if($option == ABSOLUTE_COUNTS) {
			$content .="Total";
		}
		
		$content .="\n";
		
		foreach($counts as $category => $row) {	
			if($row['sum']>0 && $category!='unclassified') {	
	
				//strip off html content
				$name = strip_tags($row['name']);
				
				if($category != $name) {
					$content .="$category\t$name";
				}
				else {
					$content .= "NA\t$name";
				}
				
				foreach($selectedDatasets as $dataset) {
					$content .="\t".$row[$dataset];
				}
				if($option == ABSOLUTE_COUNTS) {
						$content .="\t".$row['sum'];
				}	
				$content .="\n";
			}
		}	
		return $content;
	}
	
	public function blastAnnotationsToDownloadString($annotations,$fields){
		$content 	= join("\t",array_values($fields))."\n";
		foreach($annotations as $annotation) {
			$row = array();
			foreach($fields as $id => $name) {
				
				array_push($row,$this->printMultiValue($annotation->{$id}));
			}
			$content .= join("\t",$row)."\n";	
		}		
		return $content;	
	}	
}
?>