<?php
/***********************************************************
*  File: lucene_result_paginator.php
*  Description: Class to print pagination links for Lucene 
*  search results
*
*  Author: jgoll
*  Date:   Feb 17, 2010
************************************************************/

class LuceneResultPaginatorHelper extends AppHelper {

	var $limit = 10;
	var $helpers = array('Html');
	
	function addPagination($page,$numHits,$dataset,$controller,$sessionQueryId) {
		
		//we need to replace : with another symbol since it is used by cakephp to
		//add variable names along their value

		
		//calculate the number of pages	
		$pageLimit = ceil($numHits/($this->limit));
		
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
	
	function addPageInformation($page,$numHits) {
		$pageLimit = ceil($numHits/($this->limit)) ;
		$pageStart = (($page-1)*$this->limit)+1;
		$pageStop  = ($page==$pageLimit) ? $numHits : ($page)*$this->limit;
		return "<p>Page $page of $pageLimit, showing $this->limit records out of $numHits total, starting on record $pageStart, ending on $pageStop</p>";
	}
	private function printLink($text,$page,$dataset,$controller,$sessionQueryId){
		return $this->Html->link($text, array('controller'=>$controller, $dataset,$page,$sessionQueryId));
	}
	
	function data($dataset,$hits,$page,$numHits,$sessionQueryId) {
		$html= "
			<fieldset>
				<legend>Search Results</legend>".$this->addPageInformation($page,$numHits)."
					<table cellpadding=\"0\" cellspacing=\"0\">	
					<tr>	
						<th>Pep Id</th>
						<th>Common Name</th>
						<th>Common Name Source</th>
						<th>Blast Species</th>
						<th>Blast E-Value</th>
						<th>Go Id</th>
						<th>Go Source</th>
						<th>Ec Id</th>
						<th>Ec Source</th>
						<th>HMM</th>
					</tr>";
					
		$i = 0;
	
		
		foreach ( $hits as $hit ) {	
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
			
			$html .= "<tr  $class>";
			$html .= "<td>".$hit->peptide_id."</td>";
			$html .= "<td>".$this->printMultiValue($hit->com_name)."</td>";
			$html .= "<td>".$this->printMultiValue($hit->com_name_src)."</td>";
			$html .= "<td>".$this->printMultiValue($hit->blast_species)."</td>";
			$html .= "<td>".$hit->blast_evalue."</td>";	
			$html .= "<td>".$this->printMultiValue($hit->go_id)."</td>";
			$html .= "<td>".$this->printMultiValue($hit->go_src)."</td>";
			$html .= "<td>".$this->printMultiValue($hit->ec_id)."</td>";
			$html .= "<td>".$this->printMultiValue($hit->ec_src)."</td>";
			$html .= "<td>".$this->printMultiValue($hit->hmm_id)."</td>";
			$html .= '</tr>';
		}
		$html .= '</table>';
		$html .= $this->addPagination($page,$numHits,$dataset,"search",$sessionQueryId);
		$html .= '</fieldset>';
		
		//echo $crumb->getHtml('Home Page', 'reset' ) ;
		//echo '<br /><br />' ;
		//echo $html->link('One', 'one') ;
		
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