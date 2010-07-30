<!----------------------------------------------------------
  
  File: index.ctp
  Description: Search Index Page
  
  The Search Index Page let's users enter search terms and 
  specify the annotation field they would like to search in.
  The search returns results as well as frequency count lists
  and pie charts, that summarize the top functional and taxonomic
  categories for the identified subset. Counts and identifiers 
  can be exported as tab delimited files. 

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

<?php echo $html->css('search_dataset.css'); ?>

<div id="search-dataset">

	<ul id="breadcrumb">
	 	<li><a href="/metarep/dashboard/index" title="Dashboard"><img src="/metarep/img/home.png" alt="Dashboard" class="home" /></a></li>
	    <li><?php echo $html->link('List Projects', "/projects/index");?></li>
	    <li><?php echo $html->link('View Project', "/projects/view/$projectId");?></li>
	    <li><?php echo $html->link('Search Dataset', "/view/index/$dataset");?></li>
	</ul>
	<?php 	
			#read session variables
			$query = $session->read($sessionQueryId);
			$searchFields = $session->read('searchFields');
			$field = $session->read('searchField');
			
	?>
	<h2><?php __("Search Dataset");?><span class="selected_library"><?php echo "$dataset ($projectName)"; ?></span><span id="spinner" style="display: none;"><?php echo $html->image('ajax-loader.gif', array('width'=>'25px')); ?></span></h2>
	
	<div class="search-panel">
		<a href="#" id="dialog_link" class="ui-state-default ui-corner-all"><span class="ui-icon ui-icon-newwin"></span>Help</a>
		<fieldset>
		<legend> </legend>
		
		<?php echo $form->create('Search', array('url' => array('controller' => 'search', 'action' => 'index',$dataset))); ?>
		
		
		<?php 
			echo('<div class="search-box">');
			
			if(!isset($exception)) {
				$label = "Found <B>$numHits hits</b> in <b>$dataset</b> for";
			}
			else {
				$label = "<b><FONT COLOR=\"#990000\">$exception</FONT><b>";
			}
			
			echo $form->input("query", array('type'=>'text', 'value'=>$query,'label' => $label));
			echo('</div>');	
			
			echo $form->input('field',array('options' => $searchFields,'label' => "Select Search Field",'selected' =>$field,'div'=>'search-field-select-option'));
			echo $ajax->submit('Search', array('url'=> array('controller'=>'search', 'action'=>'index',$dataset),'update' => 'search-dataset', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Element.hide(\'search-results\');Effect.Appear(\'search-results\',{ duration: 1.5})','before' => 'Element.hide(\'search-results\')'));
			echo $form->end();
		?>
		</fieldset>
	</div>
 	<div id="search-results">
		<?php if($numHits>0) { ?>
		
			<?php echo $html->div('download', $html->link($html->image("download-medium.png",array("title" => "Download Top Ten List")), array('controller'=> 'search','action'=>'dowloadFacets',$dataset,$numHits,$sessionQueryId),array('escape' => false)));?>	
			<?php echo $facet->topTenList($facets,$numHits);?>	
		
			<div class="facet-pie-panel">
			<?php echo $html->div('download', $html->link($html->image("download-medium.png",array("title" => "Download Top Ten List")), array('controller'=>  'search','action'=>'dowloadFacets',$dataset,$numHits,$sessionQueryId),array('escape' => false)));?>	
			<?php  echo $facet->topTenPieCharts($facets,$numHits,"700x200");?>
			</div>
			
			<div class="data-panel">
			<?php echo $html->div('download', $html->link($html->image("download-medium.png",array("title" => "Download Peptide Id List")), array('controller'=>  'search','action'=>'dowloadData',$dataset,$numHits,$sessionQueryId),array('escape' => false)));?>	
			<?php  echo $luceneResultPaginator->data($dataset,$hits,$page,$numHits,$sessionQueryId);?>
			</div>
		<?php }?>
	</div>
</div>
<?php
echo $ajax->observeField( 'SearchField', 
    array(
        'url' => array('controller'=>'search', 'action'=>'index',$dataset),
        'frequency' => 0.1,
    	'update' => 'search-dataset', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Element.hide(\'search-results\');Effect.Appear(\'search-results\',{ duration: 1.5})','before' => 'Element.hide(\'search-results\')',
		'with' => 'Form.serialize(\'SearchAddForm\')'
    ) 
);
?>

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
<?php echo $dialog->printSearch("dialog",$dataset) ?>	