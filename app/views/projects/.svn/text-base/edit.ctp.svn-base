<div class="projects-form">
<h2><?php __('Edit Project');?></h2>
<?php echo $form->create('Project'); ?>
	<fieldset>
 		<legend></legend>
	<?php 
	
		echo $form->input('id');
		echo $form->input('name');
		
		$currentUser	=  Authsome::get();
	
		$currentUsername= $currentUser['User']['username'];	
		
		#only allow admins to change user permissions
		if($currentUsername === 'admin') { 
			echo $form->input('user_id',array('options'=> $userSelectArray,'selected'=> $projectUserId,'empty'=>'--Select User--','label'=> false));		
		}
		else {			
			#set hidden field
			echo $form->hidden('user_id',array('value',$projectUserId));
		}
		
		#echo $form->input('is_public',array('label' => 'Public Dataset'));		
		echo $form->input('description',array('type' => 'textarea'));
		echo $form->input('charge_code');
		echo $form->input('jira_link');
	?>
	</fieldset>
<?php echo $form->end('Submit');?>
</div>

