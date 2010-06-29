<!----------------------------------------------------------
  File: change_password.ctp
  Description:

  Author: jgoll
  Date:   May 31, 2010
<!---------------------------------------------------------->

<ul id="breadcrumb">
 	<li><a href="/metarep/dashboard/index" title="Dashboard"><img src="/metarep/img/home.png" alt="Dashboard" class="home" /></a></li>
    <li><?php echo $html->link('Change Password', "/logs/index");?></li>
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

<h2>Change Password</h2>
<fieldset>
<legend>Enter New Password</legend>
	<?=$form->create("User",array("action"=>"change_password")) ?>
	<?=$form->input('password')?>
	<?=$form->input('confirm_password',array('type'=>'password'))?>
	<?=$form->hidden('id')?>
	<?=$form->submit()?>
	<?=$form->end() ?>
</fieldset>
</div>