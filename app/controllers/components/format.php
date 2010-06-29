<?php
/***********************************************************
*  File: download.php
*  Description: Helps to format data returned from solr
*
*  Author: jgoll
*  Date:   Feb 24, 2010
************************************************************/


class FormatComponent extends Object {
	
	public function facetToDownloadString($heading,$facets,$numHits) {			
			$content ="$heading\tAbsolute Count\tRelative Count\n";
			$content .="----------------------------------------------------------\n";
			foreach($facets as $category => $count) {	
				$relativeCount= round($count/$numHits,4);		
				$content .= "$category\t$count\t$relativeCount\n";
			}
			return "$content\n\n";
	}
	
	public function treeLevelToDownloadString($heading,$treeLevel,$numHits) {
			$content ="$heading\tAbsolute Count\tRelative Count\n";
			$content .="----------------------------------------------------------\n";
			foreach($treeLevel as $taxon => $entry) {	
				$relativeCount= round($entry['count']/$numHits,4);		
				$content .= "{$entry['name']}\t{$entry['count']}\t$relativeCount\n";
			}
			return "$content\n\n";
	}
	public function pathwayToDownloadString($data,$numHits) {
		$content = "";
		
		foreach ($data as $level2=>$pathways) {	
			$content .= "Kegg Pathway Class: $level2 \n";
			$content .= "Pathway\tAbsolute Count\tRelative Count\tPathway Enzymes\t#Found Enzymes\t%Found Enzymes\n";
			foreach($pathways as $pathway) {
				$relativeCount= round($pathway['numPeptides']/$numHits,4);
				
				$content .= $pathway['pathway']."\t";
				$content .= $pathway['numPeptides']."\t";				
				$content .= $relativeCount."\t";		
				$content .= $pathway['numPathwayEnzymes']."\t";
				$content .= $pathway['numFoundEnzymes']."\t";
				$content .= $pathway['percFoundEnzymes']."\t\n";
			}
		}
		return $content;
	}

	public function infoString($title,$dataset,$query,$numHits,$node=''){
		$content ="----------------------------------------------------------\n";
		$timestamp =$today = date("F j, Y, g:i a"); 
		$content .="JCVI Metagenomics Report - $title\n";
		#for browse data add node
		$content .= "Date:\t\t$timestamp\nQuery:\t\t$query\n";
		if(!empty($node)) {
			$content .="Node:\t\t$node\n";
		}		
		if(is_array($dataset)) {
			$content .="Datasets:\n";
			foreach($dataset as $entry) {			
				$content .="\t\t$entry\n";
			}
		}
		else {
			$content .= "Dataset:\t$dataset\n";
		}		
		if(!empty($numHits)) {
			$content .= "Hits:\t\t$numHits\n";
		}

		
		$content .="----------------------------------------------------------\n";
		return "$content\n\n";
	}
	
	private function printMultiValue($value){
			if(is_array($value)) {
				return implode('||',$value);
			}
			else {
				return $value;
			}
	}
		
	public function facetListToDownloadString($title,$dataset,$facets,$query,$numHits,$node='') {
		
		$content =$this->infoString($title,$dataset,$query,$numHits,$node);	
		$content.=$this->facetToDownloadString('Blast Species',$facets->facet_fields->blast_species,$numHits);	
		$content.=$this->facetToDownloadString('Common Name',$facets->facet_fields->com_name,$numHits);		
		$content.=$this->facetToDownloadString('Gene Ontology',$facets->facet_fields->go_id,$numHits);		
		$content.=$this->facetToDownloadString('Enzyme',$facets->facet_fields->ec_id,$numHits);
		$content.=$this->facetToDownloadString('HMM',$facets->facet_fields->hmm_id,$numHits);
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
	
	
	public function metatstatsResultsToDonwloadString($counts,$selectedDatasets) {
		
		$content 	="Catgeory\t";
		
		foreach($selectedDatasets as $dataset) {
			$content .= "Total ($dataset)\t";
			$content .= "Mean ($dataset)\t";
			$content .= "Variance ($dataset)\t";
			$content .= "SE ($dataset)\t";		
		}
		$content .= "Mean Ratio\t";
		$content .= "p value\t";
		$content .= "p value (bonf. corr.)\n";
		
		foreach($counts as $category => $row) {				
			$content .= $row['name']."\t";
			foreach($selectedDatasets as $dataset) {
				$content .= $row[$dataset]['total']."\t";								
				$content .= $row[$dataset]['mean']."\t";
				$content .= $row[$dataset]['variance']."\t";
				$content .= $row[$dataset]['se']."\t";
			}
			$content .= $row['mratio']."\t";
			$content .= $row['pvalue']."\t";
			$content .= $row['qvalue']."\n";
		}
		
		return $content;	
	}
	
	public function comparisonResultsToDownloadString($counts,$selectedDatasets,$option) {
		$content 	="Catgeory\t";
		
		foreach($selectedDatasets as $dataset) {
			$content .=$dataset."\t";
		}
		if($option == ABSOLUTE_COUNTS) {
			$content .="Total";
		}
		if($option === CHISQUARE || $option === FISHER) {
			$content .="\tP-Value\t";
			$content .="\tP-Value (Bonf. Corr.)\t";			
		}	
		
		$content .="\n";
		
		foreach($counts as $category => $row) {	
			if($row['sum']>0 && $category!='unclassified') {	

				$content .= $row['name'];
				
				foreach($selectedDatasets as $dataset) {
					$content .="\t".$row[$dataset];
				}
				if($option == ABSOLUTE_COUNTS) {
						$content .="\t".$row['sum'];
				}
				if($option == CHISQUARE || $option == FISHER) {
					$pvalue = $row['pvalue'];
					$adjPValue= $pvalue * count($counts);
					
					if($adjPValue>1) {
						$adjPValue=1;
					}					
					
					$content .="\t".$pvalue."\t".$adjPValue;
				}			
				$content .="\n";
			}
		}	
		
		#handle unclassified category
		#$category = 'unclassified';
		
		#$content .= $category ;
				
		foreach($selectedDatasets as $dataset) {
			$content .="\t".$counts[$category][$dataset];
		}
		if($option == ABSOLUTE_COUNTS) {
				$content .="\t".$counts[$category]['sum'];
		}
		if($option == CHISQUARE || $option == FISHER) {
			$pvalue 	= $counts[$category]['pvalue'];
			$adjPValue 	= $pvalue * count($counts);
			
			if($adjPValue>1) {
				$adjPValue=1;
			}					
			
			$content .="\t".$pvalue."\t".$adjPValue;
		}			
		$content .="\n";
		return $content;
	}
}
?>