<!----------------------------------------------------------
  File: index_no_pagination.ctp
  Description:

  Author: jgoll
  Date:   Mar 31, 2010
<!---------------------------------------------------------->
<ul id="breadcrumb">
  	<li><a href="/metarep/dashboard/index" title="Dashboard"><img src="/metarep/img/home.png" alt="Dashboard" class="home" /></a></li>
    <li><?php echo $html->link('List Projects', "/projects/index");?></li>
</ul>

<div class="projects index">
<h2><?php __('Projects');?></h2>
<p>
</p>
<table cellpadding="0" cellspacing="0" style="width:82em;">
<tr>
	<th>Id</th>
	<th>Updated</th>
	<th>Name</th>
	<th class="actions">Action</th>
	<th>#Populations</th>
	<th>#Libraries</th>
	<th>Project Code</th>
	<th>Jira Link</th>
	
</tr>
<?php
$i = 0;
foreach ($projects as $project):
	$class = null;
	
	if ($i++ % 2 == 0) {
		$class = ' class="altrow"';
	}
?>
	<tr<?php echo $class;?>>
		<td>
			<?php echo $project['Project']['id']; ?>
		</td>
		<td>
			<?php echo $project['Project']['updated']; ?>
		</td>		
		<td>
			<?php echo  $project['Project']['name']; ?>
		</td>
		<td class="actions">
			<?php echo $html->link(__('View', true), array('action'=>'view', $project['Project']['id'])); ?>
		</td>
		<td>
			<?php echo  count($project['Population']); ?>
		</td>	
		<td>
			<?php echo  count($project['Library']); ?>
		</td>				
		<td>
			<?php echo $project['Project']['charge_code']; ?>
		</td>
		<td>
			<?php echo $html->link($project['Project']['jira_link'], $project['Project']['jira_link'], array('target'=>'_blank')); ?>
		</td>
	</tr>
<?php endforeach; ?>
</table>
</div>