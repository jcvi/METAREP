<!----------------------------------------------------------
  File: edit.ctp
  Description:

  Author: jgoll
  Date:   May 6, 2010
<!---------------------------------------------------------->

<ul id="breadcrumb">
  	<li><a href="/metarep/dashboard/index" title="Dashboard"><img src="/metarep/img/home.png" alt="Dashboard" class="home" /></a></li>
    <li><?php echo $html->link('List Projects', "/projects/index");?></li>
    <li><?php echo $html->link('View Project', "/projects/view/{$this->data['Library']['project_id']}");?></li>
    <li><?php echo $html->link('Edit Library', "/libraries/edit/{{$this->data['Library']['id']}}");?></li>
</ul>

<style type="text/css">
	.form
	{
		width:80%;
		float:left;
		padding: 0px;
		margin: 0px;
		
	}
	input {
		width:60%;
	} 

</style>

<div class="form">
<h2><?php  __('Edit Library');?><span class="selected_library"><?php echo "{$this->data['Library']['name']}"; ?></span></h2>
<?php echo $form->create('Library');?>
	<fieldset>
 		<legend></legend>
	<?php		
		#die("test".preg_match("/^[0-9]+°[0-9]+'[0-9]+\"[SN]$/","32°10'0\"N").":");	
		echo $form->input('id');
		echo $form->input('project_id');
		echo $form->input('description',array('type' => 'textaerea'));
		echo $form->input('apis_link',array('type' => 'text'));
	?>
	</fieldset>
		<fieldset>
 		<legend>Sample Meta Information</legend>
 		<?php
		echo $form->input('sample_date',array('type' => 'date'));
		echo $form->input('sample_depth',array('type' => 'text'),array('type' => 'text','size'=>'50','label' => 'Depth [m]') );
		echo $form->input('sample_altitude',array('type' => 'text'), array('type' => 'text','size'=>'50','label' => 'Altitude [m]'));
		echo $form->input('sample_latitude',array('type' => 'text','size'=>'50','label' => 'Sample Latitute [63&#176;35\'42"W]'));
		echo $form->input('sample_longitude',array('type' => 'text','size'=>'50','label' => 'Sample Longitude [32&#176;10\'0"N]'));
		echo $form->input('sample_filter', array( 'options' => array('3.0'=>'3.0','0.8' => '0.8','0.1' => '0.1'),'label' => 'Sample Filter Size', 'empty'=>'--Select Filter Size--','div'=>'comparator-test-select-option'));
	?>
	</fieldset>
<?php echo $form->end('Submit');?>
</div>
