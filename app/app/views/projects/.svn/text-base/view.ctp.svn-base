<!----------------------------------------------------------
  File: view.ctp
  Description:

  Author: jgoll
  Date:   Apr 1, 2010
<!---------------------------------------------------------->

<ul id="breadcrumb">
  	<li><a href="/metarep/dashboard/index" title="Dashboard"><img src="/metarep/img/home.png" alt="Dashboard" class="home" /></a></li>
    <li><?php echo $html->link('List Projects', "/projects/index");?></li>
    <li><?php echo $html->link('View Project', "/projects/view/{$project['Project']['id']}");?></li>
</ul>

<style type="text/css">
	select {
		height: 20px;
		width: 150px;
		font-size:0.9em;
	}
   .download {  
   position:absolute;	
	width: 106px;
	left: 92%;
	top: 160px;
}	
</style>

<h2><?php  __('View Project');?><span class="selected_library"><?php echo "{$project['Project']['name']}"; ?></span></h2>
<?php #echo $html->div('download', $html->link($html->image("download-large.png",array("title" => "Download Project Information")), array('controller'=> 'projects','action'=>'download',$project['Project']['id']),array('escape' => false)));?>
<fieldset>
<legend>Project Information</legend>
	<dl><?php $i = 0; $class = ' class="altrow"';?>


		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Name'); ?></dt>	
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
				<?php echo $project['Project']['name']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Description'); ?></dt>	
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
				<?php echo $project['Project']['description']; ?>
			&nbsp;
		</dd>	
			
	</dl>
	
	<?php
		$currentUser 	= Authsome::get();
		$currentUserId 	= $currentUser['User']['id'];	    	        	
       	$userGroup  	= $currentUser['UserGroup']['name'];	
    ?>
	<?php if($currentUserId === $project['Project']['user_id'] || $userGroup == 'Admin'):?>
		<dl>
			<dt><?php echo $html->link(__('Add Population', true), array('controller'=>'populations','action'=>'add', $project['Project']['id'])); ?><dt><?php if($project['Project']['has_ftp']) {echo "<dd>".$html->link(__('Download All Libraries', true), array('controller'=>'view','action'=>'ftp', $project['Project']['id'],$project['Project']['id']."_all"))."</dd>";} ?>
		</dl>
	<?php endif;?>
</fieldset>	
</div>

<?php if (!empty($project['Population'])):?>
<div class="related">
	<fieldset>
		<legend>Project Populations</legend>
	
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php __('Updated'); ?></th>
		<th><?php __('#Peptides'); ?></th>
		<th><?php __('Name'); ?></th>
		<th><?php __('Description'); ?></th>
		<th ><?php __('Annotation Pipeline'); ?></th>	
		
		<?php if($currentUserId === $project['Project']['user_id'] || $userGroup == 'Admin'):?>
			<th class="actions"><?php __('Manage');?></th>
		<?php endif; ?>
		<th class="actions"><?php __('Analyze');?></th>
	</tr>
	<?php
		$i = 0;
		foreach ($project['Population'] as $population):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
		?>
		<tr<?php echo $class;?>>
		<td style="width:5%;text-align:center">	
			<?php echo $population['updated']; ?>		
		</td>
		<td style="width:4%;text-align:right">
			<?php  echo(number_format($this->requestAction("/search/count/".$population['name']))); ?>
		</td>		
		<td style="width:25%;text-align:left">
			<?php echo $population['name']; ?>
		</td>
		<td >
			<?php echo $population['description']; ?>
		</td>
		<td style="width:4%;text-align:center">
			<?php if($population['is_viral']){echo ('viral');} else{echo('prokaryotic');} ?>
		</td>			
		
		<?php if($currentUserId === $project['Project']['user_id'] || $userGroup == 'Admin'):?>
		<td class="actions" style="width:4%;text-align:right">				
			<?php #echo $html->link(__('Edit', true), array('controller'=>'populations','action'=>'edit', $population['id'])); ?>	
			<?php echo $html->link(__('View', true), array('controller'=>'populations','action'=>'view', $population['id'])); ?>		
			<?php echo $html->link(__('Delete', true), array('controller'=>'populations','action'=>'delete', $population['id']),array(),'Delete population?'); ?>			
		</td>
		<?php endif;?>
		
		<td class="actions" style="width:4%;text-align:center">
	
			<?php 	
			
					echo("<select onChange=\"goThere(this.options[this.selectedIndex].value)\" name=\"s1\">
					<option value=\"\" SELECTED>--Select Action--</option>
					<option value=\"/metarep/view/index/{$population['name']}\">View</option>
					<option value=\"/metarep/search/index/{$population['name']}\">Search</option>
					<option value=\"/metarep/compare/index/{$population['name']}\">Compare</option>
					<option value=\"/metarep/browse/blastTaxonomy/{$population['name']}\">Browse Taxonomy (Blast)</option>");
					if($population['has_apis']) {
						echo("<option value=\"/metarep/browse/apisTaxonomy/{$population['name']}\">Browse Taxonomy (Apis)</option>");
					}					
					echo("	
					<option value=\"/metarep/browse/pathways/{$population['name']}\">Browse Pathways</option>
					<option value=\"/metarep/browse/enzymes/{$population['name']}\">Browse Enzymes</option>
					<option value=\"/metarep/browse/geneOntology/{$population['name']}\">Browse Gene Ontology</option>
					</select>");?>	
			</td>	
		</tr>
	<?php endforeach; ?>
	</table>
</fieldset>	
</div>
<?php endif; ?>
<?php if (!empty($project['Library'])):?>
<div class="related">
	<fieldset>
		<legend>Project Libraries</legend>
	
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php __('Updated'); ?></th>
		<th><?php __('#Peptides'); ?></th>
		<th><?php __('Name'); ?></th>
		<th><?php __('Description'); ?></th>
		<th><?php __('Sample Id'); ?></th>
		<th><?php __('Sample Date'); ?></th>
		<th><?php __('Sample Location'); ?></th>
		<th><?php __('Sample Depth'); ?></th>
		<th><?php __('Sample Habitat'); ?></th>
		<th><?php __('Sample Filter'); ?></th>
		<th ><?php __('Annotation Pipeline'); ?></th>		
		
		<?php if($currentUserId === $project['Project']['user_id'] || $userGroup == 'Admin'):?>
			<th class="actions"><?php __('Manage');?></th>
		<?php endif;?>	
		<th class="actions"><?php __('Analyze');?></th>
	</tr>
	<?php
		$i = 0;
		foreach ($project['Library'] as $library):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
		?>
		<tr<?php echo $class;?>>
		
		
		<td style="width:5%;text-align:center">
			<?php echo $library['updated']; ?>		
		</td>
		<td style="width:4%;text-align:right">
			<?php  echo(number_format($this->requestAction("/search/count/".$library['name']))); ?>
		</td>				
		<td style="width:25%;text-align:left">
			<?php echo $library['name']; ?>
		</td>

		<td style="width:25%;text-align:left">
			<?php echo $library['description']; ?>
		</td>
		<td style="width:4%;text-align:center">
			<?php echo $library['sample_id']; ?>
		</td>		
		<td style="width:4%;text-align:center">
			<?php echo $library['sample_date']; ?>
		</td>	
		<td style="width:10%;text-align:center">
			<?php echo $library['sample_longitude']." ".$library['sample_latitude']; ?>
		</td>	
		<td style="width:4%;text-align:center">
			<?php echo $library['sample_depth']; ?>
		</td>		
		<td style="width:6%;text-align:center">
			<?php echo $library['sample_habitat']; ?>
		</td>				
		<td style="width:4%;text-align:center">
			<?php echo $library['sample_filter']?>
		</td>					
		<td style="width:4%;text-align:center">
			<?php if($library['is_viral']){echo ('viral');} else{echo('prokaryotic');} ?>
		</td>		
		<?php if($currentUserId === $project['Project']['user_id'] || $userGroup === 'Admin'):?>
		<td class="actions" style="width:4%;text-align:right">
			<?php echo $html->link(__('Edit', true), array('controller'=>'libraries','action'=>'edit', $library['id'])); ?>
			<?php echo $html->link(__('Delete', true), array('controller'=>'libraries','action'=>'delete', $library['id']),array(),'Delete library?'); ?>			
		</td>
		<?php endif;?>
		<td class="actions" style="width:4%;text-align:right">
			<?php 	echo("<select onChange=\"goThere(this.options[this.selectedIndex].value)\" name=\"s1\">");				
					echo("<option value=\"\" SELECTED>--Select Action--</option>");
					echo("<option value=\"/metarep/view/index/{$library['name']}\">View</option>
					<option value=\"/metarep/search/index/{$library['name']}\">Search</option>
					<option value=\"/metarep/compare/index/{$library['name']}\">Compare</option>
					<option value=\"/metarep/browse/blastTaxonomy/{$library['name']}\">Browse Taxonomy (Blast)</option>");
					if($library['apis_database']) {
						echo("<option value=\"/metarep/browse/apisTaxonomy/{$library['name']}\">Browse Taxonomy (Apis)</option>");
					}				
					echo("	
					<option value=\"/metarep/browse/pathways/{$library['name']}\">Browse Pathways</option>
					<option value=\"/metarep/browse/enzymes/{$library['name']}\">Browse Enzymes</option>
					<option value=\"/metarep/browse/geneOntology/{$library['name']}\">Browse Gene Ontology</option>");
					if($library['has_ftp']) {	
						echo("<option value=\"/metarep/view/ftp/{$project['Project']['id']}/{$library['name']}\">Download</option>");
					}
					if($library['apis_dataset']) {	
						echo("<optgroup label=\"External Links\">");									
						echo("<option value=\"/metarep/view/apis/{$project['Project']['id']}/".base64_encode("http://www.jcvi.org/apis/".$library['apis_database']."/".$library['apis_dataset'])."\">APIS</option>");
					}					
					echo("</select>");?>
		</td>						
	</tr>
	<?php endforeach; ?>
	</table>
	</fieldset>	
</div>
<?php endif; ?>
<script type="text/javascript">
function goThere(loc) {
	window.location.href=loc;
}
</script>