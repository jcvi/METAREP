<!----------------------------------------------------------
  File: view.ctp
  Description:

  Author: jgoll
  Date:   May 27, 2010
<!---------------------------------------------------------->

<ul id="breadcrumb">
 	<li><a href="/metarep/dashboard/index" title="Dashboard"><img src="/metarep/img/home.png" alt="Dashboard" class="home" /></a></li>
    <li><?php echo $html->link('List Projects', "/projects/index");?></li>
    <li><?php echo $html->link('View Project', "/projects/view/{$population['Population']['project_id']}");?></li>
    <li><?php echo $html->link('View Population', "/populations/view/{$population['Population']['id']}");?></li>
</ul>

<style type="text/css">
	select {
		height: 20px;
		width: 150px;
		font-size:0.9em;
	}
</style>

<div class="libraries view">
<h2><?php  __('Population'); ?><span class="selected_library"><?php echo "{$population['Population']['name']} </span> "; if(isset($status)){echo $status;} ;?></span></h2></h2>
	<fieldset>
		<legend>Population Information</legend>
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Created'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $population['Population']['created']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Description'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $population['Population']['description']; ?>
			&nbsp;
		</dd>		
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Project'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $population['Project']['name']; ?>
			&nbsp;
		</dd>		
	</dl>
	</fieldset>
</div>

<?php if (!empty($population['Library'])):?>
<div class="related">
	<fieldset>
		<legend>Population Libraries</legend>
	
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
		<th class="actions"><?php __('Analyze');?></th>
	</tr>
	<?php
		$i = 0;
		foreach ($population['Library'] as $library):
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
//					if($library['apis_link'] || $library['has_ftp']) {
//						echo("<optgroup label=\"External Links\">");			
//					}
//					if($library['apis_link']) {
//								
//						echo("<option value=\"/metarep/view/apis/".base64_encode($library['apis_link'])."\">APIS</option>");
//					}	
					if($library['has_ftp']) {	
						echo("<option value=\"/metarep/view/ftp/{$population['Project']['id']}/{$library['name']}\">Download</option>");
					}					
					echo("</select>");?>
		</td>						
	</tr>
	<?php endforeach; ?>
	</table>
	</fieldset>	
</div>
<?php endif; ?>