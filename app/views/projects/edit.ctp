<!----------------------------------------------------------
  
  File: edit.ctp
  Description: Edit Projects Page
  
  Lets you edit project information.	
	
  PHP versions 4 and 5

  METAREP : High-Performance Comparative Metagenomics Framework (http://www.jcvi.org/metarep)
  Copyright(c)  J. Craig Venter Institute (http://www.jcvi.org)

  Licensed under The MIT License
  Redistributions of files must retain the above copyright notice.

  @link http://www.jcvi.org/metarep METAREP Project
  @package metarep
  @version METAREP v 1.2.0
  @author Johannes Goll
  @lastmodified 2010-07-09
  @license http://www.opensource.org/licenses/mit-license.php The MIT License
  
<!---------------------------------------------------------->

<div class="projects-form">
<h2><?php __('Edit Project');?></h2>
<?php echo $form->create('Project'); ?>
	<fieldset>
 		<legend></legend>
	<?php 
		$currentUser	=  Authsome::get();	
		$userGroup  	= $currentUser['UserGroup']['name'];	
		$currentUserId 	= $currentUser['User']['id'];
			
		echo $form->input('id');
		
		#the METAREP admin can assign project admin permissions
		if($userGroup === ADMIN_USER_GROUP) { 
			echo $form->input('user_id',array('options'=> $userSelectArray,'selected'=> $projectUserId,'empty'=>'--Select User--','label'=> 'Project Admin'));		
		}
		else {			
			#set hidden field
			echo $form->hidden('user_id',array('value',$projectUserId));
		}		
		
		echo $form->input('name');
		echo $form->input('description',array('type' => 'textarea'));
		
		#the METAREP admin and the project admin can make a project publicy accessable
		if($userGroup === ADMIN_USER_GROUP || $currentUserId == $projectUserId) { 		
			echo $form->input('is_public',array('label' => 'Is Public Dataset'));		
		}
		
		echo $form->input('charge_code');
		echo $form->input('jira_link',array('type' => 'text'));
	?>
	</fieldset>
<?php echo $form->end('Submit');?>
</div>

