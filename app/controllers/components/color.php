<?php
/***********************************************************
* File: gradient.php
* Generates color gradient for hexadecimal codes
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

class ColorComponent extends Object {

	function gradient($heatmapColor, $steps=20) {
				
		switch($heatmapColor) {
			case HEATMAP_COLOR_YELLOW_RED:
				$hexstart = HEATMAP_YELLOW_RED_START;
				$hexend   = HEATMAP_YELLOW_RED_END;
				break;
			case HEATMAP_COLOR_YELLOW_BLUE:
				$hexstart = HEATMAP_YELLOW_BLUE_START;
				$hexend   = HEATMAP_YELLOW_BLUE_END;
				break;
			case HEATMAP_COLOR_BLUE:
				$hexstart = HEATMAP_BLUE_START;
				$hexend   = HEATMAP_BLUE_END;				
				break;
			case HEATMAP_COLOR_GREEN:
				$hexstart = HEATMAP_GREEN_START;
				$hexend   = HEATMAP_GREEN_END;
				break;						
			case HEATMAP_COLOR_RED_BLUE:
				$hexstart = HEATMAP_RED_BLUE_START;
				$hexend   = HEATMAP_RED_BLUE_END;
				break;					
		}		
	
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
	
	function twoColorGradientHash($heatmapColorA,$heatmapColorB,$rangeMin,$rangeMax,$step) {
		$offset = $rangeMax;
		
		## add outer classes < rangeMin and > range Max 
		$numCol = ceil(($rangeMax - $rangeMin)/0.2)+2;
		
		## generate color gradient for log ods ratio scale
		$colGradHash = array();
		$colGradNeg =   array_reverse($this->gradient(HEATMAP_COLOR_BLUE,$numCol/2));
		$colGradPos =  $this->gradient(HEATMAP_COLOR_GREEN,$numCol/2);
		$colGrad = array_merge($colGradNeg,$colGradPos);		

		## set upper bound
		$colGradHash[$colGrad[0]]['min'] = $rangeMax;
		$colGradHash[$colGrad[0]]['max'] = 999999999;
		$colGradHash[$colGrad[0]]['lab'] = "> $rangeMax";	
		
		## set interval bounds
		for($i = 1; $i < count($colGrad)-1; $i++ ) {
			$max = round($offset,1);
			$min = round($offset-$step,1);	
			
			## handle - sign before zeros
			if(abs($max) == 0) {
				$max =0;
			}
			if(abs($min) == 0) {
				$min =0;
			}
			
			$colGradHash[$colGrad[$i]]['min'] = $min;
			$colGradHash[$colGrad[$i]]['max'] = $max;
			$colGradHash[$colGrad[$i]]['lab'] = "$min to $max";
			$offset = $offset-$step;	
		}

		## set lower bound
		$colGradHash[$colGrad[count($colGrad)-1]]['min'] = -999999999;
		$colGradHash[$colGrad[count($colGrad)-1]]['max'] = $rangeMin;	
		$colGradHash[$colGrad[count($colGrad)-1]]['lab'] = "< $rangeMin";		
		
		return $colGradHash;
	}
}
?>
