<!----------------------------------------------------------
 
  File: apis_taxonomy.ctp
  Description: View page to browse NCBI taxonomy based on
  taxonomic APIS assignments stored in the apis_tree field.
  (JCVI-only feature)

  PHP versions 4 and 5

  METAREP : High-Performance Comparative Metagenomics Framework (http://www.jcvi.org/metarep)
  Copyright(c) J. Craig Venter Institute (http://www.jcvi.org)

  Licensed under The MIT License
  Redistributions of files must retain the above copyright notice.

  @link http://www.jcvi.org/metarep METAREP Project
  @package metarep
  @version METAREP v 1.0.1
  @author Johannes Goll
  @lastmodified 2010-07-09
  @license http://www.opensource.org/licenses/mit-license.php The MIT License
    
<!---------------------------------------------------------->

<?php echo $html->css('browse.css'); ?>
<div id="Browse">
	<ul id="breadcrumb">
	 	<li><a href="/metarep/dashboard/index" title="Dashboard"><img src="/metarep/img/home.png" alt="Dashboard" class="home" /></a></li>
	    <li><?php echo $html->link('List Projects', "/projects/index");?></li>
	    <li><?php echo $html->link('View Project', "/projects/view/$projectId");?></li>
	    <li><?php echo $html->link('Browse Dataset', "/browse/apisTaxonomy/$dataset");?></li>
	</ul>

	<h2><?php __("Browse Taxonomy (Apis)");?><span class="selected_library"><?php echo "$dataset ($projectName)"; ?></span>	
	<span id="spinner" style="display: none;">
	 	<?php echo $html->image('ajax-loader.gif', array('width'=>'25px')); ?>
	 </span></h2><BR>
	
	<div id="browse-main-panel">	
		<div id="browse-tree-panel">			
			<fieldset>
			<legend>NCBI Taxonomy Tree </legend>
			<a href="#" id="dialog_link" class="ui-state-default ui-corner-all"><span class="ui-icon ui-icon-newwin"></span>Help</a>
			<?php 
			$treeData = $session->read($mode.'.tree');
			echo $tree->taxonomy($dataset,$treeData,$taxon,'apisTaxonomy');
			?>
			</fieldset>
		</div>
		<div id="browse-right-panel">
			<div id="browse-classification-panel">	
				<fieldset>
				<legend>Taxonomic Distribution</legend>
				<?php echo $html->div('browse-download-classification', $html->link($html->image("download-medium.png"), array('controller'=> 'browse','action'=>'downloadChildCounts',$dataset,$taxon,$mode,$numHits),array('escape' => false)));?>						
				<h2 <span class="selected_library"><?php echo($taxon)?></h2>
				<?php 
				echo $facet->pieChart('',$childCounts,$numHits,"700x300");
				?>
				</fieldset>
			</div>
			<div id="browse-facet-list-panel">
				<?php echo $html->div('browse-download-facets', $html->link($html->image("download-medium.png"), array('controller'=> 'browse','action'=>'dowloadFacets',$dataset,$taxon,$mode,$numHits),array('escape' => false)));?>	
				<?php echo $facet->topTenList($facets,$numHits);?>	
			</div>
			<div id="browse-facet-pie-panel">
				<?php echo $html->div('browse-download-facets', $html->link($html->image("download-medium.png"), array('controller'=> 'browse','action'=>'dowloadFacets',$dataset,$taxon,$mode,$numHits),array('escape' => false)));?>	
				<?php  echo $facet->topTenPieCharts($facets,$numHits,"700x200","300x150");?>
			</div>
	</div>
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

<?php $dialog->browseTaxonomy('dialog')?>	
