<!----------------------------------------------------------
  
  File: add.ctp
  Description: Add Population Page
  
  To get higher level summaries, users are able to create a
  population dataset by merging multiple existing datasets.
  The page featurs a text input form to enter name and description
  of the new population and a multi-dataset select box to select
  existing datasets to be merged.
  
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

<?php echo $html->css('population.css'); ?>
<?php echo $javascript->link(array('michael-multiselect-2a0569f/js/jquery-1.3.2.min.js')); ?>
<?php echo $javascript->link(array('michael-multiselect-2a0569f/js/jquery-ui-1.7.1.custom.min.js')); ?>
<?php echo $javascript->link(array('michael-multiselect-2a0569f/js/ui.multiselect.js')); ?>
<?php echo $javascript->link(array('prototype')); ?>
<?php echo $html->css('ui.multiselect.css'); ?>


	
<div id='testdiv'>

<ul id="breadcrumb">
 	<li><a href="/metarep/dashboard/index" title="Dashboard"><img src="/metarep/img/home.png" alt="Dashboard" class="home" /></a></li>
    <li><?php echo $html->link('List Projects', "/projects/index");?></li>
    <li><?php echo $html->link('View Project', "/projects/view/$projectId");?></li>
    <li><?php echo $html->link('New Population', "/populations/add/$projectId");?></li>
</ul>

<?php echo $form->create('Population');?>

	<h2><?php __('New Population');?><span id="spinner" style="display: none;"> - Indexing 
	<?php echo $html->image('ajax-loader.gif', array('width'=>'25px'));?></h2> 
 </span>
	
	<div class="population-input-panel">		
		<fieldset>
		<legend>Enter Population Information</legend>
			<?php
				echo $form->input('Population.project_id',array('type'=>'hidden', 'value' => $projectId));		
				echo $form->input('name');
				echo $form->input('description',array('type' => 'textaerea'));
				?>		

		</fieldset>
	</div>

	<fieldset class="population-multi-select-panel">
	
		<legend>Select Population Datasets</legend>		
		<div class="error-message"><?php if(isset($multiSelectErrorMessage)){ echo $multiSelectErrorMessage;}; ?></div>
		    <select id="Library" class="multiselect" multiple="multiple" name="data[Library][Library][]">
		      
			<?php 
				foreach ($datasets as $id =>$name) {		
						
						if(count($datasets)>0 && in_array($id,$datasets)){
						 	echo("<option value=\"{$id}\" selected=\"selected\">{$name}</option>");
						}
						else {
							echo("<option value=\"{$id}\">{$name}</option>");
						}
				}
			?>      			
		    </select>      
	</fieldset>
		
	<?php  echo $ajax->submit('Submit', array('url'=> array('controller'=>'populations', 'action'=>'add'), 'update' => 'testdiv', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Element.hide(\'population-input-panel\');Effect.Appear(\'population-input-panel\',{ duration: 1.0 })'));
			echo $form->end();
	?>
</div>
	
<script type="text/javascript">			
	jQuery('#dialog').dialog({
		autoOpen: false,
		width: 900,
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
	});
	  jQuery.ajax({ success: function(){
		  jQuery(".multiselect").multiselect();
	    }});  
</script>

 