<!----------------------------------------------------------

  File: index.ctp
  Description: Compare Index Page
  
  The compare pages allow users to compare multiple datasets.  
  Users can filter available datasets by their name (using a
  ajax-driven text box) or by their annotation content (using
  the Lucene query language). A minimum absolute count can be
  entered by users to filter out categories with only a few 
  hits. Compare options include absolute and relative counts,
  statistical tests, multidimensional scaling, heatmap and 
  hierarchical cluster plots. 
 
  Similar to the View pages, users can choose from several tabs
  to indicate the annotation data type they wish to compare 
  (see tab_panel.ctp). Choices are NCBI Taxonomy, Gene Ontology
  KEGG metabolic pathways, Enzyme Classification, HMMs, and
  functional descriptions. The level of comparison be adjusted
  and statistics ad graphics can be exported (see result_panel.ctp).

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

<?php echo $html->css('comparator.css'); ?>
<?php echo $javascript->link(array('michael-multiselect-2a0569f/js/jquery-1.3.2.min.js')); ?>
<?php echo $javascript->link(array('michael-multiselect-2a0569f/js/jquery-ui-1.7.1.custom.min.js')); ?>
<?php echo $javascript->link(array('michael-multiselect-2a0569f/js/plugins/localisation/jquery.localisation-min.js')); ?>
<?php echo $javascript->link(array('michael-multiselect-2a0569f/js/plugins/scrollTo/jquery.scrollTo-min.js')); ?>
<?php echo $javascript->link(array('michael-multiselect-2a0569f/js/ui.multiselect.large.js')); ?>
<?php echo $javascript->link(array('tablesorter-2.0/jquery.tablesorter.js')); ?>
<?php echo $javascript->link(array('prototype')); ?>
<?php echo $html->css('ui.multiselect.css'); ?>

<?php 
#get session results
$allDatasets= $session->read('allDatasets'); 
$minCount 	= $session->read('minCount');
$option 	= $session->read('option');
$filter 	= $session->read('filter');
?>

<div id="compare">
<ul id="breadcrumb">
 	<li><a href="/metarep/dashboard/index" title="Dashboard"><img src="/metarep/img/home.png" alt="Dashboard" class="home" /></a></li>
    <li><?php echo $html->link('List Projects', "/projects/index");?></li>
    <li><?php echo $html->link('View Project', "/projects/view/$projectId");?></li>
    <li><?php echo $html->link('Compare Datasets', "/compare/index/$dataset");?></li>
</ul>

<?php echo $form->create('Compare'); ?>
<h2><?php __("Compare"); ?>
<span id="spinner" style="display: none;">
<?php echo $html->image('ajax-loader.gif', array('width'=>'25px')); ?>
 </span></h2><BR>
<div class="comparator-error-message"><?php if(isset($exception)){ echo $exception;}; ?></div>
<fieldset class="comparator-multi-select-panel">
	<legend>Select Datasets</legend>

    <select id="selectedDatasets" class="multiselect" multiple="multiple" name="data[selectedDatasets][]">
      
	<?php 
		foreach ($allDatasets as $id =>$name) {					
				if(count($selectedDatasets)>0 && in_array($id,$selectedDatasets)){
				 	echo("<option value=\"{$id}\" selected=\"selected\">{$name}</option>");
				}
				else {
					echo("<option value=\"{$id}\">{$name}</option>");
				}
		}
	?>      
	</select>      
</fieldset>



<div class="compare-radio-box"> 
	<label for="no">project datasets&nbsp;<input type="radio" name="project-datasets" id="CompareSelection.project" VALUE="all datasets" <?php if($mode) {echo 'CHECKED';} ?> /></label>
	<label for="yes">all datasets&nbsp;<input type="radio" name="project-datasets" id="CompareSelection.all" VALUE="project datasets"    <?php if(!$mode) {echo 'CHECKED';} ?>/></label>
</div>

<script type="text/javascript">
//<![CDATA[
new Form.Element.EventObserver('CompareSelection.project', function(element, value) {new Ajax.Updater('compare','/metarep/compare/index/<?php echo $dataset ?>/1', {asynchronous:true, evalScripts:true, onComplete:function(request, json) {Element.hide('tab-panel');Effect.Appear('tab-panel',{ duration: 1.5 }); Element.hide('spinner');}, onLoading:function(request) {Element.show('spinner');}, parameters:Form.serialize('CompareAddForm'), requestHeaders:['X-Update', 'compare']})})
//]]>
</script>
<script type="text/javascript">
//<![CDATA[
new Form.Element.EventObserver('CompareSelection.all', function(element, value) {new Ajax.Updater('compare','/metarep/compare/index/<?php echo $dataset ?>/0', {asynchronous:true, evalScripts:true, onComplete:function(request, json) {Element.hide('tab-panel');Effect.Appear('tab-panel',{ duration: 1.5 }); Element.hide('spinner');}, onLoading:function(request) {Element.show('spinner');}, parameters:Form.serialize('CompareAddForm'), requestHeaders:['X-Update', 'compare']})})
//]]>
</script>

<fieldset class="comparator-query-panel">
	<legend>Filter Datasets</legend>
	<?php echo $form->input("filter", array('type'=>'text', 'value'=>$filter,'label' => false,'div' => 'filter-input-form')); ?>
</fieldset>

<fieldset class="comparator-option-panel">
	<legend>Options</legend>	
	
	<a href="#" id="dialog_link" class="ui-state-default ui-corner-all"><span class="ui-icon ui-icon-newwin"></span>Help</a>
	

	<?php 		
	//skipping ,'fisher.test'=>"Fisher's Exact Test"
	#echo $form->input('test', array( 'options' => array('chisq.test'=>'Chi-Square Test of Independence','metastats'=>'METASTATS - non parametric t-test'),'label' => false, 'empty'=>'--Select Statistical Test--','div'=>'comparator-test-select-option','selected'=>$test));
	echo $form->input('option', 
						array('options' => 	
							array(	'Summary' => array( 
												1 =>'Absolute Counts',
												2 =>'Relative Counts',
												3 =>'Heatmap Counts',
												),
									'Statistical Test' => array( 
												4 =>'Chi-Square Test of Independence',
												5 =>'Wilcoxon Rank Sum Test',
												6 =>'METASTATS - non parametric t-test',),
							
									'Plots' => array( 
												HIERARCHICAL_CLUSTER_PLOT  =>'Hierarchical Cluster Plot',												
												HEATMAP_PLOT =>'HeatMap Plot',
												MDS_PLOT =>'Multidimensional Scaling Plot',),			
							),
							'label' => false,'selected' => $option,'div'=>'comparator-select-option')
						);
						
	echo $form->input('minCount',array('type'=>'text', 'value'=>$minCount,'label' => 'Min. Count','div' => 'comparator-min-count-option')); 		
	echo $ajax->submit('Update', array('url'=> array('controller'=>'compare', 'action'=>'ajaxTabPanel'),'update' => 'tab-panel', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Element.hide(\'tab-panel\');Effect.Appear(\'tab-panel\',{ duration: 0.5})'));
	echo $dialog->compare("dialog") ?>
</fieldset>  

<?php  
echo $form->end();?>
<div id="tab-panel"></div>

<?php 
#to track changes in check box
echo $ajax->observeField( 'CompareOption', 
    array(
        'url' => array('controller'=>'compare', 'action'=>'ajaxTabPanel'),
        'frequency' => 0.1,
    	'update' => 'tab-panel', 'indicator' => 'spinner','complete' => 'Element.hide(\'tab-panel\');Effect.Appear(\'tab-panel\',{ duration: 1.5 })',
		'with' => 'Form.serialize(\'CompareAddForm\')'
    ) 
);



?>
<script type="text/javascript">
function changeUrl() {
	 alert(iframeData.innerHTML);
		
	var redirect;
	redirect = document.getElementById('newUrl').value;
	document.location.href = redirect;
	}
</script>
<script type="text/javascript">			
	jQuery('#dialog').dialog({
		autoOpen: false,
		width: 900,
		height: 600,
		buttons: {
			"Ok": function() { 
				jQuery(this).dialog("close"); 
			},
		}
	});
</script>

<script type="text/javascript">
	jQuery(function(){
	  // choose either the full version
	  jQuery(".multiselect").multiselect();
	  // or disable some features
	  //$(".multiselect").multiselect({sortable: false, searchable: false});
	  jQuery("#tabs").tabs({ spinner: '<img src="/metarep/img/ajax.gif\"/>' });
	});

</script>
</div>