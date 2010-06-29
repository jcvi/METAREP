<!----------------------------------------------------------
  File: edit.ctp
  Description:

  Author: jgoll
  Date:   Mar 31, 2010
<!---------------------------------------------------------->

<ul id="breadcrumb">
 	<li><a href="/metarep/dashboard/index" title="Dashboard"><img src="/metarep/img/home.png" alt="Dashboard" class="home" /></a></li>
    <li><?php echo $html->link('Change Account Information', "/logs/index");?></li>
</ul>

<style type="text/css">
	.form
	{
		width:40%;
		float:left;
		padding: 0px;
		margin: 0px;
		
	}
	input {
		width:150%;
	} 

</style>
<div class="form">
	<h2>Change Account Information</h2>
		<fieldset>
			<legend>Account Fields</legend>
			<?
				echo $form->create('User',array('action'=>'edit'));
				echo $form->input('id');
			
				$currentUser	=  Authsome::get();
				$currentUsername= $currentUser['User']['username'];	
					
				#only allow admins to change user permissions
				if($currentUsername === 'admin') { 
					echo $form->input('user_group_id',array('type'=>'select','options'=>$userGroups,'selected'=> $userGroupId,'empty'=>'--Select User--','label'=> false));
				}
				else {			
					#set hidden field
					echo $form->hidden('user_group_id',array($userGroupId));							
				}	
				
				#echo $form->input('user_group_id',array('type'=>'select','options'=>$userGroups));
				echo $form->input('username');
				echo $form->input('email');
				echo $form->input('first_name');
				echo $form->input('last_name');		
				echo $form->submit();
				echo $form->end();
			?>
		</fieldset>
</div>