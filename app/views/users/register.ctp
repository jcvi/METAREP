<!----------------------------------------------------------
  
  File: register.ctp
  Description: User Registration Page
  
  The User Registration Page provides a form that allows new
  users to enter their account information.

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

