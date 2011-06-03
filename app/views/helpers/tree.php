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
* @version METAREP v 1.3.0
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

class TreeHelper extends AppHelper {

	var $helpers = array('Html','Ajax','Javascript');

	function taxonomy($dataset,$tree,$selectedNode,$mode) {
		$html = "<ul class=\"tree\" id=\"tree\">";
		$this->taxonNode($dataset,$html,$tree,$selectedNode,$mode);
		$html .= "</ul>";
		return $html;
	}
	function enzymes($dataset,$tree,$selectedNode) {
		$html = "<ul class=\"tree\" id=\"tree\">";
		$this->ecNode($dataset,$html,$tree,$selectedNode);
		$html .= "</ul>";
		return $html;
	}
	function pathways($dataset,$tree,$selectedNode,$mode) {
		$html = "<ul class=\"tree\" id=\"tree\">";
		$this->pathwayNode($dataset,$html,$tree,$selectedNode,$mode);
		$html .= "</ul>";
		return $html;
	}
	function geneOntology($dataset,$tree,$selectedNode) {
		$html = "<ul class=\"tree\" id=\"tree\">";
		$this->goNode($dataset,$html,$tree,$selectedNode);
		$html .= "</ul>";
		return $html;
	}

	private function taxonNode($dataset,&$html,$nodes,$selectedNode,$mode) {
			
		$counter=1;
		foreach($nodes as $nodeId=>$node) {
			$ajaxLink= $this->Ajax->link($node['name'], array('controller'=> 'browse','action'=>$mode,$dataset,$node['taxon_id']), array('update' => 'Browse', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'browse-main-panel\',{ duration: 1.5 })', 'before' => 'Element.hide(\'browse-main-panel\')'));
			
			if($node['name'] == $selectedNode) {
				$link = "<span class=\"selected_taxon\">".$node['name']."</span>";
			}
			else if($node['rank'] == 'blast_species' ) {
				$link = "<i>".$node['name']."</i>";
			}
			else if($node['taxon_id'] == -1) {
				$link = $node['name'];
			}
			else {
				$link = $ajaxLink;
			}

			$class = null;

			if($counter==count($nodes)) {
				$class ="class=\"last\"";
			}

			$html .= $this->getTreeLabel($class,$link,$node['rank'],$node['count']);
			
			//if has children
			if($node['children'] != null){
				$html .="<ul>";
				$this->taxonNode($dataset,$html,$nodes[$nodeId]['children'],$selectedNode,$mode);
				$html .="</ul>";
			}
			else {
				$html .="</li>";
			}
			$counter++;

		}
		return $html;
	}

	private function pathwayNode($dataset,&$html,$nodes,$selectedNode,$mode) {
			
		$counter=1;	
				
		foreach($nodes as $nodeId=>$node) {
			
			$ajaxLink= $this->Ajax->link($node['name'], array('controller'=> 'browse','action'=>$mode,$dataset,$node['id']),
									array('update' => 'Browse', 'loading' => 'Element.show(\'spinner\')', 
										  'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'browse-main-panel\',{ duration: 1.5 })',
										  'before' => 'Element.hide(\'browse-main-panel\')'),null,false);
			
			if($node['name'] === base64_decode($selectedNode)) {
				$link = "<span class=\"selected_taxon\">".$node['name']."</span>";
			}
			else if ($node['count'] == 0 ) {
				$link = "<b>".$node['name']."</b>";
			}
			else {
				$link = $ajaxLink;
			}

			$class = null;

			if($counter == count($nodes)) {
				$class ="class=\"last\"";
			}

			if($node['level'] === 'pathway' || $node['level'] === 'enzyme') {
				$html .= $this->getTreeLabel($class,$link,$node['level'],$node['count']);
			}
			else {
				$html .= $this->getTreeLabel($class,$link,null,$node['count']);
			}
			//if has children
			if($node['children'] !=null){
				$html .="<ul>";
				$this->pathwayNode($dataset,$html,$nodes[$nodeId]['children'],$selectedNode,$mode);
				$html .="</ul>";
			}
			else {
				$html .="</li>";
			}
			$counter++;

		}
		return $html;
	}
	
	private function ecNode($dataset,&$html,$nodes,$selectedEc) {			
		$counter=1;
			
		foreach($nodes as $nodId=>$node) {
			
			$ajaxLink= $this->Ajax->link($node['name'], array('controller'=> 'browse','action'=>'enzymes',$dataset,$node['ec_id']), 
						array('update' => 'Browse', 'loading' => 'Element.show(\'spinner\')', 
							  'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'browse-main-panel\',{ duration: 1.5 })',
							  'before' => 'Element.hide(\'browse-main-panel\')'));

			if($node['name'] === "$selectedEc ({$node['ec_id']})") {
				$link = "<span class=\"selected_taxon\">".$node['name']."</span>";
			}
			else {
				$link = $ajaxLink;
			}

			$class = null;

			if($counter == count($nodes)) {
				$class ="class=\"last\"";
			}
			
			$html .= $this->getTreeLabel($class,$link,$node['rank'],$node['count']);
			
			//if has children
			if($node['children'] !=null){
				$html .="<ul>";
				$this->ecNode($dataset,$html,$nodes[$nodId]['children'],$selectedEc);
				$html .="</ul>";
			}
			else {
				$html .="</li>";
			}
			$counter++;

		}
		return $html;
	}

	private function goNode($dataset,&$html,$nodes,$selectedGo) {
		$counter=1;
			
		foreach($nodes as $nodeId=>$node) {
			$ajaxLink= $this->Ajax->link($node['name'], array('controller'=> 'browse','action'=>'geneOntology',$dataset,$node['acc']), array('update' => 'Browse', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'browse-main-panel\',{ duration: 1.5 })', 'before' => 'Element.hide(\'browse-main-panel\')'));
			
			if($node['name'] === $selectedGo) {
				$link = "<span class=\"selected_taxon\">".$node['name']."</span>";
			}
			else {
				$link = $ajaxLink;
			}

			$class = null;

			if($counter == count($nodes)) {
				$class ="class=\"last\"";
			}
			
			$html .= $this->getTreeLabel($class,$link,'',$node['count']);
			
			#$html .="<li $class><span style=\"white-space: nowrap\">".$link." <strong>[".number_format($node['count'])." hits]</strong></span>";

			//if has children
			if($node['children'] !=null){
				$html .="<ul>";
				$this->goNode($dataset,$html,$nodes[$nodeId]['children'],$selectedGo);
				$html .="</ul>";
			}
			else {
				$html .="</li>";
			}
			$counter++;
		}
		return $html;
	}
	
	private function getTreeLabel($class,$link,$level,$count) {

		if(is_float($count)) {
			$count = number_format($count,2);
		}
		else {
			$count = number_format($count);
		}
		if($level) {
			return "<li $class><span style=\"white-space: nowrap\">$link ($level) <strong>[$count hits]</strong></span>";
		}
		else {
			return "<li $class><span style=\"white-space: nowrap\">$link <strong>[$count hits]</strong></span>";			
		}
	}
}
?>