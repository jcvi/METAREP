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
  @version METAREP v 1.0.1
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
$flipAxis			= $session->read('flipAxis');
$tabs				= $session->read('tabs');

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
	echo $form->input( 'level', array( 'options' => $levels, 'selected' => $level,'label' => false, 'empty'=>'--select level--','div'=>'comparator-level-select'));
	
	if($option == HEATMAP) {
		echo $form->input( 'heatmap', array( 'options' => array(0=>'red-yellow (default)',1=>'yellow-blue',2=>'blue',3=>'green'),'label' => false, 'empty'=>'--Select Heatmap Color--','div'=>'comparator-heatmap-color-select'));
	}
	
	echo $form->end();	
				
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
		
	if($option > 6) {	
		
		switch($option) {
				case COMPLETE_LINKAGE_CLUSTER_PLOT:
					$plotFile .= "_hclust_plot.pdf";
					break;
				case SINGLE_LINKAGE_CLUSTER_PLOT:
					$plotFile .= "_hclust_plot.pdf";
					break;			
				case AVERAGE_LINKAGE_CLUSTER_PLOT:
					$plotFile .= "_hclust_plot.pdf";
					break;	
				case WARDS_CLUSTER_PLOT:
					$plotFile .= "_hclust_plot.pdf";
					break;		
				case MEDIAN_CLUSTER_PLOT:
					$plotFile .= "_hclust_plot.pdf";
					break;	
				case MCQUITTY_CLUSTER_PLOT:
					$plotFile .= "_hclust_plot.pdf";
					break;	
				case CENTROID_CLUSTER_PLOT:
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
	You can access <a href=\"'/metarep/tmp/r_input.txt.heat_map.pdf'\">the document</a>
	via a link though.]</iframe>");
	}
	else {
		if($option == HEATMAP) {
			if($flipAxis == 0) {
				echo $matrix->printHeatMap($selectedDatasets,$counts,$option,$mode,$heatmapColor);
			}
			elseif($flipAxis == 1) {		
				echo $matrix->printFlippedHeatMap($selectedDatasets,$counts,$option,$mode,$heatmapColor);
			}
		}
		else {
			if($flipAxis == 0) {
				echo $matrix->printTable($selectedDatasets,$counts,$option,$mode);
			}
			elseif($flipAxis == 1) {		
				echo $matrix->printFlippedTable($selectedDatasets,$counts,$option,$mode);
			}
		}
	}
}
?>