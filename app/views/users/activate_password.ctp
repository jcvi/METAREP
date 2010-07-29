<!----------------------------------------------------------
  
  File: activate_password.ctp
  Description: Activate a new password. Used when users forgot
  their password.

  METAREP : High-Performance Comparative Metagenomics Framework (http://www.jcvi.org/metarep)
  Copyright(c)  J. Craig Venter Institute (http://www.jcvi.org)

  Licensed under The MIT License
  Redistributions of files must retain the above copyright notice.

  @link http://www.jcvi.org/metarep METAREP Project
  @package metarep
  @version METAREP v 1.0.1
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
<h2>Ativate New Password</h2>
<fieldset>
<legend>Enter Password</legend>

	<div class="quickform2 nobackground">
		
	<?=$form->create("User",array("action"=>"activatePassword")) ?>
	<?=$form->input('password')?>
	<?=$form->input('confirm_password',array('type'=>'password'))?>
	<?=$form->hidden('ident',array('value'=>$ident))?>
	<?=$form->hidden('activate',array('value'=>$activate))?>
	<?=$form->submit()?>
	<?=$form->end() ?>

	</div>
</fieldset>
</div>
