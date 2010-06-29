<div class="libraries form">
<h2><?php __('New Library');?></h2>
<?php echo $form->create('Library');?>
	<fieldset>
 		<legend></legend>
	<?php
		echo $form->input('name');
		echo $form->input('description',array('type' => 'textaerea'));
		echo $form->input('apis_database',array('type' => 'text'));
//		echo $form->input('reads_file_path',array('type' => 'text'));
//		echo $form->input('evidence_file_path',array('type' => 'text'));
//		echo $form->input('annotation_file_path',array('type' => 'text'));
//		echo $form->input('sqlite_database_path',array('type' => 'text'));
		echo $form->input('project_id');
	?>
	</fieldset>
<?php echo $form->end('Submit');?>

		</div>
