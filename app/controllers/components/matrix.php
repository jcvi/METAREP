<?php
/***********************************************************
* File: matrix.php
* Description: This class manipulates comparison results based
* on user specified options. The key data structure is the $counts
* 2-dimensional array which is passed in as a reference. The count
* matrix is then manipulated to reflect the users choice (absolute,
* relative, or relative row counts (heatmap), statistical test or
* plot.
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
* @version METAREP v 1.2.0
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

ini_set('memory_limit', '256M');

class MatrixComponent extends Object {

	var $components = array('Session','R');
	
	public function formatCounts($option,$filter,$minCount,$selectedDatasets,&$totalCounts,&$counts) {

		#get total peptide counts for each dataset and store them in totalCounts class variable
		//$this->totalCounts = $this->getTotalCounts($filter,$selectedDatasets,$counts);
		$this->totalCounts = $totalCounts;
		
		#filter out empty categories
		$this->filterCounts($minCount,$selectedDatasets,$counts);

		#add another category that contains 'unclassified' counts
		#unclassified contains the total count - classified counts
		#$this->addUnclassifiedCategory($selectedDatasets,$counts);
		
		if($option == CHISQUARE || $option === FISHER) {
			#add p-values to the counts matrix
			$this->R->writeContingencyMatrix($selectedDatasets,$counts,$this->totalCounts,$option);
			return;
		}
		elseif($option == WILCOXON) {
			$this->absoluteToRelativeCounts($selectedDatasets,$counts,6);
			$this->R->writeWilcoxonMatrix($selectedDatasets,$counts);
		}
		elseif($option == METASTATS) {
			$this->R->writeMetastatsMatrix($selectedDatasets,$counts);				
		}
		#handle all plot options
		elseif($option > 6) {
			$this->R->writeRPlotMatrix($selectedDatasets,$counts,$option);
		}

		#transform matrix into relative counts
		if($option == RELATIVE_COUNTS || $option == HEATMAP) {
			$this->absoluteToRelativeCounts($selectedDatasets,$counts);
		}

		#tranform matric for heatmap
		if($option == HEATMAP) {
			$this->relativeToRelativeRowCounts($selectedDatasets,$counts);
		}

		asort($counts);
	}



	#adds another coategory to the counts array called 'unclassified'.
	#this category contains the difference between total and classified counts
	private function addUnclassifiedCategory($datasets,&$counts) {

		$this->counts['unclassified']['sum'] = 0;
			
		$unclassifiedTotal = 0;

		#loop through each dataset [dimension 1]
		foreach($datasets as $dataset) {
				
			#to store counts over all categories of a datatset
			$classfiedCount = 0;
				
			#loop through each category [dimension 2]
			foreach($counts as $category => $row) {
				if($category != 'unclassified')	{
					$classfiedCount += $row[$dataset];
				}
			}
				
			#get the difference between classfied and total
			$unclassfiedCount = $this->totalCounts[$dataset] - $classfiedCount;
			$counts['unclassified'][$dataset] =  $unclassfiedCount;
				
			#sum up unclassified over datasets to set $counts['unclassified']['sum']
			$unclassifiedTotal += $unclassfiedCount;
		}

		$counts['unclassified']['sum'] = $unclassifiedTotal;
		$counts['unclassified']['name']= 'unclassified';
	}

	#filter absolute counts that are 0 or less than min count
	private function filterCounts($minCount,$datasets,&$counts) {

		#loop through counts, row by row [dimension 1]
		foreach($counts as $category => $row) {
				
			#if any of the category counts falls below min count this is set to 0
			$validEntry=1;
				
			#delete empty keys
			if($counts[$category]['sum'] == 0) {

				#unset($categories[$i]);
				unset($counts[$category]);
			}
			else {
				#loop through each dataset [dimension 2]
				foreach($datasets as $dataset) {
					$absoluteCount = $row[$dataset];
						
					//init empty cells
					if(empty($absoluteCount)) {
						$counts[$category][$dataset] =0;
					}
					#unset valid entry if absolute count is below the min count
					if($absoluteCount < $minCount) {
						$validEntry=0;
					}
				}
				//if at least one of the datasets falls below the min count for a
				//category that category get removed from the count array
				if(!$validEntry) {
					unset($counts[$category]);
				}
			}
		}
	}

	#transforms absolute counts to relative counts
	private function absoluteToRelativeCounts($datasets,&$counts,$precision = 4) {

		#loop through counts, row by row [dimension 1]
		foreach($counts as $category => $row) {
				
			#contains the sum of all relative counts for a category (row) (replaces absolute row sum)
			$relativeRowSum = 0;
				
			#loop through each dataset [dimension 2]
			foreach($datasets as $dataset) {
				#get the the count for that catgeory and dataset
				$absoluteCount = $row[$dataset];
					
				#get the total count for the dataset
				$totalCount = $this->totalCounts[$dataset];
					
				#calculate relative counts (precision=4)
				if($totalCount==0) {
					$relativeCount = 0;
				}
				else {
					$relativeCount = round(($absoluteCount/$totalCount),$precision);
				}
					
				#replace absolute count with relative count
				$counts[$category][$dataset] = $relativeCount;
					
				#add relative count to relative row sum
				$relativeRowSum+= $relativeCount;
			}
				
			#replace absolute row sum with the relative row sum
			$counts[$category]['sum'] = $relativeRowSum;
		}
	}

	//devides relative counts in a row by the relative row total (used for heatmap)
	private function relativeToRelativeRowCounts($datasets,&$counts) {

		#loop through counts, row by row [dimension 1]
		foreach($counts as $category => $row) {

			#get relative row sum for the category
			$relativeRowSum = $row['sum'];
				
			#skip relative row sums that are zero (due to rounding it might be zero)
			if($relativeRowSum != 0) {

				#loop through each dataset [dimension 2]
				foreach($datasets as $dataset) {

					#get relative row count
					$relativeCount = $row[$dataset];
						
					#devide each relative count by the total row relative count (precision=6)
					$relativeRowCount = round(($relativeCount/$relativeRowSum),4);
						
					#update relative counts to relative row-wide count
					$counts[$category][$dataset] = $relativeRowCount;
				}
			}
		}
	}

	#function comparePvalues($a, $b) { return strnatcmp($a['pvalue'], $b['pvalue']); }
}
?>
