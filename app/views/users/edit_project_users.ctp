<!----------------------------------------------------------
  File: edit_project_users.ctp
  Description:

  Author: jgoll
  Date:   Mar 31, 2010
<!---------------------------------------------------------->

<?php echo $html->css('population.css'); ?>
<?php echo $javascript->link(array('michael-multiselect-2a0569f/js/jquery-1.3.2.min.js')); ?>
<?php echo $javascript->link(array('michael-multiselect-2a0569f/js/jquery-ui-1.7.1.custom.min.js')); ?>
<?php echo $javascript->link(array('michael-multiselect-2a0569f/js/ui.multiselect.js')); ?>
<?php echo $javascript->link(array('prototype')); ?>
<?php echo $html->css('ui.multiselect.css'); 
?>

<div id='manage-project-users'>
<?php echo $form->create('User',array('action' => "editProjectUsers/$projectId"));
echo $form->input('Project.id',array('type'=>'hidden', 'value' => $projectId));		
#echo $form->input('data[Users][User][]',array('type'=>'hidden', 'value' => 3));	
?>

	<h2><?php __('Manage User Permissions'); ?><span class="selected_library"><?php echo $projectName; ?></span><span id="spinner" style="display: none;">
	
 	<?php echo $html->image('ajax-loader.gif');?></h2> 
	 </span>
	

	<fieldset class="population-multi-select-panel">
	
		<legend>Select Users</legend>		
		<div class="error-message"><?php if(isset($multiSelectErrorMessage)){ echo $multiSelectErrorMessage;}; ?></div>
		    <select id="User" class="multiselect" multiple="multiple" name="data[User][User][]">
		      
			<?php 		
				$currentUser	= Authsome::get();
				$currentUserId 	= $currentUser['User']['id'];	
			
				foreach ($users as $user) {							
					#do not show admin / current users / and other project users in multi-select box
					if($user['User']['username'] != 'admin' && $user['User']['id'] != $currentUserId && !$user['ProjectsUser']['is_admin'] ) {						
						$displayName = "{$user['User']['first_name']} {$user['User']['last_name']}"; 		
						$userId = $user['User']['id'];
						
						if(in_array($userId,$projectUsers)){												
							echo("<option value=\"$userId\" selected=\"selected\">$displayName</option>");
						}
						else {
							echo("<option value=\"$userId\">$displayName</option>");
						}
					}
				}
			?>      			
		    </select>      
	</fieldset>
		
	<?php 
		echo $form->submit("Submit");
		echo $form->end();
	?>
</div>

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