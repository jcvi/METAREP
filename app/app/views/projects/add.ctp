<div class="projects form">
<h2><?php __('New Project');?></h2>
<?php echo $form->create('Project');?>
	<fieldset>
 		<legend></legend>
	<?php
		echo $form->input('name');
		echo $form->input('user_id',array('options'=> $userSelectArray,'selected'=> $projectUserId,'empty'=>'--Select User--','label'=> false));		
		echo $form->input('description',array('type' => 'textarea'));
		echo $form->input('charge_code');
		echo $form->input('jira_link',array('type' => 'text'));
	?>
	</fieldset>
<?php echo $form->end('Submit');?>
</div>
