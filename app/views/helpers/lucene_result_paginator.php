<?php
/***********************************************************
* File: lucene_result_paginator.php
* Description: The Lucene Result Paginator Helper class, 
* provides result pagination support for Solr search results.
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

class LuceneResultPaginatorHelper extends AppHelper {

	var $limit = NUM_SEARCH_RESULTS;
	var $helpers = array('Html');
	
	function addPagination($page,$numHits,$dataset,$controller,$limit,$sessionQueryId) {
	
		//calculate the number of pages	
		$pageLimit = ceil($numHits/($limit));
		
		//maximum of displayed page links is 10
		$paginationPageLimit = $pageLimit >= 10 ?  10 : $pageLimit;
		
		//disable pagination elements if needed (first page, and last page)
		$paginationString='<div class="paging">';
			
		//first page
		if($page==1) {
			$paginationString .= "<div class=\"disabled\">&lt;&lt; previous</div>";
		}
		else {
			$paginationString .= $this->printLink("<< previous",($page-1),$dataset,$controller,$sessionQueryId);
		}
		//pages between first and last			
		for($i=1; $i< $paginationPageLimit ; $i++) {
			if($i==$page) {
				$paginationString .="| <span class=\"current\">$i</span>";
			}
			else {
				//$paginationString .="| <span><a href=\"/mg-reports-dev/searches/search/$i/".$query."\">$i</a></span>";
				$paginationString .="| <span>".$this->printLink($i,$i,$dataset,$controller,$sessionQueryId)."</span>";
			}
		}
			
		//last page
		if($page==$pageLimit) {
			$paginationString .= "<div class=\"disabled\"> next &gt;&gt;</div>";
		}
		else {
			$paginationString .= $this->printLink(" next >>",($page+1),$dataset,$controller,$sessionQueryId)."";
		}
		$paginationString .='</div>';

		return $paginationString;
	}
	
	function addPageInformation($page,$numHits,$limit) {
		$pageLimit = ceil($numHits/$limit) ;
		$pageStart = (($page-1)*$limit)+1;
		$pageStop  = ($page==$pageLimit) ? $numHits : ($page)*$limit;
		return "<p>Page $page of $pageLimit, showing $limit records out of $numHits total, starting on record $pageStart, ending on $pageStop</p>";
	}
	private function printLink($text,$page,$dataset,$controller,$sessionQueryId){
		return $this->Html->link($text, array('controller'=>$controller, $dataset,$page,$sessionQueryId));
	}
	
	function data($dataset,$hits,$page,$numHits,$limit,$sessionQueryId,$resultFields) {
		$html= "
			<fieldset>
				<legend>Search Results</legend>".$this->addPageInformation($page,$numHits,$limit)."
					<table cellpadding=\"0\" cellspacing=\"0\" >
					<tr>";
		
		//add field header
		foreach($resultFields as $fieldId => $fieldName) {
			$html.= "<th>$fieldName</th>";
		}
		$html.= "</tr>";
					
		$i = 0;
	
		
		foreach ($hits as $hit ) {	
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
			$html .= "<tr  $class>";
			
			//print field values
			foreach($resultFields as $fieldId => $fieldName) {
				$html .= "<td>".$this->printMultiValue($hit->{$fieldId})."</td>";
			}
			$html .= '</tr>';
		}
		$html .= '</table>';
		$html .= $this->addPagination($page,$numHits,$dataset,"search",$limit,$sessionQueryId);
		$html .= '</fieldset>';
		
		return $html;
				
	}
	
	function printMultiValue($value){
			if(is_array($value)) {
				return implode('<BR>',$value);
			}
			else {
				return $value;
			}
	}
}

?>