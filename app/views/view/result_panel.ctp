<!----------------------------------------------------------
  
  File: result_panel.ctp
  Description: View Result Panel
  
  The View Result Panel displays summary statistics for a 
  selected View tab. It provides drop down menus to specify
  the number of hits that are shown and what filter tag should
  be applied (only available for datasets with filter assignments.

  METAREP : High-Performance Comparative Metagenomics Framework (http://www.jcvi.org/metarep)
  Copyright(c)  J. Craig Venter Institute (http://www.jcvi.org)

  Licensed under The MIT License
  Redistributions of files must retain the above copyright notice.

  @link http://www.jcvi.org/metarep METAREP Project
  @package metarep
  @version METAREP v 1.2.0
  @author Johannes Goll
  @lastmodified 2010-07-09
  @license http://www.opensource.org/licenses/mit-license.php The MIT License
  
<!---------------------------------------------------------->

<?php

	$viewResults= $session->read($sessionId);
	$facetCounts= $viewResults['facetCounts']; 
	$filters	= $viewResults['filters'];	
	$numHits	= $viewResults['numHits'];
	 
	if(isset($viewResults['limit']))  {
		$limit  	= $viewResults['limit'];
	}
	if(isset($viewResults['filter']))  {
		$filter		= $viewResults['filter']; 
	}
	
	echo $html->div('download', $html->link($html->image("download-large.png",array("title" => "Download List")), array('controller'=> 'view','action'=>'download',$dataset,$sessionId,$facetField,$numHits,$limit),array('escape' => false)));
		
	//handle drop down options
	if($facetField === 'pathway_id') {		
		if(isset($filters)) {
			echo ('<div id="view-drop-down-panel">');
			echo $form->create( 'Post' );
	
			if(!isset($filter)) {
				$filter = '';
			}
			echo $form->input('filter', array( 'options' => $filters, 'selected' => $filter,'label' => false, 'empty'=>'--select filter--','div'=>'view-level-select'));
			echo $form->end();
			echo ('</div>');
		}		
	}
	else{
		echo ('<div id="view-drop-down-panel">');
		echo $form->create( 'Post' );
		
		$levels = array('10' => 'Top 10 Hits',
				'20' => 'Top 20 Hits',
				'50' => 'Top 50 Hits',
				'100' => 'Top 100 Hits',
				'1000' => 'Top 1000 Hits'			
		);	
		echo $form->input('limit', array( 'options' => $levels, 'selected' => $limit,'label' => false, 'empty'=>'--select level--','div'=>'view-level-select'));
		
		if(isset($filters)) {
			
			if(!isset($filter)) {
				$filter = '';
			}
			echo $form->input('filter', array( 'options' => $filters, 'selected' => $filter,'label' => false, 'empty'=>'--select filter--','div'=>'view-filter-select'));
		}
		echo $form->end();
		echo ('</div>');
	}
	
	//handle results					
	echo ('<div id="view-facet-result-panel">');
	if($facetField === 'pathway_id') {
		echo $facet->pathwayTable($facetCounts); 
	}
	else {		
		echo $facet->table('',$facetCounts->facet_fields->{$facetField},$numHits);	
		
		//handle drop down based ajax select for the limit option
		echo $ajax->observeField( 'PostLimit', 
		    array(
		        'url' => array( 'controller' => 'view','action'=>'facet',$dataset,$sessionId,$facetField),
		        'frequency' => 0.2,
		    	'update' => 'view-facet-panel', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Element.hide(\'view-facet-panel\');Effect.Appear(\'view-facet-panel\',{ duration: 1.2 })',
		    	'with' => 'Form.serialize(\'PostAddForm\')'
		    ) 
		);
	}
	
	//handle drop down based ajax select for the filter option
	if(isset($filters)) {
	
		echo $ajax->observeField( 'PostFilter', 
		    array(
		        'url' => array( 'controller' => 'view','action'=>'facet',$dataset,$sessionId,$facetField),
		        'frequency' => 0.2,
		    	'update' => 'view-facet-panel', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Element.hide(\'view-facet-panel\');Effect.Appear(\'view-facet-panel\',{ duration: 1.2 })',
		    	'with' => 'Form.serialize(\'PostAddForm\')'
		    ) 
		);	
	}
	
	echo ('</div>');	
?>
