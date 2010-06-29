<!----------------------------------------------------------
  File: register.ctp
  Description:

  Author: jgoll
  Date:   Mar 24, 2010
<!---------------------------------------------------------->

<style type="text/css">
	.form
	{
		width:40%;
		float:left;
		padding: 0px;
		margin: 0px;
		font-weight: bold;
		
	}
	input {
		width:150%;
	} 

</style>
<div class="form quickform2 nobackground">
<h2>User Registration</h2>
	Existing User? <?=$html->link('Go back to log-in page','/dashboard')?>
	<fieldset>
		<legend>Required Fields</legend>					
			<?php echo $form->hidden("user_group_id",array("value"=>"2")); ?>
			<?php echo $form->create("User",array("action"=>"register")); ?>
			<?php echo $form->input("username") ?>
			<?php echo $form->input("email") ?>
			<?php echo $form->input("first_name") ?>
			<?php echo $form->input("last_name") ?>							
			<?php echo $form->input("password",array("value"=>null)) ?>
			<?php echo $form->input("confirm_password",array("type"=>"password")) ?>
			<?php echo $form->submit("Register") ?>
			<?php echo $form->end() ?>	
	</fieldset>
</div>

