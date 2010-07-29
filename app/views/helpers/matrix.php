<?php
/***********************************************************
* File: matrix.php
* Description: The Matrix Helper class helps to layout compare
* results and provides a HTML-based heatmap.
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

define('HEATMAP_YELLOW_RED_START', 'F03B20');
define('HEATMAP_YELLOW_RED_END', 'FFEDA0');

define('HEATMAP_YELLOW_BLUE_START', '2C7FB8');
define('HEATMAP_YELLOW_BLUE_END', 'EDF8B1');

define('HEATMAP_BLUE_START', '3182BD');
define('HEATMAP_BLUE_END', 'DEEBF7');

define('HEATMAP_GREEN_START', '31A354');
define('HEATMAP_GREEN_END', 'E5F5E0');


class MatrixHelper extends AppHelper {

	var $uses = array('Library');
		
	function printTable($datasets,$counts,$option,$mode) {
			
		$html='';
			
		#debug($datasets);
		if($option == METASTATS || $option == WILCOXON) {	
				if($option == METASTATS) {		
					$html .= "<table style=\"border:1px; padding-bottom:5px; border-bottom-style:solid;border-width:1px;\">
						<tr>"	;
					$html .= "<th style=\"padding-right:5px; width:30%; border-width:0px;font-size:1.2em;background-color:#FFFFFF;\"></th>";			
					$html .= "<th style=\"padding-center:5px; width:21%; border-width:0px;font-size:1.2em;background-color:#FFFFFF;\">{$datasets[0]}</th>";
					$html .= "<th style=\"padding-center:5px; width:21%; border-width:0px;font-size:1.2em;background-color:#FFFFFF;\">{$datasets[1]}</th>";
					$html .= "<th style=\"padding-center:5px; width:27%; border-width:0px;font-size:1.2em;background-color:#FFFFFF;\">Significance</th>";
					$html .= "</tr></table>";
					
					$html .= "<table cellpadding=\"0px\" cellspacing=\"0\", id=\"myTable\" class=\"tablesorter comparison-results-table\"><thead> 	
						<tr><th style=\"padding-right:5px;width:30%;\">Category</th>";
					
					foreach($datasets as $dataset) {	
						$html .= "<th style=\"padding-right:5px;width:7%;\">#Total</th>";			
						$html .= "<th style=\"padding-right:5px;width:7%;\">%Mean</th>";
						#$html .= "<th style=\"padding-right:5px;width:6%;\">Variance</th>";
						$html .= "<th style=\"padding-right:5px;width:7%;\">%SE</th>";					
					}
					
					$html .= "<th style=\"padding-right:5px;width:7%;\">Mean Ratio</th>";
					$html .= "<th style=\"padding-right:5px;width:7%;\">p-value</th>";
					$html .= "<th style=\"padding-right:5px;width:7%;\">p-value (bonf. corr.)</th>";					
					$html .= "<th style=\"padding-right:5px;width:21%;\">CI (%Mean +/- %SE)</th>";	
				}
				elseif($option == WILCOXON) {
					$html .= "<table style=\"border:1px; padding-bottom:5px; border-bottom-style:solid;border-width:1px;\">
						<tr>"	;
					$html .= "<th style=\"padding-right:5px; width:35%; border-width:0px;font-size:1.2em;background-color:#FFFFFF;\"></th>";			
					$html .= "<th style=\"padding-center:5px; width:20%; border-width:0px;font-size:1.2em;background-color:#FFFFFF;\">{$datasets[0]}</th>";
					$html .= "<th style=\"padding-center:5px; width:20%; border-width:0px;font-size:1.2em;background-color:#FFFFFF;\">{$datasets[1]}</th>";
					$html .= "<th style=\"padding-center:5px; width:25%; border-width:0px;font-size:1.2em;background-color:#FFFFFF;\">Significance</th>";
					$html .= "</tr></table>";
					
					$html .= "<table cellpadding=\"0px\" cellspacing=\"0\", id=\"myTable\" class=\"tablesorter comparison-results-table\"><thead> 	
						<tr><th style=\"padding-right:5px;width:35%;\">Category</th>";
					
					foreach($datasets as $dataset) {	
						$html .= "<th style=\"padding-right:8px;width:10%;\">%Median</th>";
						$html .= "<th style=\"padding-right:8px;width:10%;\">%MAD</th>";		
					}
					
					$html .= "<th style=\"padding-right:5px;width:9%;\">Median Ratio</th>";
					$html .= "<th style=\"padding-right:5px;width:8%;\">p-value</th>";
					$html .= "<th style=\"padding-right:5px;width:8%;\">p-value (bonf. corr.)</th>";					
					#$html .= "<th style=\"padding-right:5px;width:14%;\">CI (Mean +/- SE)</th>";
				}		
		}
		else {
		
			$html .= "<table cellpadding=\"0px\" cellspacing=\"0\", id=\"myTable\" class=\"tablesorter comparison-results-table\"><thead> 	
						<tr>	
							<th>Category</th>";			
			
			foreach($datasets as $dataset) {
					$html .= "<th style=\"padding-right:5px; \">$dataset</th>";		
			}	
			if($option == ABSOLUTE_COUNTS) {			
				$html .= '<th style=\"padding-right:10px; \">Total</th>';
			}
			
			if($option == CHISQUARE || $option == FISHER) {	
				$html .= '<th style=\"padding-right:10px; \">Total</th>';
				$html .= '<th style=\"padding-right:10px; \">p-value</th>';
				$html .= '<th style=\"padding-right:10px; \">p-value (Bonf. Corr.)</th>';
			}			
		}
		
		$html .= '</tr></thead><tbody> ';
		
		$i = 0;
		#debug($counts);
		foreach($counts as $category => $row) {				
				if($row['sum'] > 0 || $option == METASTATS || $option == WILCOXON) {					
									
					if ($i++ % 2 == 0) {
						$color = '#FFFFFF';
					}	
					else {
						$color = '#FFFFFF';
					}	
					if($category === 'unclassified') {
						$html .="<tr style=\"text-align:left;font-weight:bold; \">";
						$html .= "<td style=\"text-align:left; \">{$row['name']}</td>";
					}
					else {			
						$html .= "<tr>";
						$rowValue = '';

						switch ($mode) {
							case 'taxonomy':
								$rowValue = "{$row['name']} (taxid:$category)";
								break;
							case 'commonNames':
								$rowValue = $row['name'];						
								break;
							case 'clusters':
								$rowValue = $row['name'];						
								break;	
							case 'pathways':
								$rowValue = "{$row['name']} (map$category)";
								break;							
							case 'environmentalLibraries':
								$rowValue = $row['name'];
								break;																	
							default:
								$rowValue = "{$row['name']} ($category)";
								break;
						}
						$html .= "<td style=\"text-align:left; \">$rowValue</td>";
					}
					
					if($option == METASTATS) {
						foreach($datasets as $dataset) {	
							$html .= "<td style=\"text-align:right;\">{$row[$dataset]['total']}</td>";							
							$html .= "<td style=\"text-align:right;\">".($row[$dataset]['mean']*100)."</td>";
							#$html .= "<td style=\"text-align:right;\">{$row[$dataset]['variance']}</td>";
							$html .= "<td style=\"text-align:right;\">".($row[$dataset]['se']*100)."</td>";
						}	
											
						$meanA 			= $row[$datasets[0]]['mean']*100;
						$lowBoundA 		= ($row[$datasets[0]]['mean']-$row[$datasets[0]]['se'])*100;
						$upperBoundA 	= ($row[$datasets[0]]['mean']+$row[$datasets[0]]['se'])*100;
						
						$meanB 			= $row[$datasets[1]]['mean']*100;
						$lowBoundB 		= ($row[$datasets[1]]['mean']-$row[$datasets[1]]['se'])*100;
						$upperBoundB 	= ($row[$datasets[1]]['mean']+$row[$datasets[1]]['se'])*100;
											
						$html .= "<td style=\"text-align:right;\">{$row['mratio']}</td>";	
						$html .= "<td style=\"text-align:right;\">{$row['pvalue']}</td>";	
						$html .= "<td style=\"text-align:right;\">{$row['qvalue']}</td>";	
						
						$chartUrl 	= "http://chart.apis.google.com/chart?chs=140x18&cht=bhs&chd=t0:-1,";
						$chartUrl  .= "{$lowBoundA},{$lowBoundB},-1|-1,{$meanA},{$meanB},-1|-1,{$meanA},{$meanB},";
						$chartUrl  .= "-1|-1,{$upperBoundA},{$upperBoundB},-1|-1,{$meanA},{$meanB},-1&chm=F,C00000,0,1:4,5&chxr=0,0,1,100&chbh=1,5,1";
						
						#$largeChart 	= "http://chart.apis.google.com/chart?chs=325x48&cht=bhs&chd=t0:-1,{$lowBoundA},{$lowBoundB},-1|-1,{$meanA},{$meanB},-1|-1,{$meanA},{$meanB},-1|-1,{$upperBoundA},{$upperBoundB},-1|-1,{$meanA},{$meanB},-1&chm=F,808080,0,1:4,5&chxr=0,0,1,100&chbh=1,5,1";
						
						$html .="<td style=\"text-align:center;\"><img src=\"$chartUrl\" name=\"ci_chart\">";
						$html .="</td>";
					}
					elseif($option == WILCOXON) {
						
						foreach($datasets as $dataset) {			
							$html .= "<td style=\"text-align:right;\">".($row[$dataset]['median']*100)."</td>";
							#$html .= "<td style=\"text-align:right;\">{$row[$dataset]['variance']}</td>";
							$html .= "<td style=\"text-align:right;\">".($row[$dataset]['mad']*100)."</td>";
						}	
						
						//get confidence intervals
						$medianA 		= $row[$datasets[0]]['median']*100;
						#$lowBoundA 		= $row[$datasets[0]]['ci_low']*100;
						#$upperBoundA 	= $row[$datasets[0]]['ci_high']*100;					
						$medianB 		= $row[$datasets[1]]['median']*100;
						#$lowBoundB 		= $row[$datasets[1]]['ci_low']*100;
						#$upperBoundB 	= $row[$datasets[1]]['ci_high']*100;
											
						$html .= "<td style=\"text-align:right;\">{$row['mratio']}</td>";	
						$html .= "<td style=\"text-align:right;\">{$row['pvalue']}</td>";	
						$html .= "<td style=\"text-align:right;\">{$row['bonf-pvalue']}</td>";	
						
						#$chartUrl 	= "http://chart.apis.google.com/chart?chs=140x18&cht=bhs&chd=t0:-1,";
						#$chartUrl  .= "{$lowBoundA},{$lowBoundB},-1|-1,{$medianA},{$medianB},-1|-1,{$medianA},{$medianB},";
						#$chartUrl  .= "-1|-1,{$upperBoundA},{$upperBoundB},-1|-1,{$medianA},{$medianB},-1&chm=F,C00000,0,1:4,5&chxr=0,0,1,100&chbh=1,5,1";
						
						#$largeChart 	= "http://chart.apis.google.com/chart?chs=325x48&cht=bhs&chd=t0:-1,{$lowBoundA},{$lowBoundB},-1|-1,{$meanA},{$meanB},-1|-1,{$meanA},{$meanB},-1|-1,{$upperBoundA},{$upperBoundB},-1|-1,{$meanA},{$meanB},-1&chm=F,808080,0,1:4,5&chxr=0,0,1,100&chbh=1,5,1";
						
						#$html .="<td style=\"text-align:center;\"><img src=\"$chartUrl\" name=\"ci_chart\">";
						$html .="</td>";
					}
					else {
						#set the individual counts
						foreach($datasets as $dataset) {	
								$count = $row[$dataset];					
								$html .= "<td style=\"text-align:right;\">$count</td>";
						}
		
						if($option != RELATIVE_COUNTS) {	
							#set the sum
							$sum 	= trim($counts[$category]['sum']);
							$html .= "<td style=\"text-align:right; \">$sum</td>";
						}
						if($option == CHISQUARE || $option == FISHER) {	
							$html .= "<td style=\"text-align:right;\">{$row['pvalue']}</td>";	
							$adjPValue= $row['pvalue'] * count($counts);
							
							if($adjPValue>1) {
								$adjPValue=1;
							}
							$html .= "<td style=\"text-align:right;\">$adjPValue</td>";			
						}
					}
					
					$html .= '</tr>';
				}		
		}
			
		$html .= '</table>';
		
		return $html;
	}
	
	

	function printFlippedTable($datasets,$counts,$option,$mode) {
	
		#generate table heading
		$html = "<table cellpadding=\"0px\" cellspacing=\"0\", id=\"myTable\" class=\"tablesorter comparison-results-table\"><thead> 	
					<tr>	
						<th>Dataset</th>";
		
		foreach($counts as $category => $row) {	
			
			
			if(!empty($row['name'])  && $row['sum'] > 0 ) {
				$html .= "<th style=\"padding-right:5px; \">{$row['name']}</th>";	
			}				
		}
		
		$html .= '</tr></thead><tbody> ';

		#add total column of absolute counts
		if($option == ABSOLUTE_COUNTS) {	
			array_push($datasets,'Total')	;
		}

		#add p-value and adj. p-value if user has selected a test
		if($option == CHISQUARE || $option == FISHER) {	
				array_push($datasets,'Total')	;
				array_push($datasets,'P-Value')	;
				array_push($datasets,'P-Value (Bonf. Corr.)')	;
		}
		
		#loop through each dataset [dimension 1]	
		foreach($datasets as $dataset) {		
				
			$rowValue = '';
								
			switch ($mode) {
				case 'taxonomy':
					$rowValue = "{$row['name']} (taxid:$category)";
					break;
				case 'commonNames':
					$rowValue = $row['name'];
					break;
				case 'clusters':
					$rowValue = $row['name'];
					break;		
				case 'pathways':
					$rowValue = "{$row['name']} (map$category)";
					break;							
				case 'environmentalLibraries':
					$rowValue = $row['name'];
					break;										
				default:
					$rowValue = "{$row['name']} ($category)";
					break;
			}
			
			//set font weight to bold for total 
			if($dataset === 'Total') {
				$html .= "<tr style=\"text-align:left;font-weight:bold;\"><td >$dataset</td>";
			}
			else {
				$html .= "<tr style=\"text-align:left;\"><td>$dataset</td>";				
			}
			
			#set the individual counts
			foreach($counts as $category => $row) {	
				#exclude unclassified
				if($row['sum'] > 0) { 
					if($dataset==='P-Value') {
						$count = $row['pvalue'];	
					}
					elseif($dataset==='P-Value (Bonf. Corr.)') {
						$adjPValue= $row['pvalue'] * count($counts);
				
						if($adjPValue>1) {
							$adjPValue=1;
						}
						$count = $adjPValue;
					}
					elseif($dataset==='Total') {
						$count  = $row['sum'];
						
					}
					else {
						$count = $row[$dataset];		
					}	
					$html .= "<td style=\"text-align:right;\">$count</td>";
				}													
			}
					
			$html .= '</tr>';		
		}
				
		
		$html .= '<tbody></table>';
		
		return $html;
	}	
	
	
	function printHeatMap($datasets,$counts,$option,$mode,$heatmapColor) {
		
		switch($heatmapColor) {
			case HEATMAP_COLOR_YELLOW_RED:
				$colorGradient =  $this->gradient(HEATMAP_YELLOW_RED_START,HEATMAP_YELLOW_RED_END,20);	
				break;
			case HEATMAP_COLOR_YELLOW_BLUE:
				$colorGradient =  $this->gradient(HEATMAP_YELLOW_BLUE_START,HEATMAP_YELLOW_BLUE_END,20);
				break;
			case HEATMAP_COLOR_BLUE:
				$colorGradient =  $this->gradient(HEATMAP_BLUE_START,HEATMAP_BLUE_END,20);		
				break;
			case HEATMAP_COLOR_GREEN:
				$colorGradient =  $this->gradient(HEATMAP_GREEN_START,HEATMAP_GREEN_END,20);
				break;						
		}
		
		$html = $this->printHeatmapColorLegend($colorGradient);	
		
		#print table header
		$html .= "<table cellpadding=\"0\" cellspacing=\"0\" id=\"myTable\" class=\"tablesorter comparison-results-table\"><thead>	
					<tr>	
						<th>Category</th>";
		
		foreach($datasets as $dataset) {
				$html .= "<th style=\"padding-right:5px; \">$dataset</th>";		
		}	
		
		$html .= '</tr></thead><tbody>';
		
		
		#print table body
		foreach($counts as $category => $row) {	
			
			#filter rows for those with entries
			if($row['sum']>0 && !empty($row['name'])) {					
					$html .= "<tr class=\"comparator-heatmap\" \" >";
									
					switch ($mode) {
						case 'taxonomy':
							$rowValue = "{$row['name']} (taxid:$category)";
							break;
						case 'commonNames':
							$rowValue = $row['name'];
							break;
						case 'clusters':
							$rowValue = $row['name'];
							break;
						case 'pathways':
							$rowValue = "{$row['name']} (map$category)";
							break;				
						case 'environmentalLibraries':
							$rowValue = $row['name'];
							break;												
						default:
							$rowValue = "{$row['name']} ($category)";
							break;
					}					
					

					$html .= "<td style=\"text-align:left; \">$rowValue</td>";
					
					foreach($datasets as $dataset) {	
						
						$color = $colorGradient[floor($row[$dataset]*19)];
						$html .= "<td style=\"text-align:left; background-color:#$color;\">{$row[$dataset]}</td>";
					}				
					$html .= '</tr>';
			}
		}
		#$html .= "<tr style=\"text-align:left;font-weight:bold; \">";
		#$html .= "<td>Unclassified</td>";	
		
//		foreach($datasets as $dataset) {		
//			$color = $colorGradient[floor($counts['unclassified'][$dataset]*19)];				
//			$html .= "<td style=\"text-align:left; background-color:#$color;\">{$counts['unclassified'][$dataset]}</td>";			
//		}
		
		$html .= '</tr>';			
		
		
		$html .= '<tbody></table>';
		
		return $html;	
	}

	function printFlippedHeat2Map($datasets,$counts,$mode,$heatmapColor) {
			
		#print heatmap color legend
		$html = "<table cellpadding=\"0\" cellspacing=\"0\"><tr>";
		$colorGradient =  $this->gradient(HEATMAP_START_COLOR,HEATMAP_END_COLOR,20);	

		$offset= 0;
		$step  = 0.05;
		foreach($colorGradient as $color) {
			$start = $offset;
			$end   =  $offset + $step;
			$html.="<td class=\"comparator-heatmap-legend\" style=\"background-color:#$color; \">{$start} - {$end}</td>";
			$offset +=$step;
		}
		$html .="</table>";
		
		#print values
		$html .= "<table cellpadding=\"0\" cellspacing=\"0\" id=\"myTable\" class=\"tablesorter comparison-results-table\"><thead>	
					<tr>	
						<th>Category</th>";
		
		foreach($counts as $category => $row) {	
			if(!empty($row['name'])) {
				switch ($mode) {
					case 'taxonomy':
						$rowValue = "{$row['name']} (taxid:$category)";
						break;
					case 'commonNames':
						$rowValue = $row['name'];
						break;
					case 'clusters':
						$rowValue = $row['name'];
						break;	
					case 'pathways':
						$rowValue = "{$row['name']} (map$category)";
						break;							
					case 'environmetnalLibraries':
						$rowValue = $row['name'];
						break;													
					default:
						$rowValue = "{$row['name']} ($category)";
						break;
				}	
			}
			
			$html .= "<th style=\"padding-right:5px; \">$rowValue</th>";					
		}
		
		$html .= '</tr></thead><tbody>';
		
		foreach($datasets as $dataset) {
			
			#filter rows for those with entries
			if($row['sum']>0 && !empty($row['name'])){					
					$html .= "<tr class=\"comparator-heatmap\" \" >";
					

					$html .= "<td style=\"text-align:left; \">$dataset</td>";
					
					foreach($counts as $category => $row) {		
						
						$color = $colorGradient[floor($row[$dataset]*19)];
						$html .= "<td style=\"text-align:left; background-color:#$color;\">{$row[$dataset]}</td>";
					}				
					$html .= '</tr>';
			}
		}
		$html .= '<tbody></table>';
		
		return $html;

	}	
	
	function printFlippedHeatmap($datasets,$counts,$option,$mode,$heatmapColor) {
		
		switch($heatmapColor) {
			case HEATMAP_COLOR_YELLOW_RED:
				$colorGradient =  $this->gradient(HEATMAP_YELLOW_RED_START,HEATMAP_YELLOW_RED_END,20);	
				break;
			case HEATMAP_COLOR_YELLOW_BLUE:
				$colorGradient =  $this->gradient(HEATMAP_YELLOW_BLUE_START,HEATMAP_YELLOW_BLUE_END,20);
				break;
			case HEATMAP_COLOR_BLUE:
				$colorGradient =  $this->gradient(HEATMAP_BLUE_START,HEATMAP_BLUE_END,20);		
				break;
			case HEATMAP_COLOR_GREEN:
				$colorGradient =  $this->gradient(HEATMAP_GREEN_START,HEATMAP_GREEN_END,20);
				break;						
		}		
		

		$html = $this->printHeatmapColorLegend($colorGradient);
		
		#print table header
		$html .= "<table cellpadding=\"0px\" cellspacing=\"0\", id=\"myTable\" class=\"tablesorter comparison-results-table\"><thead> 	
					<tr>	
						<th>Category</th>";
		
		foreach($counts as $category => $row) {	
			if(!empty($row['name']) && $row['sum'] > 0) {
				$html .= "<th style=\"padding-right:5px; \">{$row['name']}</th>";	
			}				
		}
		
		#$html .= "<th style=\"padding-right:5px; \">Unclassified</th>";	
		
		$html .= '</tr></thead><tbody> ';
			
		#llop through datasets
		foreach($datasets as $dataset) {
					
			#filter rows for those with entries
			
				#$html .= "<tr>";
				
				$rowValue = '';
								
				switch ($mode) {
					case 'taxonomy':
						$rowValue = "{$row['name']} (taxid:$category)";
						break;
					case 'commonName':
						$rowValue = $row['name'];
						break;
					case 'clusters':
						$rowValue = $row['name'];
						break;	
					case 'pathways':
						$rowValue = "{$row['name']} (map$category)";
						break;							
					case 'environmetnalLibraries':
						$rowValue = $row['name'];
						break;													
					default:
						$rowValue = "{$row['name']} ($category)";
						break;
				}

				$html .= "<tr style=\"text-align:left;\"><td>$dataset</td>";				

				
				#set the individual counts
				foreach($counts as $category => $row) {						
					if(!empty($row['name']) && $row['sum']>0) { 
						$count = $row[$dataset];		
						$color = $colorGradient[floor($count *19)];
						$html .= "<td style=\"text-align:right;background-color:#$color;\">$count</td>";
					}													
				}
				#$color = $colorGradient[floor($counts['unclassified'][$dataset] *19)];
				#$html .= "<td style=\"text-align:right;background-color:#$color;\">{$counts['unclassified'][$dataset]}</td>";
				
				
				
				$html .= '</tr>';
			}			
		
				
		
		$html .= '<tbody></table>';
		
		return $html;
	}		
	
	
	
	function gradient($hexstart, $hexend, $steps) {
	
	    $start['r'] = hexdec(substr($hexstart, 0, 2));
	    $start['g'] = hexdec(substr($hexstart, 2, 2));
	    $start['b'] = hexdec(substr($hexstart, 4, 2));
	
	    $end['r'] = hexdec(substr($hexend, 0, 2));
	    $end['g'] = hexdec(substr($hexend, 2, 2));
	    $end['b'] = hexdec(substr($hexend, 4, 2));
	    
	    $step['r'] = ($start['r'] - $end['r']) / ($steps - 1);
	    $step['g'] = ($start['g'] - $end['g']) / ($steps - 1);
	    $step['b'] = ($start['b'] - $end['b']) / ($steps - 1);
	    
	    $gradient = array();
	    
	    for($i = 0; $i <= $steps; $i++) {
	        
	        $rgb['r'] = floor($start['r'] - ($step['r'] * $i));
	        $rgb['g'] = floor($start['g'] - ($step['g'] * $i));
	        $rgb['b'] = floor($start['b'] - ($step['b'] * $i));
	        
	        $hex['r'] = sprintf('%02x', ($rgb['r']));
	        $hex['g'] = sprintf('%02x', ($rgb['g']));
	        $hex['b'] = sprintf('%02x', ($rgb['b']));
	        
	        $gradient[] = implode(NULL, $hex);
	                
	    }
		#reverse array
		$gradient = array_reverse($gradient);	    
		
		#remove empty white cell
		array_pop($gradient);			
	    
		return $gradient;
	
	}  
	
	private function printHeatmapColorLegend(&$colorGradient) {
		#print heatmap color legend
		$html = "<table cellpadding=\"0\" cellspacing=\"0\"><tr>";
		
		$offset= 0;
		$step  = 0.05;
		
		foreach($colorGradient as $color) {
			$start = $offset;
			$end   =  $offset + $step;
			$html.="<td class=\"comparator-heatmap-legend\" style=\"background-color:#$color; \">{$start} - {$end}</td>";
			$offset +=$step;
		}
		$html .="</table>";
		return $html;		
	}
}
?>