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
  @version METAREP v 1.3.0
  @author Johannes Goll
  @lastmodified 2010-10-31
  @license http://www.opensource.org/licenses/mit-license.php The MIT License
    
<!---------------------------------------------------------->

<?php echo $html->css('browse.css'); 
	

//handle session variables
if($mode === KEGG_PATHWAYS) {
	$function = 'keggPathwaysEc';
	$dialog->browseKeggPathways('dialog');
}
else if($mode === KEGG_PATHWAYS_KO) {
	$function = 'keggPathwaysKo';
	$dialog->browseKeggPathways('dialog');
}
else if($mode === METACYC_PATHWAYS) {
	$function = 'metacycPathways';
	$dialog->browseMetacycPathways('dialog');
}

if($session->check("$function.browse.query")) {
	$filter = $session->read("$function.browse.query");
}
else {
	$filter = '*:*';
}
if($session->check("$function.browse.facets")) {
	$facets = $session->read("$function.browse.facets");
}
else {
	$facets = array();
}

if($session->check("$function.browse.tree")) {
	$treeData = $session->read("$function.browse.tree");
}
else {
	$treeData = array();
}



$facetFields = $session->read("$function.browse.facetFields");
?>
<div id="Browse">
	<ul id="breadcrumb">
	 	<li><a href="/metarep/dashboard/index" title="Dashboard"><img src="/metarep/img/home.png" alt="Dashboard" class="home" /></a></li>
	    <li><?php echo $html->link('List Projects', "/projects/index");?></li>
	    <li><?php echo $html->link('View Project', "/projects/view/$projectId");?></li>
	    <li><?php echo $html->link('Browse Dataset', "/browse/pathways/$dataset");?></li>
	</ul>

	<h2><?php echo $header;?><span class="selected_library"><?php echo "$dataset ($projectName)"; ?></span>	
	<span id="spinner" style="display: none;">
	 		 	<?php echo $html->image('ajax-loader.gif', array('width'=>'25px')); ?>
	 </span></h2><BR>
	
	<div id="browse-main-panel">	
		<div id="browse-left-panel">		
			<div id="browse-search-panel">			
				<fieldset>
					<legend>Filter</legend>				
					<?php echo $form->create('Filter');?>	
					<a href="#" id="dialog_link" class="ui-state-default ui-corner-all"><span class="ui-icon ui-icon-newwin"></span>Help</a>	
					<?php echo $form->input("filter", array('type'=>'text', 'value'=>$filter,'label' => false,'div' => 'filter-input-form')); ?>
					<?php echo $ajax->submit('Filter', array('url'=> array('controller'=>'browse', 'action'=>'filter',$dataset,$function),'update' => 'Browse', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'browse-main-panel\',{ duration: 1.5 })', 'before' => 'Element.hide(\'browse-main-panel\');'));?>
					<?php echo $form->end();?>
				</fieldset>
			</div>		
			<div id="browse-tree-panel">			
				<fieldset>
				<legend><?php echo $header;?></legend>			
					<?php 	
					
					if($numHits == 0) {
						echo("<div id=\"flashMessage\" class=\"message\" style=\"text-align:center\">No hits found. Please try again with a different filter query.</div>");						
					} 
					else {
						echo $tree->pathways($dataset,$treeData,$node,$function);
					}
					?>
				</fieldset>
			</div>
		</div>	
		<?php if($numHits > 0) :?>		
		<div id="browse-right-panel">			
			<div id="browse-classification-panel">	
				<fieldset>
				<legend>Pathway Classification</legend>
				
				
				<?php echo $html->div('browse-download-classification', $html->link($html->image("download-medium.png"), array('controller'=> 'browse','action'=>'downloadChildCounts',$dataset,$node,$mode,array_sum($childCounts),urlencode($filter)),array('escape' => false)));?>						
				
				
				<h2 <span class="selected_library"><?php echo(base64_decode($node))?></h2>
				<?php 				
					
					if($level != 'pathway') {		
						echo $facet->pieChart('',$childCounts,$numHits,"700x300");									
					}
					
					if($level === 'pathway') {
						
						echo("<p><iframe src=\"$url\" target=\"_blank\"  width=\"100%\"
						style=\"height:405px\"; top:\"100px\"; align=\"center\" scrolling=\"yes\"
						>[Your browser does <em>not</em> support <code>iframe</code>,
						or has been configured not to display inline frames.]</iframe></p>");
						
						echo("<table cellpadding=\"0\" cellspacing=\"0\"><tr>");
						
						$offset= 0;
						$step  = 0.05;
						
						foreach($colorGradient as $color) {
							$start = $offset;
							$end   =  $offset + $step;
							echo("<td class=\"comparator-heatmap-legend\" style=\"background-color:#$color; \">{$start} - {$end}</td>");
							$offset +=$step;
						}
						echo("</table>");
						//echo $html;
						
						
						if($mode === KEGG_PATHWAYS_KO) {
							echo $facet->oneFacetTable($childCounts,'Kegg Ortholog',$numHits);
						}	
						else if($mode === KEGG_PATHWAYS || $mode === METACYC_PATHWAYS) {
							echo $facet->oneFacetTable($childCounts,'Enzymes',$numHits);
						}
					}
					
				?>
				
			</fieldset>
			</div>			
			<?php if(!empty($facets)) :?>
			<div id="browse-facet-list-panel">
				<?php echo $html->div('browse-download-facets', $html->link($html->image("download-medium.png"), array('controller'=> 'browse','action'=>'dowloadFacets',$dataset,$node,$mode,$numHits,urlencode($filter)),array('escape' => false)));?>	
				<?php echo $facet->topTenList($facets,$facetFields,$numHits);?>	
			</div>
			<div id="browse-facet-pie-panel">
				<?php echo $html->div('browse-download-facets', $html->link($html->image("download-medium.png"), array('controller'=> 'browse','action'=>'dowloadFacets',$dataset,$node,$mode,$numHits,urlencode($filter)),array('escape' => false)));?>	
				<?php  echo $facet->topTenPieCharts($facets,$facetFields,$numHits,"700x200","300x150");?>
			</div>
			<?php endif;?>
		</div>
		<?php endif;?>
</div>

<script type="text/javascript">
 jQuery.noConflict();
	
	jQuery(function(){			

		// Dialog			
		jQuery('#dialog').dialog({
			autoOpen: false,
			width: 400,
			modal: true,
			buttons: {
				"Ok": function() { 
					jQuery(this).dialog("close"); 
				},
			}
		});
		
		// Dialog Link
		jQuery('#dialog_link').click(function(){
			jQuery('#dialog').dialog('open');
			return false;
		});
});
</script>


