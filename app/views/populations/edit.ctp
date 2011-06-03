<!----------------------------------------------------------
  
  File: edit.ctp
  Description: Edit Population Page
  
  Edit population description and population datasets.
  
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

<?php echo $html->css('population.css'); ?>
<?php echo $html->script(array('michael-multiselect-2a0569f/js/jquery-1.3.2.min.js')); ?>
<?php echo $html->script(array('michael-multiselect-2a0569f/js/jquery-ui-1.7.1.custom.min.js')); ?>
<?php echo $html->script(array('michael-multiselect-2a0569f/js/ui.multiselect.js')); ?>
<?php echo $html->css('ui.multiselect.css');   ?>

<?php

$selectedDatasets = array();

if($this->data['Library']) {
	foreach($this->data['Library'] as $libraryEntry) {
		array_push($selectedDatasets,$libraryEntry['id']);
	}
}

?>

<?php echo $form->create('Population');?>

	<h2><?php __('New Population');?></h2>
	
	<div class="population-input-panel">		
		<fieldset>
		<legend>Enter Population Information</legend>
			<?php
				echo $form->input('id');
				echo $form->input('project_id',array('type'=>'hidden'));		
				echo $form->input('name');
				echo $form->input('description',array('type' => 'textaerea'));
			?>		

		</fieldset>
	</div>
	
	<fieldset class="population-multi-select-panel">
		<legend>Select Population Datasets</legend>		
		    <select id="Library" class="multiselect" multiple="multiple" name="data[Library][Library][]">
		      
			<?php 
				foreach ($datasets as $id =>$name) {		
						
						if(count($datasets)>0 && in_array($id,$selectedDatasets)){						
						 	echo("<option value=\"{$id}\" selected=\"selected\">{$name}</option>");
						}
						else {
							echo("<option value=\"{$id}\">{$name}</option>");
						}
				}
			?>      			
		    </select>      
	</fieldset>
	
	<?php echo $form->end('Submit');?>
	
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
</script>