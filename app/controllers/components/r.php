<?php
/***********************************************************
* File: r.php
* Description: The R component handles all interactions between
* METAREP and the R statistical software.
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

class RComponent extends Object {
	
	var $components = array('Session');

	/**
	 * Executes R PDF plots
	 * 
	 * @param array $datasets list of selected datasets
	 * @param reference $counts matrix containing absolute counts
	 * @return void
	 * @access public
	 */
	public function writeRPlotMatrix($datasets,&$counts,$option) {
		
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
		
		$inFile   = "jcvi_metagenomics_report_".$id;
		$distFile = "jcvi_metagenomics_report_".$id."_dist";

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

		//loop through each dataset [dimension 1 / column]
		foreach($datasets as $dataset) {	
			$result .= $dataset." ";
				
			//loop through each category [dimension 2 / row]
			foreach($categories as $category) {
				$result .= $counts[$category][$dataset]." ";
			}
			$result .="\n";
		}


		//write to file
		$fh = fopen(METAREP_TMP_DIR."/$inFile", 'w');		#write session variables

		fwrite($fh,$result);
		fclose($fh);
		
		exec(RSCRIPT_PATH." ".METAREP_WEB_ROOT."/scripts/r/plots.r ".METAREP_TMP_DIR."/$inFile $option \"$plotName ($mode $level)\" $rMethod"." \"".METAREP_TMP_DIR."/".$distFile."\"");
		
		$clusterMatrices = file_get_contents(METAREP_TMP_DIR."/$distFile", 'w');		
		$this->Session->write('distantMatrices',$clusterMatrices);	
		$this->Session->write('plotFile',$inFile);
	}

	/**
	 * Executes METASTATS - a modified non -parameteric t-test (White et al 2009)
	 * 
	 * @param array $datasets list of selected datasets
	 * @param reference $counts matrix containing absolute counts
	 * @return Array of counts with test results
	 * @access public
	 */	
	public function writeMetastatsMatrix($datasets,&$counts) {

		$compareStartSecondPopulation 	= $this->Session->read('compareStartSecondPopulation');
		$comparePopulations 			= $this->Session->read('comparePopulations');
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

		#write frequency matrix to file
		$fh = fopen(METAREP_TMP_DIR."/$metastatsFrequencyMatrixFile", 'w');
		fwrite($fh,$matrixContent);
		fclose($fh);

		#prepare metastats rscript
		$metastatsRScriptContent  = "#!".RSCRIPT_PATH."\n";
		$metastatsRScriptContent .= "source(\"".METAREP_WEB_ROOT."/scripts/r/metastats/detect_DA_features.r\")\n";
		$metastatsRScriptContent .= "jobj <- load_frequency_matrix(\"".METAREP_TMP_DIR."/$metastatsFrequencyMatrixFile"."\")\n";
		$metastatsRScriptContent .= "detect_differentially_abundant_features(jobj,$compareStartSecondPopulation,\"".METAREP_TMP_DIR."/$metastatsResultsFile"."\",B=".NUM_METASTATS_BOOTSTRAP_PERMUTATIONS.")\n";


		#write metastats r script to file
		$fh = fopen(METAREP_TMP_DIR."/$metastatsRscriptFile", 'w');
		fwrite($fh,$metastatsRScriptContent);
		fclose($fh);

		#create results file
		$fh = fopen(METAREP_TMP_DIR."/$metastatsResultsFile", 'w');
		
		fclose($fh);
		chmod(METAREP_TMP_DIR."/$metastatsResultsFile", 0755);

		#execute script
		exec(RSCRIPT_PATH." ".METAREP_TMP_DIR."/$metastatsRscriptFile");

		#read R results/usr/local/bin/Rscript
		$fh = fopen(METAREP_TMP_DIR."/$metastatsResultsFile", 'r');
			
		$newCounts   = array();
		$populationA = $comparePopulations[0];
		$populationB = $comparePopulations[1];

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
					if($i < ($compareStartSecondPopulation-1)) {
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
					
				$newCounts[$category]['pvalue']					= round($column[7],(strlen(NUM_METASTATS_BOOTSTRAP_PERMUTATIONS)-1));
				$newCounts[$category]['qvalue']					= round($column[7]*$categoryCount,(strlen(NUM_METASTATS_BOOTSTRAP_PERMUTATIONS)-1));
				
				if($newCounts[$category]['qvalue']>1) {
					$newCounts[$category]['qvalue']	= 1;
				}

				if($newCounts[$category]['qvalue'] < 0) {
					$newCounts[$category]['qvalue'] = 0 ;
				}
			}
		}
		fclose($fh);
		
		$this->Session->write('selectedDatasets',$comparePopulations);
		
		$counts= $newCounts;

		return;
	}
	
	/**
	 * Transforms counts to R compatible contingency table format string
	 * 
	 * @param array $selectedDatasets list of selected datasets
	 * @param reference $counts count matrix
	 * @param reference $totalCounts matrix that contains the total count for each dataset
	 * @return String R compatible contingency table format string
	 * @access public
	 */
	private function countsToContingencyTables($datasets,&$counts,&$totalCounts) {

		$tables = array();
			
		$counter=0;

		foreach($counts as $category => $row) {
			$rString="";
				
			#loop through datatsets
			foreach($datasets as $dataset) {

				#create contigency table based on postive and negative counts for
				#a certain category
				$countCategory 		= $row[$dataset];
				$countNonCategory 	= $totalCounts[$dataset] - $countCategory;

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
	
	/**
	 * Writes contigency matrix and executes either Fisher's exact test or 
	 * a ChiSquare test of independence
	 * 
	 * @param array $selectedDatasets list of selected datasets
	 * @param reference $counts count matrix
	 * @param reference $totalCounts matrix that contains the total count for each dataset
	 * @param constant $option defines test to use CHISQUARE/FISHER
	 * @return Array of counts with test results
	 * @access public
	 */
	public function writeContingencyMatrix($selectedDatasets,&$counts,&$totalCounts,$option) {
		#format data as R matrix (2x2 contingency table)
		$tables = $this->countsToContingencyTables($selectedDatasets,$counts,$totalCounts) ;

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

		#read R results
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
		fclose($fh);
	}
		
	/**
	 * Executes non-parametric Wilcoxon Runk Sum Test comparing 
	 * relative counts of two populations.
	 * 
	 * @param array $selectedDatasets list of selected datasets
	 * @param reference $counts relative counts
	 * @return Array of counts with test results
	 * @access public
	 */	
	function writeWilcoxonMatrix($selectedDatasets,&$counts) {
		$categoryCount = count($counts);
		$compareStartSecondPopulation = $this->Session->read('compareStartSecondPopulation');
		$comparePopulations 			= $this->Session->read('comparePopulations');

		$populationA = $comparePopulations[0];
		$populationB = $comparePopulations[1];
		
		$id = time();
		$inFile = "metarep_wilcox.in_".$id.'.txt';
		$outFile = "metarep_wilcox.out_".$id.'.txt';
		$fh = fopen(METAREP_TMP_DIR."/$inFile", 'w');		
			
		$categoryCounter=1;
		
		$ids = array();
						#execute R code
		exec(R_PATH." --quiet --vanilla < ".METAREP_TMP_DIR."/$inFile > ".METAREP_TMP_DIR."/$outFile");
		
		$newCounts = array();
		
		foreach($counts as $category => $row) {
			
			//specify default values
			$newCounts[$category]['sum'] = 0;
			$newCounts[$category][$populationA]['median'] = 0; 
			$newCounts[$category][$populationB]['median'] = 0; 
			$newCounts[$category][$populationA]['mad'] = 0; 
			$newCounts[$category][$populationB]['mad'] = 0;
			$newCounts[$category]['pvalue'] =1;
			$newCounts[$category]['bonf-pvalue'] =1;
			
			//specify R commands
			$ids[$categoryCounter] = $category;
			$vectorA 	= "wca_c".$categoryCounter."= c(";
			$vectorB	= "wcb_c".$categoryCounter."= c(";
			$medianA	= "median(wca_c".$categoryCounter.")\n";
			$medianB	= "median(wcb_c".$categoryCounter.")\n";
			$madA		= "mad(wca_c".$categoryCounter.")\n";
			$madB		= "mad(wcb_c".$categoryCounter.")\n";
			$testCommand="wilcox.test(wca_c".$categoryCounter.",wcb_c".$categoryCounter.",alternative=\"two.sided\")\$p.value\n";
			
			//create vector A string
			for($i =0;$i < ($compareStartSecondPopulation-1); $i++) {
				$countCategory = $row[$selectedDatasets[$i]];
				//if last element
				if($i == $compareStartSecondPopulation-2) {
					$vectorA .= "$countCategory)\n";
				}
				else {
					$vectorA .= "$countCategory,";
				}				
			}
			
			//create vector B string
			for($i = $compareStartSecondPopulation-1;$i < count($selectedDatasets); $i++) {
				$countCategory = $row[$selectedDatasets[$i]];
				//if last element
				if($i == count($selectedDatasets)-1) {
					$vectorB .= "$countCategory)\n";
				}
				else {
					$vectorB .= "$countCategory,";
				}	
			}
			
			//write commands to input file
			fwrite($fh,$vectorA);
			fwrite($fh,$vectorB);
			fwrite($fh,$medianA);
			fwrite($fh,$medianB);
			fwrite($fh,$madA);
			fwrite($fh,$madB);			
			fwrite($fh,$testCommand);
			$categoryCounter++;
		}		
		fclose($fh);
		
		//execute R code
		exec(R_PATH." --quiet --vanilla < ".METAREP_TMP_DIR."/$inFile > ".METAREP_TMP_DIR."/$outFile");
				
		//open result file
		$fh = fopen(METAREP_TMP_DIR."/$outFile", 'r');
		
		$resultCounter=1;
		
		//read R results 
		while (!feof($fh)) {
				
			$line = fgets($fh);
			 
			if (substr($line,0,6) == '> wcb_') {
				$resultCounter = 1;				
				$line =	str_replace('> wcb_c','',$line);
				$tmp= split("=",$line);
				$id = $tmp[0];
				$category = $ids[$id];
			}
			else if(substr($line,0,3) == '[1]') {
				$value =	round(trim(str_replace('[1] ','',$line)),6);
				
				if($resultCounter == 1) {
					$newCounts[$category][$populationA]['median'] = round($value,6);
				}
				else if($resultCounter == 2) {
					$newCounts[$category][$populationB]['median'] = round($value,6);
				}
				else if($resultCounter == 3) {
					$newCounts[$category][$populationA]['mad'] =  round($value,6);
				}	
				else if($resultCounter == 4) {
					$newCounts[$category][$populationB]['mad'] =  round($value,6);
				}										
				else if($resultCounter == 5) {					
					$newCounts[$category]['pvalue'] = $value;
					$newCounts[$category]['name'] = $counts[$category]['name'];
					
					if($newCounts[$category][$populationB]['median'] !=0) {
						$newCounts[$category]['mratio']	= round($newCounts[$category][$populationA]['median']/$newCounts[$category][$populationB]['median'],3);
					}
					else {
						$newCounts[$category]['mratio']	 =0;
					}
					$bonfPValue = round($value*$categoryCount,6);
					
					if($bonfPValue > 1) {
						$bonfPValue = 1;
					}
					$newCounts[$category]['bonf-pvalue'] = $bonfPValue;
					
					if($newCounts[$category][$populationA]['median'] == 0 || $newCounts[$category][$populationB]['median'] == 0) {
						unset($newCounts[$category]);	
					}
				}	
				$resultCounter++;						
			}
		}
		fclose($fh);
		
		$this->Session->write('selectedDatasets',$comparePopulations);
		
		$counts = $newCounts;
		
		return;
	} 
}
?>