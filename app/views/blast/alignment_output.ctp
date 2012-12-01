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
  @version METAREP v 1.3.2
  @author Johannes Goll
  @lastmodified 2010-07-09
  @license http://www.opensource.org/licenses/mit-license.php The MIT License
  
<!---------------------------------------------------------->


<?php

$selectedDatasets 	= $session->read('selectedDatasets'); 
$option				= $session->read('option'); 
$minCount			= $session->read('wordCount'); 
$tabs				= $session->read('tabs');
$mode				= $session->read('mode');

$currentTabPosition =0;
$tabPosition = 0;
if(isset($tabs)) {
	foreach($tabs as $tab) {
		if($tab['function'] === $mode) {			
			$currentTabPosition = $tabPosition;
		}
		$tabPosition ++;
	}
}

?>

<script>
jQuery(document).ready(function(){
  jQuery.noConflict() ;
  
  jQuery("#myTable").tablesorter({widgets:['zebra']}); 
	
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
	echo("<div id=\"resize-box\"><a href=\"#\" class=\"increaseFont\">zoom in</a> <a href=\"#\" class=\"decreaseFont\">zoom out</a>
				</div>");
	echo("<div class=\"comparison-results-table\">");
	echo(file_get_contents($htmlFilePath));
	echo("<div>");
	
?>

