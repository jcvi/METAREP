<!----------------------------------------------------------
  
  File: change_password.ctp
  Description: Change password form.

  METAREP : High-Performance Comparative Metagenomics Framework (http://www.jcvi.org/metarep)
  Copyright(c)  J. Craig Venter Institute (http://www.jcvi.org)

  Licensed under The MIT License
  Redistributions of files must retain the above copyright notice.

  @link http://www.jcvi.org/metarep METAREP Project
  @package metarep
  @version METAREP v 1.4.0
  @author Johannes Goll
  @lastmodified 2010-07-09
  @license http://www.opensource.org/licenses/mit-license.php The MIT License
  
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
	<?=$form->create("User",array("action"=>"changePassword")) ?>
	<?=$form->input('password')?>
	<?=$form->input('confirm_password',array('type'=>'password'))?>
	<?=$form->hidden('id')?>
	<?=$form->submit()?>
	<?=$form->end() ?>
</fieldset>
</div>