<!----------------------------------------------------------
  
  File: view.ctp
  Description: View Project Page
  
  The View Project Page displays project information, project
  populations and libraries.

  PHP versions 4 and 5

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
    <li><?php echo $html->link('Projects', "/projects/index");?></li>
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
<?php
if(isset($ftpLink)) {
	echo("<p><iframe src=\"$ftpLink\" height=\"1px\" width=\"1px\" frameborder=\"0\" align=\"center\" scrolling=\"no\"
>[Your browser does <em>not</em> support <code>iframe</code>,
or has been configured not to display inline frames.]</iframe></p>");
};
?>

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
    <?php 
    
    ## display project options (create pppulation | download all datasets) 
    echo("<dl><dt>Options</dt><dd>"); 
    
    if($currentUserId == $project['Project']['user_id'] || $userGroup === ADMIN_USER_GROUP ) {		
			if($currentUserId === $project['Project']['user_id'] || $userGroup === ADMIN_USER_GROUP) {
				echo $html->link(__('Add Population', true), array('controller'=>'populations','action'=>'add', $project['Project']['id'])); 
				echo('&nbsp;|');
			}
			echo $html->link(__('Import Library', true), array('controller'=>'import','action'=>'index', $project['Project']['id'])); 
			echo('&nbsp;|');
			if($project['Project']['has_ftp']) {				
				echo $html->link(__('Download All Libraries', true), 
				array('controller'=>'projects','action'=>'ftp', $project['Project']['id'],
				$project['Project']['id']."_all"));} 
				echo('&nbsp;|');							
    }
	echo $html->link(__('Refresh', true), 
	array('controller'=>'projects','action'=>'refresh', $project['Project']['id'])); 	
	echo "</dd></dl>";    
    ?>
</fieldset>	
</div>

<?php if (!empty($project['Population'])): #debug($project);?>
<div class="related">
	<fieldset>
		<legend>Project Populations</legend>
	
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php __('Updated'); ?></th>
		<th><?php __('#Entries'); ?></th>
		<th><?php __('#Libraries'); ?></th>
		<th><?php __('Name'); ?></th>
		<th><?php __('Description'); ?></th>
		<th ><?php __('Annotation Pipeline'); ?></th>	
		
		<?php if($currentUserId == $project['Project']['user_id'] || $userGroup === ADMIN_USER_GROUP):?>
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
			<?php  echo $population['count'];; ?>
		</td>		
		<td style="width:4%;text-align:right">
			<?php  echo $population['libraryCount'];; ?>
		</td>	
		<td style="width:20%;text-align:left">
			<?php echo $population['name']; ?>
		</td>			
		<td >
			<?php echo $population['description']; ?>
		</td>
		<td style="width:4%;text-align:center">
			<?php echo $population['pipeline'] ?>
		</td>			
		
		<?php if($currentUserId == $project['Project']['user_id'] || $userGroup === ADMIN_USER_GROUP):?>
		<td class="actions" style="width:4%;text-align:right">				
			<?php #echo $html->link(__('Edit', true), array('controller'=>'populations','action'=>'edit', $population['id'])); ?>	
			<?php echo $html->link(__('View', true), array('controller'=>'populations','action'=>'view', $population['id'])); ?>		
			<?php echo $html->link(__('Delete', true), array('controller'=>'populations','action'=>'delete', $population['id']),array(),'Delete population?'); ?>			
		</td>
		<?php endif;?>
		
		<td class="actions" style="width:4%;text-align:center">
	
			<?php 
			
					#debug($population);
					echo("<select onChange=\"goThere(this.options[this.selectedIndex].value)\" name=\"s1\">
					<option value=\"\" SELECTED>--Select Action--</option>
					<option value=\"/metarep/view/index/{$population['name']}\">View</option>
					<option value=\"/metarep/search/index/{$population['name']}\">Search</option>
					<option value=\"/metarep/compare/index/{$population['name']}\">Compare</option>
					<option value=\"/metarep/browse/blastTaxonomy/{$population['name']}\">Browse Taxonomy (Blast)</option>");
					if($population['has_apis']) {
						echo("<option value=\"/metarep/browse/apisTaxonomy/{$population['name']}\">Browse Taxonomy (Apis)</option>");
					}	
													
					echo("<option value=\"/metarep/browse/keggPathwaysEc/{$population['name']}\">Browse Kegg Pathways (EC)</option>");
					if($population['pipeline'] === PIPELINE_HUMANN || $population['has_ko'] ) {
						echo("<option value=\"/metarep/browse/keggPathwaysKo/{$population['name']}\">Browse Kegg Pathways (KO)</option>");
					}
					echo("<option value=\"/metarep/browse/metacycPathways/{$population['name']}\">Browse Metacyc Pathways (EC)</option>
					<option value=\"/metarep/browse/enzymes/{$population['name']}\">Browse Enzymes</option>
					<option value=\"/metarep/browse/geneOntology/{$population['name']}\">Browse Gene Ontology</option>");
					
					if($population['has_sequence']) {
						echo("<option value=\"/metarep/blast/index/{$population['name']}\">Blast Sequence</option>");
					}
										
					echo("</select>");?>	
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
		<th><?php __('#Entries'); ?></th>
		<th><?php __('Name'); ?></th>
		<th><?php __('Label'); ?></th>
		<th><?php __('Description'); ?></th>
		<th><?php __('Sample Id'); ?></th>
		<th><?php __('Sample Date'); ?></th>
		<th><?php __('Sample Location'); ?></th>
		<th><?php __('Sample Depth'); ?></th>
		<th><?php __('Sample Habitat'); ?></th>
		<th><?php __('Sample Filter'); ?></th>
		<th ><?php __('Annotation Pipeline'); ?></th>		
		
		<?php if($currentUserId == $project['Project']['user_id'] || $userGroup === ADMIN_USER_GROUP):?>
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
			<?php echo $library['count'];  ?>
		</td>				
		<td style="width:20%;text-align:left">
			<?php echo $library['name']; ?>
		</td>
		<td style="width:10%;text-align:left">
			<?php echo $library['label']; ?>
		</td>
		<td style="width:20%;text-align:left">
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
			<?php echo $library['pipeline'] ?>
		</td>		
		<?php if($currentUserId == $project['Project']['user_id'] || $userGroup === ADMIN_USER_GROUP):?>
		<td class="actions" style="width:4%;text-align:right">
			<?php if($currentUserId == $project['Project']['user_id'] || $userGroup === ADMIN_USER_GROUP){echo $html->link(__('Edit', true), array('controller'=>'libraries','action'=>'edit', $library['id']));} ?>
			<?php if($userGroup === ADMIN_USER_GROUP){echo $html->link(__('Delete', true), array('controller'=>'libraries','action'=>'delete', $library['id']),array(),'Delete library?');} ?>			
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
					if($library['pipeline'] === PIPELINE_HUMANN || $library['has_ko'] ) {
						echo("<option value=\"/metarep/browse/keggPathwaysKo/{$library['name']}\">Browse Kegg Pathways (KO)</option>");
					}	
					
					echo("<option value=\"/metarep/browse/keggPathwaysEc/{$library['name']}\">Browse Kegg Pathways (EC)</option>");
							
					echo("	
					<option value=\"/metarep/browse/metacycPathways/{$library['name']}\">Browse Metacyc Pathways (EC)</option>
					<option value=\"/metarep/browse/enzymes/{$library['name']}\">Browse Enzymes</option>
					<option value=\"/metarep/browse/geneOntology/{$library['name']}\">Browse Gene Ontology</option>");
					if($library['has_sequence']) {	
						echo("<option value=\"/metarep/blast/index/{$library['name']}\">Blast Sequence</option>");
					}					
					if($library['has_ftp']) {	
						echo("<option value=\"/metarep/projects/ftp/{$project['Project']['id']}/{$library['name']}\">Download</option>");
					}
					if(!empty($library['apis_database']) && !empty($library['apis_dataset']) && JCVI_INSTALLATION) {	
						echo("<optgroup label=\"External Links\">");									
						echo("<option value=\"/metarep/iframe/apis/{$project['Project']['id']}/".base64_encode("http://apis-dev.jcvi.org/apis/".$library['apis_database']."/".$library['apis_dataset'])."\">APIS</option>");
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