<?php
/***********************************************************
* File: tree.php
* Description: The Tree Helper class helps to layout browse
* results. It generates the tree and ajax links for each tree
* node.
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

class TreeHelper extends AppHelper {

	var $helpers = array('Html','Ajax','Javascript');

	function taxonomy($dataset,$tree,$selectedTaxon,$mode) {

		$html = "<ul class=\"tree\" id=\"tree\">";
		$this->taxonNode($dataset,$html,$tree,$selectedTaxon,$mode);
		$html .= "</ul>";
		return $html;

	}
	function enzymes($dataset,$tree,$selectedTaxon) {
		$html = "<ul class=\"tree\" id=\"tree\">";
		$this->ecNode($dataset,$html,$tree,$selectedTaxon);
		$html .= "</ul>";
		return $html;

	}
	function pathways($dataset,$tree,$selectedTaxon) {
		$html = "<ul class=\"tree\" id=\"tree\">";
		$this->pathwayNode($dataset,$html,$tree,$selectedTaxon);
		$html .= "</ul>";
		return $html;

	}
	function geneOntology($dataset,$tree,$selectedTaxon) {
		$html = "<ul class=\"tree\" id=\"tree\">";
		$this->goNode($dataset,$html,$tree,$selectedTaxon);
		$html .= "</ul>";
		return $html;
	}

	private function taxonNode($dataset,&$html,$level,$selectedTaxon,$mode) {
			
		$counter=1;
		foreach($level as $taxonId=>$taxonEntry) {
			$ajaxLink= $this->Ajax->link($taxonEntry['name'], array('controller'=> 'browse','action'=>$mode,$dataset,$taxonEntry['taxon_id']), array('update' => 'Browse', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'browse-main-panel\',{ duration: 1.5 })', 'before' => 'Element.hide(\'browse-main-panel\')'));
			
			if($taxonEntry['name'] == $selectedTaxon) {
				$link = "<span class=\"selected_taxon\">".$taxonEntry['name']."</span>";
			}
			else if($taxonEntry['rank'] == 'blast_species' ) {
				$link = "<i>".$taxonEntry['name']."</i>";
			}
			else if($taxonEntry['taxon_id'] == -1) {
				$link = $taxonEntry['name'];
			}
			else {
				$link = $ajaxLink;
			}

			$class = null;

			if($counter==count($level)) {
				$class ="class=\"last\"";
			}

			$html .="<li $class><span style=\"white-space: nowrap\">".$link." (".$taxonEntry['rank'].") <strong>[".number_format($taxonEntry['count'])." peptides]</strong></span>";

			//if has children
			if($taxonEntry['children'] != null){
				$html .="<ul>";
				$this->taxonNode($dataset,$html,$level[$taxonId]['children'],$selectedTaxon,$mode);
				$html .="</ul>";
			}
			else {
				$html .="</li>";
			}
			$counter++;

		}
		return $html;
	}

	private function pathwayNode($dataset,&$html,$level,$selectedEc) {
			
		$counter=1;
			
		foreach($level as $taxonId=>$taxonEntry) {
			$ajaxLink= $this->Ajax->link($taxonEntry['name'], array('controller'=> 'browse','action'=>'pathways',$dataset,$taxonEntry['id']), array('update' => 'Browse', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'browse-main-panel\',{ duration: 1.5 })', 'before' => 'Element.hide(\'browse-main-panel\')'));
		
			if($taxonEntry['name'] == $selectedEc) {
				$link = "<span class=\"selected_taxon\">".$taxonEntry['name']."</span>";
			}
			else if ($taxonEntry['count'] == 0 ) {
				//$link = $this->Html->link($taxonEntry['name'], '', array('class'=>'_class', 'id'=>'_id'));
				$link = "<b>".$taxonEntry['name']."</b>";
			}
			else {
				$link = $ajaxLink;
			}

			$class = null;

			if($counter == count($level)) {
				$class ="class=\"last\"";
			}

			$html .="<li $class><span style=\"white-space: nowrap\">".$link." (".$taxonEntry['level'].") <strong>[".number_format($taxonEntry['count'])." peptides]</strong></span>";

			//if has children
			if($taxonEntry['children'] !=null){
				$html .="<ul>";
				$this->pathwayNode($dataset,$html,$level[$taxonId]['children'],$selectedEc);
				$html .="</ul>";
			}
			else {
				$html .="</li>";
			}
			$counter++;

		}
		return $html;
	}
	
	private function ecNode($dataset,&$html,$level,$selectedEc) {
			
		$counter=1;
			
		foreach($level as $taxonId=>$taxonEntry) {
			$ajaxLink= $this->Ajax->link($taxonEntry['name'], array('controller'=> 'browse','action'=>'enzymes',$dataset,$taxonEntry['ec_id']), array('update' => 'Browse', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'browse-main-panel\',{ duration: 1.5 })', 'before' => 'Element.hide(\'browse-main-panel\')'));

			if($taxonEntry['name'] == $selectedEc) {
				$link = "<span class=\"selected_taxon\">".$taxonEntry['name']."</span>";
			}
			else if ($taxonEntry['rank']=='level 4') {
				$link = "<b>".$taxonEntry['name']."</b>";
			}
			else {
				$link = $ajaxLink;
			}

			$class = null;

			if($counter==count($level)) {
				$class ="class=\"last\"";
			}

			$html .="<li $class><span style=\"white-space: nowrap\">".$link." (".$taxonEntry['rank'].") <strong>[".number_format($taxonEntry['count'])." peptides]</strong></span>";

			//if has children
			if($taxonEntry['children'] !=null){
				$html .="<ul>";
				$this->ecNode($dataset,$html,$level[$taxonId]['children'],$selectedEc);
				$html .="</ul>";
			}
			else {
				$html .="</li>";
			}
			$counter++;

		}
		return $html;
	}

	private function goNode($dataset,&$html,$level,$selectedGo) {
		$counter=1;
			
		foreach($level as $taxonId=>$taxonEntry) {
			$ajaxLink= $this->Ajax->link($taxonEntry['name'], array('controller'=> 'browse','action'=>'geneOntology',$dataset,$taxonEntry['acc']), array('update' => 'Browse', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'browse-main-panel\',{ duration: 1.5 })', 'before' => 'Element.hide(\'browse-main-panel\')'));

			if($taxonEntry['acc'] == $selectedGo['acc']) {
				$link = "<span class=\"selected_taxon\">".$taxonEntry['name']."</span>";
			}
			else {
				$link = $ajaxLink;
			}

			$class = null;

			if($counter==count($level)) {
				$class ="class=\"last\"";
			}
			$html .="<li $class><span style=\"white-space: nowrap\">".$link." <strong>[".number_format($taxonEntry['count'])." peptides]</strong></span>";

			//if has children
			if($taxonEntry['children'] !=null){
				$html .="<ul>";
				$this->goNode($dataset,$html,$level[$taxonId]['children'],$selectedGo);
				$html .="</ul>";
			}
			else {
				$html .="</li>";
			}
			$counter++;

		}
		return $html;
	}
}
?>