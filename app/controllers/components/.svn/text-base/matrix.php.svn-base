<?php
/***********************************************************
 *  File: matrix.php
 *  Description: To handle matrix manipulations
 *
 *  The key data structure is the $counts 2x2 array which is passed
 *  in as a reference. The count matrix is then manipulated to reflect
 *  the users choice (absolute, relative, or relative row counts (heatmap).
 *  Also, if a statistical test has been selected, p-values are added to it
 *
 *  Author: jgoll
 *  Date:   Feb 25, 2010
 ************************************************************/

ini_set('memory_limit', '256M');

class MatrixComponent extends Object {

	var $components = array('Solr','Session');
	public function formatCounts($option,$filter,$minCount,$selectedDatasets,&$counts) {


		#lety's get the totoal peptide counts for each dataset and make it accessable as a class variable
		$this->totalCounts = $this->getTotalCounts($filter,$selectedDatasets,$counts);

		#filter out empty categories
		$this->filterCounts($minCount,$selectedDatasets,$counts);

		#add another category that contains 'unclassified' counts
		#unclassified contains the total count - classified counts
		#$this->addUnclassifiedCategory($selectedDatasets,$counts);

		#if plot option
		if($option > 5) {
			$this->writeRPlotMatrix($selectedDatasets,$counts,$option);
		}

		if($option == CHISQUARE || $option === FISHER) {
			#add p-values to the counts matrix
			$this->addPvalues($selectedDatasets,$counts,$option);
			return;
		}
		elseif($option == METASTATS) {
			$this->writeMetastatsMatrix($selectedDatasets,$counts);
				
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

	#returns associative array containing the total peptide counts for all selected datasets
	#conuts are used to generate relative and relative row counts
	private function getTotalCounts($filter,$datasets,&$counts) {
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
	private function absoluteToRelativeCounts($datasets,&$counts) {

		#loop through counts, row by row [dimension 1]
		foreach($counts as $category => $row) {
				
			#contains the sum of all relative counts for a category (row) (replaces absolute row sum)
			$relativeRowSum = 0;
				
			#loop through each dataset [dimension 2]
			foreach($datasets as $dataset) {
				#get the the count for that catgeory and dataset
				$absoluteCount = $row[$dataset];
					
				#get the total count for the dataset
				$totalCount    = $this->totalCounts[$dataset];
					
				#calculate relative counts (precision=4)
				if($totalCount==0) {
					$relativeCount = 0;
				}
				else {
					$relativeCount = round(($absoluteCount/$totalCount),4);
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

	private function addPvalues($selectedDatasets,&$counts,$option) {
		#format data as R matrix (2x2 contingency table)
		$tables = $this->contingencyTables($selectedDatasets,$counts) ;

		if($option == CHISQUARE) {
			$test = 'chisq.test';
		}
		elseif($option == FISHER) {
			$test = 'fisher.test';
		}		
		
		$id = time();
		$inFile = "metarep_$test.in_".$id.'.txt';
		$outFile = "metarep_$test.out_".$id.'.txt';
		$fh = fopen(METAREP_TMP_DIR."/$inFile", 'w');

		$counter=1;
	
		#write R code
		for ($i = 0; $i < count($tables); $i++) {
			$rCommand = "{$tables[$i][1]}\n$test(ct_$i)\$p.value\n";

			#write test
			fwrite($fh, $rCommand);
			$counter++;
		}
		fclose($fh);


		#execute R code
		exec(R_PATH." --quiet --vanilla < ".METAREP_TMP_DIR."/$inFile > ".METAREP_TMP_DIR."/$outFile");

		#read R results/usr/local/bin/Rscript
		$fh = fopen(METAREP_TMP_DIR."/$outFile", 'r');
		while (!feof($fh)) {
				
			$line = fgets($fh);
			 
			if (substr($line,0,5) == '> ct_') {
				$line =	str_replace('> ct_','',$line);
				$tmp= split("=",$line);
				$id = $tmp[0];

				#get the id to category mapping
				$category = $tables[$id][0];

			}
			else if(substr($line,0,3) == '[1]') {
				$pValue =	round(trim(str_replace('[1] ','',$line)),6);
				$counts[$category]['pvalue']=$pValue;
			}
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

	private function contingencyTables($datasets,&$counts) {

		$tables = array();
			
		$counter=0;

		foreach($counts as $category => $row) {
			$rString="";
				
			#loop through datatsets
			foreach($datasets as $dataset) {

				#create contigency table based on postive and negative counts for
				#a certain category
				$countCategory 		= $row[$dataset];
				$countNonCategory 	= $this->totalCounts[$dataset] - $countCategory;

				#uses only first two datasets
				if(empty($rString)) {
					$rString ="ct_$counter=matrix(c($countCategory,$countNonCategory";
				}
				else {
					$rString.=",$countCategory,$countNonCategory";
				}
			}
			$rString.="),2,".count($datasets).")";
			array_push($tables,array($category,$rString));
			$counter++;
		}

		return $tables;
	}

	//	private function addPopulationTotals($datasets,&$counts) {
	//
	//		$metastatsStartSecondPopulation = $this->Session->read('metastatsStartSecondPopulation');
	//
	//		#loop through each dataset [dimension 1]
	//		for($i=0;$i<count($datasets);$i++) {
	//			#to store counts over all categories of a datatset
	//
	//			#loop through each category [dimension 2]
	//			foreach($counts as $category => $row) {
	//				$counts[$category]['']
	//
	//				if($category != 'unclassified')	{
	//					$classfiedCount += $row[$dataset];
	//				}
	//			}
	//		}
	//	}

	private function writeMetastatsMatrix($datasets,&$counts) {

		$metastatsStartSecondPopulation = $this->Session->read('metastatsStartSecondPopulation');
		$metastatsPopulations 			= $this->Session->read('metastatsPopulations');
		$mode				 			= $this->Session->read('mode');

		$matrixContent = "";
		$id = time();

		#generate file names
		$metastatsFrequencyMatrixFile = "metarep_metagenomics_report_metastats_in_".$id;
		$metastatsResultsFile 		  = "metarep_metagenomics_report_metastats_out_".$id;
		$metastatsRscriptFile		  = "metarep_metastats_rscript_".$id;

		#write headings
		foreach($datasets as $dataset) {
			$matrixContent .="\t".$dataset;
		}
		$matrixContent .="\n";

		#loop through each category [dimension 1]
		foreach($counts as $category => $row) {
				
			$matrixContent .= $category;
				
			#loop through each dataset [dimension 1 / column]
			foreach($datasets as $dataset) {
				$matrixContent .= "\t".$row[$dataset];
			}
			$matrixContent .="\n";
		}

		//		$matrixContent = "	13_0	14_0	13_52	14_52	70S	71S	72S	M1	M2	M3	C11	C12	C21	C15	C16	C19	C3	C4	C9\n
		//Alphaproteobacteria	0	0	0	0	0	0	5	0	0	0	0	0	0	0	0	0	0	0	0\n
		//Mollicutes	0	0	2	0	0	59	5	11	4	1	0	2	8	1	0	1	0	3	0\n
		//Verrucomicrobiae	0	0	0	0	0	1	6	0	0	0	0	0	0	0	0	0	0	0	0\n
		//Deltaproteobacteria	0	0	0	0	0	6	1	0	1	0	1	1	7	0	0	0	0	0	0\n
		//Cyanobacteria	0	0	1	0	0	0	1	0	0	0	0	0	0	0	0	0	0	0	0\n
		//Epsilonproteobacteria	0	0	0	0	0	0	0	0	6	0	0	3	1	0	0	0	0	0	0\n
		//Clostridia	75	65	207	226	801	280	267	210	162	197	81	120	106	148	120	94	84	98	121\n
		//Bacilli	3	2	16	8	21	52	31	70	46	65	4	28	5	23	62	26	20	30	25\n
		//Bacteroidetes (class)	21	25	22	64	226	193	296	172	98	55	19	149	201	85	50	76	113	92	82\n
		//Gammaproteobacteria	0	0	0	0	0	1	0	0	0	0	1	1	0	0	0	1	0	0	0\n
		//TM7_genera_incertae_sedis	0	0	0	0	0	0	0	0	1	0	1	2	0	2	0	0	0	0	0\n
		//Actinobacteria (class)	1	1	1	2	0	0	0	9	3	7	1	1	1	3	1	2	1	2	3\n
		//Betaproteobacteria	0	0	3	3	0	0	9	1	1	0	1	2	3	1	1	0	0	0	0\n";

		#write frequency matrix to file
		$fh = fopen(METAREP_TMP_DIR."/$metastatsFrequencyMatrixFile", 'w');
		fwrite($fh,$matrixContent);
		fclose($fh);

		#prepare metastats rscript
		$metastatsRScriptContent  = "#!".RSCRIPT_PATH."\n";
		$metastatsRScriptContent .= "source(\"/opt/www/metarep/htdocs/metarep/app/webroot/files/r/metastats/detect_DA_features.r\")\n";
		$metastatsRScriptContent .= "jobj <- load_frequency_matrix(\"".METAREP_TMP_DIR."/$metastatsFrequencyMatrixFile"."\")\n";
		$metastatsRScriptContent .= "detect_differentially_abundant_features(jobj,$metastatsStartSecondPopulation,\"".METAREP_TMP_DIR."/$metastatsResultsFile"."\")\n";

		#write metastats r script to file
		$fh = fopen(METAREP_TMP_DIR."/$metastatsRscriptFile", 'w');
		fwrite($fh,$metastatsRScriptContent);
		fclose($fh);

		#create results file
		$fh = fopen(METAREP_TMP_DIR."/$metastatsResultsFile", 'w');
		#fwrite($fh,$metastatsResultsFile);
		fclose($fh);
		chmod(METAREP_TMP_DIR."/$metastatsResultsFile", 0755);

		#execute script
		exec(RSCRIPT_PATH." ".METAREP_TMP_DIR."/$metastatsRscriptFile");

		#read R results/usr/local/bin/Rscript
		$fh = fopen(METAREP_TMP_DIR."/$metastatsResultsFile", 'r');
			
		$newCounts   = array();
		$populationA = $metastatsPopulations[0];
		$populationB = $metastatsPopulations[1];

		$categoryCount = count($counts);
		
		#loop through metastats output results
		while (!feof($fh)) {
			
			$line 	= fgets($fh);
			if($line) {
				$column 	= split("\t",$line);

				#round means with precison of 6
				$meanA = round($column[1],6);
				$meanB = round($column[4],6);
					
				#if either mean is zero after rounding skip line
				if($meanA == 0 ||  $meanB == 0) {
					$categoryCount--;
					continue;
				}

				#get the category from the metastats output
				$category = trim($column[0]);

				#for pathways we need to left-pad the categories with zeros
				if($mode === 'pathways') {
					$category 		= str_pad($category,5,0,STR_PAD_LEFT);
				}
									
				#init newCounts fields
				$newCounts[$category]['sum']  = 0;
				$newCounts[$category]['name'] = $counts[$category]['name'];
				$newCounts[$category][$populationA]['total']=0;
				$newCounts[$category][$populationB]['total']=0;
					
				#sum up library counts for each category to set population totals	
				for($i=0;$i<count($datasets);$i++) {
					if($i < ($metastatsStartSecondPopulation-1)) {
						$newCounts[$category][$populationA]['total'] += $counts[$category][$datasets[$i]];
					}
					else {
						$newCounts[$category][$populationB]['total'] += $counts[$category][$datasets[$i]];
					}
				}

				$newCounts[$category][$populationA]['mean'] 	= $meanA;
				$newCounts[$category][$populationA]['variance']	= round($column[2],6);
				$newCounts[$category][$populationA]['se']		= round($column[3],6);
				$newCounts[$category][$populationB]['mean'] 	= $meanB;
				$newCounts[$category][$populationB]['variance']	= round($column[5],6);
				$newCounts[$category][$populationB]['se']		= round($column[6],6);
					
				if($column[4] !=0) {
					$newCounts[$category]['mratio']				= round($column[1]/$column[4],3);
				}
				else {
					$newCounts[$category]['mratio']	 =0;
				}
					
				$newCounts[$category]['pvalue']					= round($column[7],3);
				$newCounts[$category]['qvalue']					= round($column[7]*$categoryCount,3);

				if($newCounts[$category]['qvalue'] < 0) {
					$newCounts[$category]['qvalue'] = 0 ;
				}
			}
		}
		$this->Session->write('selectedDatasets',$metastatsPopulations);
		
		$counts= $newCounts;

		return;
	}

	#function comparePvalues($a, $b) { return strnatcmp($a['pvalue'], $b['pvalue']); }

	private function writeRPlotMatrix($datasets,&$counts,$option) {
		
		$mode = str_replace('ajax','',$this->Session->read('mode'));
		$level = $this->Session->read("$mode.level");
		
		$mode =ucfirst($mode);
		
		switch($option) {
			case COMPLETE_LINKAGE_CLUSTER_PLOT:
				$plotName = "Complete Linkage Plot";
				$rMethod  =  "complete";
				break;
			case AVERAGE_LINKAGE_CLUSTER_PLOT:
				$plotName = "Average Linkage Plot";
				$rMethod  =  "average";
				break;
			case SINGLE_LINKAGE_CLUSTER_PLOT:
				$plotName = "Single Linkage Plot";
				$rMethod  =  "single";
				break;
			case WARDS_CLUSTER_PLOT:
				$plotName = "Ward's Minimum Variance";
				$rMethod  =  "ward";
				break;
			case MEDIAN_CLUSTER_PLOT:
				$plotName = "Median Linkage Plot";
				$rMethod  =  "median";
				break;
			case MCQUITTY_CLUSTER_PLOT:
				$plotName = "McQuitty Cluster Plot";
				$rMethod  =  "mcquitty";
				break;
			case CENTROID_CLUSTER_PLOT:
				$plotName = "Centroid Cluster Plot";
				$rMethod  =  "centroid";
				break;
			case MDS_PLOT:
				$plotName = "MDS Plot";
				$rMethod  =  "complete";
				break;
			case HEATMAP_PLOT:
				$plotName = "Heatmap";
				$rMethod  =  "complete";
				break;
		}

		$result = ' ';
		$id 	= time();
		$inFile = "jcvi_metagenomics_report_".$id;


		foreach($counts as $category => $row) {
			if($mode === 'Hmms') {
				$result .= preg_replace('/\s++/','_',trim($category))." ";
			}
			else {
				$result .= preg_replace('/\s++/','_',trim($row['name']))." ";
			}
		}
		$result .="\n";
			
		$categories = array_keys($counts);

		#loop through each dataset [dimension 1 / column]
		foreach($datasets as $dataset) {
				
			$result .= $dataset." ";
				
			#loop through each category [dimension 2 / row]
			foreach($categories as $category) {
				$result .= $counts[$category][$dataset]." ";
			}
			$result .="\n";
		}


		#write to file
		$fh = fopen(METAREP_TMP_DIR."/$inFile", 'w');		#write session variables

		fwrite($fh,$result);
		fclose($fh);
		#debug("/usr/local/bin/Rscript /opt/www/metarep/htdocs/metarep/app/webroot/files/r/r_plots.r ".METAREP_TMP_DIR."/$inFile \"$plotName ($mode $level)\" $rMethod");
		exec(RSCRIPT_PATH." ".METAREP_WEB_ROOT."/app/webroot/files/r/r_plots.r ".METAREP_TMP_DIR."/$inFile \"$plotName ($mode $level)\" $rMethod");
		$this->Session->write('plotFile',$inFile);
	}
}
?>
