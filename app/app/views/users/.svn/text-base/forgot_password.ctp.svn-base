<!----------------------------------------------------------
  File: forgot_password.ctp
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
		
	}
	input {
		width:150%;
	} 
</style>

<div class="form">
<h2>Password Recovery</h2>
<fieldset>
	<legend>Enter Your Email</legend>
		<?php echo $form->create("User",array("action"=>"forgotPassword")) ?>
		<?php echo $form->text("email",array("size"=>"40")) ?>
		<?php echo $form->submit("Submit",array("class"=>"buttons")) ?>
		<?php echo $form->end() ?>
	</fieldset>
</div>