<!----------------------------------------------------------
  File: tab_panel.ctp
  Description:

  Author: jgoll
  Date:   Mar 29, 2010
<!---------------------------------------------------------->

<?php 


$selectedDatasets 	= $session->read('selectedDatasets'); 
$option				= $session->read('option'); 
$minCount			= $session->read('minCount'); 
$optionalDatatypes	= $session->read('optionalDatatypes');
$mode				= $session->read('mode');
$tabs 				= $session->read('tabs');

#debug($tabs);

//switch($mode) {
//	case "taxonomy":
//		$tabPos = 1;
//		break;
//	case "geneOntology":
//		$tabPos = 2;
//	case "enzymes":
//		$tabPos = 3;		
//	case "hmms":
//		$tabPos = 4;
//		break;			
//	case "commonNames":
//		$tabPos = 5;		
//}



#debug($tabPos);

#$tabPos =4;

echo("
<fieldset class=\"comparator-main-panel\">
	<legend>Result Panel</legend>");


echo $ajax->div('tabs');
echo("<ul>");

$inactiveTabs = array();
$currentTabPosition =0;
$tabPosition = 0;
foreach($tabs as $tab) {
	
	if($tab['function'] === $mode) {
		$currentTabPosition = $tabPosition;
	}
	if(!$tab['isActive']) {
		array_push($inactiveTabs,$tabPosition);
	}
	
	echo("<li >");
		echo $ajax->link("<span>{$tab['tabName']}</span>",array('action'=>$tab['function']), array('update' => 'comparison-results', 'indicator' => 'spinner','title' => 'comparison-results','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'comparison-results\',{ duration: 0.5 })', 'before' => 'Element.hide(\'comparison-results\')'), null, null, false); 
	echo("</li>");
	$tabPosition ++;
}

echo("<ul>");
#debug('['.explode(',',$inactiveTabs).']');


//		echo("<li >");
//			echo $ajax->link('<span>Taxonomy (Blast)</span>',array('action'=>'taxonomy','blast_tree'), array('update' => 'comparison-results', 'title' => 'comparison-results','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'comparison-results\',{ duration: 1.5 })', 'before' => 'Element.hide(\'comparison-results\')'), null, null, false); 
//		echo("</li>");
//
//		echo("<li>");
//			echo $ajax->link('<span>Gene Ontology</span>',array('action'=>'geneOntology'), array('update' => 'comparison-results', 'title' => 'comparison-results','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'comparison-results\',{ duration: 1.5 })', 'before' => 'Element.hide(\'comparison-results\')'), null, null, false); 
//		echo("</li>");
//		echo("<li >");
//			echo $ajax->link('<span>Pathways</span>',array('action'=>'pathways'), array('update' => 'comparison-results', 'title' => 'comparison-results','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'comparison-results\',{ duration: 1.5 })', 'before' => 'Element.hide(\'comparison-results\')'), null, null, false);
//		echo("</li>");
//		echo("<li>");
//			echo $ajax->link('<span>Enzymes</span>',array('action'=>'enzymes'), array('update' => 'comparison-results', 'title' => 'comparison-results','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'comparison-results\',{ duration: 1.5 })', 'before' => 'Element.hide(\'comparison-results\')'), null, null, false); 
//		echo("</li>");
//		echo("<li>");
//		echo $ajax->link('<span>HMMs</span>',array('action'=>'hmms'), array('update' => 'comparison-results', 'title' => 'comparison-results','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'comparison-results\',{ duration: 1.5 })', 'before' => 'Element.hide(\'comparison-results\')'), null, null, false);
//		echo("</li>");
//		echo("<li>");		
//		echo $ajax->link('<span>Common Names</span>',array('action'=>'commonNames'), array('update' => 'comparison-results', 'title' => 'comparison-results','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'comparison-results\',{ duration: 1.5 })', 'before' => 'Element.hide(\'comparison-results\')'), null, null, false); 				
//		echo("</li>");		
//		
//		#if($optionalDatatypes['clusters']) {	
//			echo("<li >");			
//			echo $ajax->link('<span>Clusters</span>',array('action'=>'clusters'), array('update' => 'comparison-results', 'title' => 'comparison-results','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'comparison-results\',{ duration: 1.5 })', 'before' => 'Element.hide(\'comparison-results\')'), null, null, false);
//			echo("</li>");
//		#}
//		#if($optionalDatatypes['apis']) {		
//			echo("<li >");
//			echo $ajax->link('<span>Taxonomy (Apis)</span>',array('action'=>'taxonomy','apis_tree'), array('update' => 'comparison-results', 'title' => 'comparison-results','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'comparison-results\',{ duration: 1.5 })', 'before' => 'Element.hide(\'comparison-results\')'), null, null, false); 
//			echo("</li>");
//		#}				
//		#if($optionalDatatypes['viral']) {	
//			echo("<li >");			
//			echo $ajax->link('<span>Environmental Libraries</span>',array('action'=>'environmentalLibraries'), array('update' => 'comparison-results', 'title' => 'comparison-results','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'comparison-results\',{ duration: 1.5 })', 'before' => 'Element.hide(\'comparison-results\')'), null, null, false);
//			echo("</li>");
//		#}		
//	echo("</ul>");
		
echo $ajax->divEnd('tabs');	

echo $html->div('comparator-download', $html->link($html->image("download-large.png"), array('controller'=> 'compare','action'=>'download'),array('escape' => false)));	

echo("</fieldset>");
?>

<script type="text/javascript">
jQuery(function() {
	jQuery("#tabs").tabs({ spinner: '<img src="/metarep/img/ajax.gif\"/>' });
	jQuery("#tabs").tabs( "option", "disabled", <?php echo('['.implode(',',$inactiveTabs).']');?>);
	 jQuery("#tabs").tabs( "option", "selected",<?php echo($currentTabPosition);?>);
	 
});



/*jQuery("#tabs").bind( "tabsselect", function(event, ui) {
	var tabId = jQuery("#tabs").tabs( "option", "selected");
	alert(tabId);
	
	});
	
	
	*/
</script>	

