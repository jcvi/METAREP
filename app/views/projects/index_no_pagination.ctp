<!----------------------------------------------------------
  
  File: index_no_pagination.ctp
  Description: Project Index No Pagination Page
  
  List all projects in one long list instead of showing only
  a subsets with paging.
  
  PHP versions 4 and 5

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
	<?php 
	$currentUser 	= Authsome::get();
	$currentUserId 	= $currentUser['User']['id'];	    	        	
	$userGroup  	= $currentUser['UserGroup']['name'];	
	
	if($userGroup === ADMIN_USER_GROUP || $userGroup === INTERNAL_USER_GROUP) {
		echo('<th>');
			echo $paginator->sort('project_code');
		echo('</th>');
		echo('<th>');
			echo $paginator->sort('jira_link');
		echo('</th>');
	}?>
	
</tr>

	<?php

$i =0;

if(!empty($projects)) {	
	foreach ($projects as $project) {
		$class = null;
	
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
		echo("
		<tr $class>
		<td style=\"width:3%;text-align:right\">
			{$project['Project']['id']}
		</td>
		<td style=\"width:6%;text-align:center\">
			{$project['Project']['updated']}
		</td>		
		<td>
			{$project['Project']['name']}
		</td>
		<td class=\"actions\">");
			echo $html->link(__('View', true), array('action'=>'view', $project['Project']['id']));
		echo("	
		</td>
			<td style=\"width:8%;text-align:right\">");
			echo count($project['Population']);
		echo("		
		</td>	
		<td style=\"width:8%;text-align:right\">");
			echo count($project['Library']);
		echo("</td>");					
		if($userGroup === ADMIN_USER_GROUP || $userGroup === INTERNAL_USER_GROUP) {
			echo('<td style=\"width:10%;text-align:center\">');
				echo $project['Project']['charge_code'];
			echo('</td>');
			
			echo('<td>');
				echo $html->link($project['Project']['jira_link'], $project['Project']['jira_link'], array('target'=>'_blank')); 
			echo('</td>');
		} //end if
	echo('</tr>');
	} //end foreach
}//end if
?>	
</table>
</div>