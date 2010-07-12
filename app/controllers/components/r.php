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
		exec(RSCRIPT_PATH." ".METAREP_WEB_ROOT."/scripts/r/plots.r ".METAREP_TMP_DIR."/$inFile \"$plotName ($mode $level)\" $rMethod");
		$this->Session->write('plotFile',$inFile);
	}
	
	public function writeMetastatsMatrix($datasets,&$counts) {

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

		#write frequency matrix to file
		$fh = fopen(METAREP_TMP_DIR."/$metastatsFrequencyMatrixFile", 'w');
		fwrite($fh,$matrixContent);
		fclose($fh);

		#prepare metastats rscript
		$metastatsRScriptContent  = "#!".RSCRIPT_PATH."\n";
		$metastatsRScriptContent .= "source(\"".METAREP_WEB_ROOT."/scripts/r/metastats/detect_DA_features.r\")\n";
		$metastatsRScriptContent .= "jobj <- load_frequency_matrix(\"".METAREP_TMP_DIR."/$metastatsFrequencyMatrixFile"."\")\n";
		$metastatsRScriptContent .= "detect_differentially_abundant_features(jobj,$metastatsStartSecondPopulation,\"".METAREP_TMP_DIR."/$metastatsResultsFile"."\",B=".NUM_METASTATS_BOOTSTRAP_PERMUTATIONS.")\n";


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
		$this->Session->write('selectedDatasets',$metastatsPopulations);
		
		$counts= $newCounts;

		return;
	}

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
	 * @param reference $counts total count matrix
	 * @param constant $option defines test to use CHISQUARE/FISHER
	 * @return void
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
	}	
}
?>