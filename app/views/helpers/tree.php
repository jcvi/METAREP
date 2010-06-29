<?php
/***********************************************************
 *  File: tree.php
 *  Description:
 *
 *  Author: jgoll
 *  Date:   Jun 8, 2010
 ************************************************************/

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
			//$count=$taxonEntry['count'];
			#$html .= $this->Javascript->link(array('prototype'));
			#$ajaxLink= $this->Ajax->link($taxonEntry['name'], array('controller'=> 'searches','action'=>'browse',$dataset,$taxonEntry['taxon_id']), array('update' => 'BrowseData', 'indicator' => 'spinner','loading' => 'Effect.Appear(\'BrowseData\')'));
			#$ajaxLink= $this->Ajax->link($taxonEntry['name'], array('controller'=> 'searches','action'=>'browse',$dataset,$taxonEntry['taxon_id']), array('update' => 'BrowseData', 'loading' => 'Element.show(\'spinner\'); Effect.BlindDown(\'BrowseData\')', 'complete' => 'Element.hide(\'spinner\')', 'before' => 'Element.hide(\'BrowseData\')'));
			//
			$ajaxLink= $this->Ajax->link($taxonEntry['name'], array('controller'=> 'browse','action'=>$mode,$dataset,$taxonEntry['taxon_id']), array('update' => 'Browse', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'browse-main-panel\',{ duration: 1.5 })', 'before' => 'Element.hide(\'browse-main-panel\')'));

			//$ajaxLink= $this->Ajax->link($taxonEntry['name'], array('controller'=> 'browse','action'=>'browse',$dataset,$taxonEntry['taxon_id']),
			//array('update' => 'BrowseData'),null,false);


			if($taxonEntry['name'] == $selectedTaxon) {
				$link = "<span class=\"selected_taxon\">".$taxonEntry['name']."</span>";
			}
			else if ($taxonEntry['rank']=='blast_species') {
				//$link = $this->Html->link($taxonEntry['name'], '', array('class'=>'_class', 'id'=>'_id'));
				$link = "<i>".$taxonEntry['name']."</i>";
			}
			else {
				$link = $ajaxLink;
				//$link = $this->Html->link($taxonEntry['name'], array('controller'=>'searches', 'action'=>'browse',$dataset,$taxonEntry['taxon_id']), array('class'=>'_class', 'id'=>'_id'));
				//$link = $ajaxLink;
			}

			$class = null;

			if($counter==count($level)) {
				$class ="class=\"last\"";
			}
			//				if($taxonEntry['name'] == $selectedTaxon) {
			//					$class .=" selected";
			//				}
			//				$class .= "\\\"";

			$html .="<li $class><span style=\"white-space: nowrap\">".$link." (".$taxonEntry['rank'].") <strong>[".number_format($taxonEntry['count'])." peptides]</strong></span>";

			//if has children
			if($taxonEntry['children'] !=null){
				$html .="<ul>";
				$this->taxonNode($dataset,$html,$level[$taxonId]['children'],$selectedTaxon,$mode);
				//die($this->printLevel($html,$level[$taxonId]['children']));
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
			else if ($taxonEntry['count'] == 0) {
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
				//die($this->printLevel($html,$level[$taxonId]['children']));
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
			//$count=$taxonEntry['count'];
			#$html .= $this->Javascript->link(array('prototype'));
			#$ajaxLink= $this->Ajax->link($taxonEntry['name'], array('controller'=> 'browse','action'=>'browse',$dataset,$taxonEntry['taxon_id']), array('update' => 'BrowseData', 'indicator' => 'spinner','loading' => 'Effect.Appear(\'BrowseData\')'));
			#$ajaxLink= $this->Ajax->link($taxonEntry['name'], array('controller'=> 'browse','action'=>'browse',$dataset,$taxonEntry['taxon_id']), array('update' => 'BrowseData', 'loading' => 'Element.show(\'spinner\'); Effect.BlindDown(\'BrowseData\')', 'complete' => 'Element.hide(\'spinner\')', 'before' => 'Element.hide(\'BrowseData\')'));
			$ajaxLink= $this->Ajax->link($taxonEntry['name'], array('controller'=> 'browse','action'=>'enzymes',$dataset,$taxonEntry['ec_id']), array('update' => 'Browse', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'browse-main-panel\',{ duration: 1.5 })', 'before' => 'Element.hide(\'browse-main-panel\')'));
			#$ajaxLink= $this->Ajax->link($taxonEntry['name'], array('controller'=> 'browse','action'=>'enzymes',$dataset,$taxonEntry['ec_id']), array('update' => 'Browse', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'BrowseTable\',{ duration: 10 })', 'before' => 'Element.hide(\'BrowseTable\')'));

			//$ajaxLink= $this->Ajax->link($taxonEntry['name'], array('controller'=> 'browse','action'=>'browse',$dataset,$taxonEntry['taxon_id']),
			//array('update' => 'BrowseData'),null,false);


			if($taxonEntry['name'] == $selectedEc) {
				$link = "<span class=\"selected_taxon\">".$taxonEntry['name']."</span>";
			}
			else if ($taxonEntry['rank']=='level 4') {
				//$link = $this->Html->link($taxonEntry['name'], '', array('class'=>'_class', 'id'=>'_id'));
				$link = "<b>".$taxonEntry['name']."</b>";
			}
			else {
				$link = $ajaxLink;
				//$link = $this->Html->link($taxonEntry['name'], array('controller'=>'searches', 'action'=>'browse',$dataset,$taxonEntry['taxon_id']), array('class'=>'_class', 'id'=>'_id'));
				//$link = $ajaxLink;
			}

			$class = null;

			if($counter==count($level)) {
				$class ="class=\"last\"";
			}
			//				if($taxonEntry['name'] == $selectedEc) {
			//					$class .=" selected";
			//				}
			//				$class .= "\\\"";

			$html .="<li $class><span style=\"white-space: nowrap\">".$link." (".$taxonEntry['rank'].") <strong>[".number_format($taxonEntry['count'])." peptides]</strong></span>";

			//if has children
			if($taxonEntry['children'] !=null){
				$html .="<ul>";
				$this->ecNode($dataset,$html,$level[$taxonId]['children'],$selectedEc);
				//die($this->printLevel($html,$level[$taxonId]['children']));
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