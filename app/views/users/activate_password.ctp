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
		
	<?=$form->create("User",array("action"=>"activate_password")) ?>
	<?=$form->input('password')?>
	<?=$form->input('confirm_password',array('type'=>'password'))?>
	<?=$form->hidden('ident',array('value'=>$ident))?>
	<?=$form->hidden('activate',array('value'=>$activate))?>
	<?=$form->submit()?>
	<?=$form->end() ?>

	</div>
</fieldset>
</div>
