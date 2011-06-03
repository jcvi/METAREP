<!----------------------------------------------------------

  File: result_panel.ctp
  Description: Compare Result Panel
 
  The Compare Result Panel displays the comparison results 
  within the Compare Tab Panel. It provides options to change
  the category level at which datasets are being compared, 
  flip the axis of the result matrix, change the font size, 
  change the color of the HTML heatmap, download data and 
  layouts PDF graphics within an iFrame.
  
  PHP versions 4 and 5

  METAREP : High-Performance Comparative Metagenomics Framework (http://www.jcvi.org/metarep)
  Copyright(c)  J. Craig Venter Institute (http://www.jcvi.org)

  Licensed under The MIT License
  Redistributions of files must retain the above copyright notice.

  @link http://www.jcvi.org/metarep METAREP Project
  @package metarep
  @version METAREP v 1.3.0
  @author Johannes Goll
  @lastmodified 2010-07-09
  @license http://www.opensource.org/licenses/mit-license.php The MIT License
  
<!---------------------------------------------------------->


<?php

$selectedDatasets 	= $session->read('selectedDatasets'); 
$option				= $session->read('option'); 
$minCount			= $session->read('minCount'); 
$plotFile			= $session->read('plotFile');
$mode				= $session->read('mode');
$level				= $session->read("$mode.level");
$counts				= $session->read('counts');
$heatmapColor		= $session->read('heatmapColor');
$colorGradient		= $session->read('colorGradient');
$plotLabel			= $session->read('plotLabel');
$flipAxis			= $session->read('flipAxis');
$tabs				= $session->read('tabs');
$distanceMatrix		= $session->read('distanceMatrix');
$clusterMethod		= $session->read('clusterMethod');
$maxPvalue			= $session->read('maxPvalue');



$currentTabPosition =0;
$tabPosition = 0;

foreach($tabs as $tab) {
	if($tab['function'] === $mode) {
		$currentTabPosition = $tabPosition;
	}
	$tabPosition ++;
}


?>

<script>
jQuery(document).ready(function(){
  jQuery.noConflict() ;
 
  jQuery("#myTable").tablesorter({widgets:['zebra'] <?php 
  	if($option == METASTATS){ 
  		echo(", sortList: [[8,0]]");
  	}
  	else if($option == WILCOXON){ 
  		echo(", sortList: [[6,0]]");
  	}  	
  	elseif($option == CHISQUARE) {
  		echo(", sortList: [[4,0]]");
  	}
  	elseif($mode === 'keggPathways') {
  		echo(", sortList: [[0,0]]");
  	} 
  	elseif($mode === 'metacycPathways') {
  		echo(", sortList: [[0,0]]");
  	} 
  	
  ?>}); 
	
  // Reset Font Size
  var originalFontSize = jQuery('.comparison-results-table').css('font-size');
  jQuery(".resetFont").click(function(){
    	jQuery('.comparison-results-table').css('font-size', originalFontSize);
  });
  // Increase Font Size
  jQuery(".increaseFont").click(function(){
	
    var currentFontSize = jQuery('.comparison-results-table').css('font-size');
    var currentFontSizeNum = parseFloat(currentFontSize, 10);
    var newFontSize = currentFontSizeNum*1.2;
    jQuery('.comparison-results-table').css('font-size', newFontSize);
    return false;
  });
  // Decrease Font Size
  jQuery(".decreaseFont").click(function(){
	 
    var currentFontSize = jQuery('.comparison-results-table').css('font-size');
    var currentFontSizeNum = parseFloat(currentFontSize, 10);
    var newFontSize = currentFontSizeNum*0.8;
    jQuery('.comparison-results-table').css('font-size', newFontSize);
    return false;
  });

jQuery.ajax({ success: function(){
  jQuery("#myTable").trigger("appendCache");
  jQuery("#tabs").tabs( "option", "selected",<?php echo($currentTabPosition);?>);
}}); 
     
});
</script>
   
<?php

  //update table and selected tab


#print message above multi-select box
if(isset($multiSelectException)) {	
	
	echo("<div id=\"flashMessage\" class=\"message\" style=\"position:absolute;font-size:1.2em;top:185px;left:205px\">$multiSelectException</div>");
	exit();
}
else {	
	$levels = $session->read('levels');
		
	if(empty($flipAxis)) {
		$flipAxis=0;		
	}
	if(empty($heatmapColor)) {
		$heatmapColor =  HEATMAP_COLOR_YELLOW_RED;
	}
	if(empty($plotLabel)) {
		$plotLabel =  PLOT_LIBRARY_NAME;
	}	
	
	if($option < 7) {
		if($option == METASTATS || $option == WILCOXON) {
			$flipLink= "";
		}
		else {
			$flipLink= $ajax->link('flip axis', array('controller'=> 'compare','action'=>'flipAxis'), array('update' => 'comparison-results',  'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Element.hide(\'comparison-results\');Effect.Appear(\'comparison-results\',{ duration: 0.5 })')); 				
		}
		echo("<div id=\"resize-box\">".$flipLink."<a href=\"#\" class=\"increaseFont\">zoom in</a> <a href=\"#\" class=\"decreaseFont\">zoom out</a>
			</div>");
	}
	
	echo $form->create( 'Post' );
	
//	if(!isset($level)) {
//		$level = 'root';
//	}
//	else {
//		debug($level);
//	}
	echo $form->input( 'level', array( 'options' => $levels, 'selected' => $level,'label' => 'Level', 'empty'=>'--select level--','div'=>'comparator-drop-down-one'));
	
	if($option == HEATMAP) {
		echo $form->input( 'heatmap', array( 'options' => array(HEATMAP_COLOR_YELLOW_RED=>'red-yellow (default)',
																HEATMAP_COLOR_YELLOW_BLUE=>'yellow-blue',
																HEATMAP_COLOR_BLUE =>'blue',
																HEATMAP_COLOR_GREEN =>'green'),'label' => 'Color', 'selected'=>$heatmapColor,'empty'=>'--Select Heatmap Color--','div'=>'comparator-drop-down-two'));
	}
				
	#to track changes in the drop down
	echo $ajax->observeField( 'PostLevel', 
	    array(
	        'url' => array( 'controller' => 'compare','action'=>$mode),
	        'frequency' => 0.2,
	    	'update' => 'comparison-results', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Element.hide(\'comparison-results\');Effect.Appear(\'comparison-results\',{ duration: 0.5 })',
	    	'with' => 'Form.serialize(\'PostAddForm\')'
	    ) 
	);
	if($option == HEATMAP) {
		echo $ajax->observeField( 'PostHeatmap', 
		    array(
		        'url' => array( 'controller' => 'compare','action'=>'changeHeatmapColor'),
		        'frequency' => 0.2,
		    	'update' => 'comparison-results', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Element.hide(\'comparison-results\');Effect.Appear(\'comparison-results\',{ duration: 0.5 })',
		    	'with' => 'Form.serialize(\'PostAddForm\')'
		    ) 
		);
	}
	
	if(count($counts) == 0) {
		echo("<div id=\"flashMessage\" class=\"message\" style=\"position:absolute;font-size:1.4em;top:90px;text-align:center;left:200px\">No hits found. Please try again with different options.</div>");
		exit();
	}
	if(count($counts) < 3 && $option > 6) {
		echo("<div id=\"flashMessage\" class=\"message\" style=\"position:absolute;font-size:1.4em;top:90px;text-align:center;left:200px\">Too few categories found for selected plot option. Please try again with different options.</div>");
		exit();
	}

	if($option == METASTATS || $option == WILCOXON) {
		echo $form->input('maxPvalue', 
			array( 'options' => 
					array(
						PVALUE_ALL => 'show all results',
						'p-value' =>
							array(
								PVALUE_HIGH_SIGNIFICANCE=>'< 0.01',
								PVALUE_MEDIUM_SIGNIFICANCE => '< 0.05',
								PVALUE_LOW_SIGNIFICANCE => '< 0.10',
							),
						'bonf. corr. p-value' =>
							array(	
								PVALUE_BONFERONI_HIGH_SIGNIFICANCE=>'< 0.01',
								PVALUE_BONFERONI_MEDIUM_SIGNIFICANCE => '< 0.05',
								PVALUE_BONFERONI_LOW_SIGNIFICANCE => '< 0.10',
							),						
						),							
				'selected' => $maxPvalue,'label' => 'max. p-value', 'empty'=>'--Select Pvalue--','div'=>'comparator-drop-down-two'));
	
		echo $ajax->observeField('PostMaxPvalue', 
			    array(
			        'url' => array( 'controller' => 'compare','action'=>'changePvalue'),
			        'frequency' => 0.2,
			    	'update' => 'comparison-results', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Element.hide(\'comparison-results\');Effect.Appear(\'comparison-results\',{ duration: 0.5 })',
			    	'with' => 'Form.serialize(\'PostAddForm\')'
			    ) 
		);			
	}		
	
	if($option == HEATMAP_PLOT || $option == MDS_PLOT || $option == HIERARCHICAL_CLUSTER_PLOT) {	
		echo $form->input('plotLabel', array( 'options' => array(
				PLOT_LIBRARY_NAME=>'Library Name',
				PLOT_LIBRARY_LABEL => 'Library Label',
				PLOT_LIBRARY_SAMPLE_ID => 'Sample ID',
				),'selected'=>$plotLabel,'label' => 'Dataset Label', 'empty'=>'--Select Dataset Label--','div'=>'comparator-drop-down-two'));
	
		echo $ajax->observeField('PostPlotLabel', 
			    array(
			        'url' => array( 'controller' => 'compare','action'=>'changePlotLabel'),
			        'frequency' => 0.2,
			    	'update' => 'comparison-results', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Element.hide(\'comparison-results\');Effect.Appear(\'comparison-results\',{ duration: 0.5 })',
			    	'with' => 'Form.serialize(\'PostAddForm\')'
			    ) 
		);		

		$distanceMatrixOptions = array(
					DISTANCE_BRAY 	   => 'Bray-Curtis',			
					DISTANCE_HORN 	   => 'Horn-Morisita',
					DISTANCE_JACCARD   => 'Jaccard',							
					DISTANCE_EUCLIDEAN => 'Euclidean',
					);		
		
		if($option == MDS_PLOT) {
			unset($distanceMatrixOptions[DISTANCE_HORN]);
		}			
					
		echo $form->input('distanceMatrix', 
			array( 'options' => $distanceMatrixOptions,'selected'=>$distanceMatrix,'label' => 'Distance Matrix', 'empty'=>'--Select Distance Matrix--','div'=>'comparator-drop-down-three'));
	
		echo $ajax->observeField('PostDistanceMatrix', 
			    array(
			        'url' => array( 'controller' => 'compare','action'=>'changeDistanceMatrix'),
			        'frequency' => 0.2,
			    	'update' => 'comparison-results', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Element.hide(\'comparison-results\');Effect.Appear(\'comparison-results\',{ duration: 0.5 })',
			    	'with' => 'Form.serialize(\'PostAddForm\')'
			    ) 
		);	
			
		
		if($option != MDS_PLOT) {
			echo $form->input('clusterMethod', 
				array( 'options' => array(
						CLUSTER_COMPLETE => 'Complete Linkage',
						CLUSTER_AVERAGE => 'Average Linkage',
						CLUSTER_SINGLE => 'Single Linkage',
						CLUSTER_WARD => "Ward's Minimum Variance",
						CLUSTER_MEDIAN => 'Median Linkage',
						CLUSTER_MCQUITTY => 'McQuitty',		
						CLUSTER_CENTROID => 'Centroid',		
						),
				'selected'=>$clusterMethod,'label' => 'Cluster Method', 'empty'=>'--Select Cluster Method--','div'=>'comparator-drop-down-four'));				
	
			echo $ajax->observeField('PostClusterMethod', 
				    array(
				        'url' => array( 'controller' => 'compare','action'=>'changeClusterMethod'),
				        'frequency' => 0.2,
				    	'update' => 'comparison-results', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Element.hide(\'comparison-results\');Effect.Appear(\'comparison-results\',{ duration: 0.5 })',
				    	'with' => 'Form.serialize(\'PostAddForm\')'
				    ) 
			);						
		}
		if($option == HEATMAP_PLOT) {
			echo $form->input('heatmap', array( 'options' => array(HEATMAP_COLOR_YELLOW_RED=>'red-yellow (default)',
																	HEATMAP_COLOR_YELLOW_BLUE=>'yellow-blue',
																	HEATMAP_COLOR_BLUE =>'blue',
																	HEATMAP_COLOR_GREEN =>'green'),'label' => 'Color', 'selected'=>$heatmapColor,'empty'=>'--Select Heatmap Color--','div'=>'comparator-drop-down-five'));
			
			echo $ajax->observeField('PostHeatmap', 
				    array(
				        'url' => array( 'controller' => 'compare','action'=>'changeHeatmapColor'),
				        'frequency' => 0.2,
				    	'update' => 'comparison-results', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Element.hide(\'comparison-results\');Effect.Appear(\'comparison-results\',{ duration: 0.5 })',
				    	'with' => 'Form.serialize(\'PostAddForm\')'
				    ) 
			);	
		}

	
		
		echo $form->end();	
		
		switch($option) {
				case HIERARCHICAL_CLUSTER_PLOT:
					$plotFile .= "_hclust_plot.pdf";
					break;															
				case MDS_PLOT:	
					$plotFile .= "_mds_plot.pdf";
					break;
				case HEATMAP_PLOT:	
					$plotFile .= "_heat_map.pdf";
					break;	
				
		}
		
	echo ("
		<p><iframe src=\"/metarep/tmp/$plotFile\" width=\"99%\"
	style=\"height:396px\" align=\"center\"
	>[Your browser does <em>not</em> support <code>iframe</code>,
	or has been configured not to display inline frames.
	You can access <a href=\"'/hmp-metarep/tmp/r_input.txt.heat_map.pdf'\">the document</a>
	via a link though.]</iframe>");
	}
	else {
		
		echo $form->end();	

		if($option == HEATMAP) {
			if($flipAxis == 0) {
				echo $matrix->printHeatMap($selectedDatasets,$counts,$option,$mode,$colorGradient);
			}
			elseif($flipAxis == 1) {		
				echo $matrix->printFlippedHeatMap($selectedDatasets,$counts,$option,$mode,$colorGradient);
			}
		}
		else {
			if($flipAxis == 0) {
				echo $matrix->printTable($selectedDatasets,$counts,$option,$mode,$maxPvalue);
			}
			elseif($flipAxis == 1) {		
				echo $matrix->printFlippedTable($selectedDatasets,$counts,$option,$mode);
			}
		}
	}
}
?>