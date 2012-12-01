<!----------------------------------------------------------
 
  File: pathways.ctp
  Description: View page to browse KEGG and Metacyc metabolic pathways 
  based on the ec_id field.

  PHP versions 4 and 5

  METAREP : High-Performance Comparative Metagenomics Framework (http://www.jcvi.org/metarep)
  Copyright(c)  J. Craig Venter Institute (http://www.jcvi.org)

  Licensed under The MIT License
  Redistributions of files must retain the above copyright notice.

  @link http://www.jcvi.org/metarep METAREP Project
  @package metarep
  @version METAREP v 1.4.0
  @author Johannes Goll
  @lastmodified 2010-10-31
  @license http://www.opensource.org/licenses/mit-license.php The MIT License
    
<!---------------------------------------------------------->
<?php echo $html->css('comparator.css'); ?>
<?php echo $javascript->link(array('michael-multiselect-2a0569f/js/jquery-1.3.2.min.js')); ?>
<?php echo $javascript->link(array('michael-multiselect-2a0569f/js/jquery-ui-1.7.1.custom.min.js')); ?>
<?php echo $javascript->link(array('michael-multiselect-2a0569f/js/plugins/localisation/jquery.localisation-min.js')); ?>
<?php echo $javascript->link(array('michael-multiselect-2a0569f/js/plugins/scrollTo/jquery.scrollTo-min.js')); ?>
<?php echo $javascript->link(array('michael-multiselect-2a0569f/js/ui.multiselect.large.js')); ?>
<?php echo $javascript->link(array('tablesorter-2.0/jquery.tablesorter.js')); ?>
<?php echo $javascript->link(array('prototype')); ?>

<?php 	
$selectedDatasets 	= $session->read('selectedDatasets'); 
$option				= $session->read('option'); 
$minCount			= $session->read('minCount'); 
$plotFile			= $session->read('plotFile');
$mode				= $session->read('mode');
$level				= $session->read("$mode.level");
$counts				= $session->read('counts');
$heatmapColor		= $session->read('heatmapColor');
$plotLabel			= $session->read('plotLabel');
$flipAxis			= $session->read('flipAxis');
$maxPvalue			= $session->read('maxPvalue');

?>
<div id="compare-pathway-map">
	<ul id="breadcrumb">
	 	<li><a href="/metarep/dashboard/index" title="Dashboard"><img src="/metarep/img/home.png" alt="Dashboard" class="home" /></a></li>
	    <li><?php echo $html->link('Projects', "/projects/index");?></li>
	    <li><?php echo $html->link('View Project', "/projects/view/$projectId");?></li>
	    <li><?php echo $html->link('Compare Datasets', "/compare/index/$dataset1");?></li>
	    <li><?php echo $html->link('Compare Pathway Map', "compare/pathwayMap/$mode/00660");?></li>
	</ul>

	<h2><?php __("Compare Pathway Map");?><span class="selected_library"><?php echo "$dataset1 vs. $dataset2 ($projectName)"; ?></span>	
	<span id="spinner" style="display: none;">
	<?php echo $html->image('ajax-loader.gif', array('width'=>'25px')); ?>
	 </span></h2><BR>


<div id="compare-pathway-main-panel">	

<?php
echo("
<fieldset>
	<legend>KEGG Pathway Map</legend>");
echo("<p>");

echo("<div class=\"frame\" style=\"overflow: auto; width: 100%; height: 400px;text-align: center;\">");
#echo("<table style=\"overflow: auto; width: 100%; height: 400px\"><tr>");
echo("<div style=\"position:relative; float:left;width: 400px\">    
  <img src=\"$pathwayImage\" alt=\"\" />
  <div style=\"position:absolute;left:10px;top:5px;width: 400px;white-space:nowrap\">
    The background color encodes the log odds ratio (see legend); red border and label color indicates the statistical significance level selected by the p-value drop down. To save the image, right click on it and choose \"Save Image As\".
  </div>
</div>");
echo("</div>");

#echo($html->image($pathwayImage,array("title" => '')));
echo("</tr></table>");
#echo("</div>");

echo("</p>");

echo("<table style=\"border:1px; padding-bottom:5px; border-bottom-style:solid;border-width:1px;\">
						<tr>");		
echo("<th style=\"padding-center:5px; width:40%; border-width:0px;font-size:1.2em;background-color:#FFFFFF;\">$dataset1 (Logg Odds Ratio > 0)</th>");
echo("<th style=\"padding-center:5px; width:50%; border-width:0px;font-size:1.2em;background-color:#FFFFFF;\">$dataset2 (Logg Odds Ratio < 0)</th>");
echo("</tr></table>");

echo("<table cellpadding=\"0\" cellspacing=\"0\"><tr>");

$offset= -2;
$step  = 0.2;

foreach($colorGradient as $color => $entry) {
	echo("<td class=\"comparator-heatmap-legend\" style=\"background-color:#$color; \">{$entry['lab']}</td>");		
	$offset +=$step;
}
echo("<td class=\"comparator-heatmap-legend\" style=\"background-color:#FFFFF; \">missing</td>");	
echo("</table></fieldset><BR>");


echo("
<fieldset >

<legend>Result Panel</legend>");
echo $html->div('comparator-pathway-map-download', $html->link($html->image("download-medium.png",array("title" => 'Download Statistics')), array('controller'=> 'compare','action'=>'download'),array('escape' => false)));	

echo $form->create( 'Post' );
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
						'p-value (bonferroni)' =>
							array(	
								PVALUE_BONFERONI_HIGH_SIGNIFICANCE=>'< 0.01',
								PVALUE_BONFERONI_MEDIUM_SIGNIFICANCE => '< 0.05',
								PVALUE_BONFERONI_LOW_SIGNIFICANCE => '< 0.10',
							),	
						'q-value (fdr)' =>
							array(	
								PVALUE_FDR_HIGH_SIGNIFICANCE=>'< 0.01',
								PVALUE_FDR_MEDIUM_SIGNIFICANCE => '< 0.05',
								PVALUE_FDR_LOW_SIGNIFICANCE => '< 0.10',
							),													
						),							
				'selected' => $maxPvalue,'label' => 'max. p-value', 'empty'=>'--Select Pvalue--','div'=>'comparator-pathway-drop-down'));
	
		echo $ajax->observeField('PostMaxPvalue', 
			    array(
			        'url' => array( 'controller' => 'compare','action'=>'filterPathwayMapByEvalue',$mode,$level,$externalPathwayId),
			        'frequency' => 0.2,
			    	'update' => 'compare-pathway-map', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Element.hide(\'compare-pathway-main-panel\');Effect.Appear(\'compare-pathway-main-panel\',{ duration: 0.5 })',
			    	'with' => 'Form.serialize(\'PostAddForm\')'
			    ) 
		);	
echo $form->end();	
echo $matrix->printTable($selectedDatasets,$counts,$option,$mode,$maxPvalue,$level)."</fieldset>";	
?>
</div> 	
</div> 
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
  	elseif($option == CHISQUARE || $option == FISHER || $option == PROPORTION_TEST) {
  		echo(",  sortList: [[7,0],[5,1]] ");
  	}
  	elseif($mode === 'keggPathwaysEc') {
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
	}}); 
     
});
</script>