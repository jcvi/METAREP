<!----------------------------------------------------------
  File: facet_panel.ctp
  Description:

  Author: jgoll
  Date:   Mar 22, 2010
<!---------------------------------------------------------->

<div id="facet-result-panel">
<?php
	echo $html->div('download', $html->link($html->image("download-large.png"), array('controller'=> 'view','action'=>'download',$dataset,$facetField,$numHits,$limit),array('escape' => false)));
	
	if($facetField!='pathway_id') {
		echo $form->create( 'Post' );
		
		$levels = array('10' => 'Top 10 Hits',
				'20' => 'Top 20 Hits',
				'50' => 'Top 50 Hits',
				'100' => 'Top 100 Hits',
				'1000' => 'Top 1000 Hits'			
		);	
		
		echo $form->input('limit', array( 'options' => $levels, 'selected' => $limit,'label' => false, 'empty'=>'--select level--'));
		echo $form->end();
	
				
		#to track changes in the drop down
		echo $ajax->observeField( 'PostLimit', 
		    array(
		        'url' => array( 'controller' => 'view','action'=>'facet',$dataset,$facetField),
		        'frequency' => 0.2,
		    	'update' => 'view-facet-panel', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Element.hide(\'view-facet-panel\');Effect.Appear(\'view-facet-panel\',{ duration: 1.2 })',
		    	'with' => 'Form.serialize(\'PostAddForm\')'
		    ) 
		);
		echo $facet->table('',$facets->facet_fields->{$facetField},$numHits);	
	}
	else {
		echo $facet->pathwayTable($pathways); 
		#echo $facet->pathwayTable($facets);	
	}
	
?>
