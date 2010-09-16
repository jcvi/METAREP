<!----------------------------------------------------------
  File: forgot_password.ctp
  Description: Forgot Password Page
  
  Form to enter and confirm a new password.
  
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