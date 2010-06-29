<!----------------------------------------------------------
  File: index.ctp
  Description: Search Index Page

  Author: jgoll
  Date:   Mar 4, 2010
<!---------------------------------------------------------->

<ul id="breadcrumb">
 	<li><a href="/metarep/dashboard/index" title="Dashboard"><img src="/metarep/img/home.png" alt="Dashboard" class="home" /></a></li>
    <li><?php echo $html->link('List Projects', "/projects/index");?></li>
    <li><?php echo $html->link('View Project', "/projects/view/$projectId");?></li>
    <li><?php echo $html->link('Search Dataset', "/view/index/$dataset");?></li>
</ul>

<?php echo $html->css('search.css'); ?>
<?php 	
		#read session variables
		$query = $session->read($sessionQueryId);
		$searchFields = $session->read('searchFields');
		$field = $session->read('searchField');
		
?>
<h2><?php __("Search");?><span class="selected_library"><?php echo "$dataset ($projectName)"; ?></span><span id="spinner" style="display: none;"><?php echo $html->image('ajax-loader.gif', array('width'=>'25px')); ?></span></h2>

<div class="search-panel">
<a href="#" id="dialog_link" class="ui-state-default ui-corner-all"><span class="ui-icon ui-icon-newwin"></span>Help</a>
<fieldset>
<legend> </legend>

<?php echo $form->create('Search', array('url' => array('controller' => 'search', 'action' => 'index',$dataset))); ?>


<?php 
	echo('<div class="search-box">');
	echo $form->input("query", array('type'=>'text', 'value'=>$query,'label' => "Found <B>$numHits hits</b> in <b>$dataset</b> for"));
	echo('</div>');	
	
	echo $form->input('field',array('options' => $searchFields,'label' => "Select Search Field",'selected' =>$field,'div'=>'search-field-select-option'));
	echo $form->end("Search");

?>
	
</fieldset>
</div>
 

<?php echo $dialog->printSearch("dialog",$dataset) ?>	

<?php if($numHits>0) { 
	?>
	
	
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