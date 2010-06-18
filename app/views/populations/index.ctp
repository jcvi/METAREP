<!----------------------------------------------------------
  File: index.ctp
  Description: population index

  Author: jgoll
  Date:   Mar 8, 2010
<!---------------------------------------------------------->

<ul id="breadcrumb">
  	<li><a href="/metarep/dashboard/index" title="Dashboard"><img src="/metarep/img/home.png" alt="Dashboard" class="home" /></a></li>
    <li><?php echo $html->link('List Populations', "/populations/index");?></li>
</ul>

<style type="text/css">
	select {
		height: 20px;
		width: 150px;
		font-size:0.9em;
	}
</style>


<div class="libraries index">
<h2><?php __('Populations'); ?></h2>
<p>
<?php
echo $paginator->counter(array(
'format' => __('Page %page% of %pages%, showing %current% records out of %count% total, starting on record %start%, ending on %end%', true)
));
?></p>
<table cellpadding="0" cellspacing="0" style="width:82em;">
<tr>
	<th><?php echo $paginator->sort('updated');?></th>
	<th class="actions"><?php __('#Peptides');?></th>
	<th><?php echo $paginator->sort('name');?></th>
	<th><?php echo $paginator->sort('description');?></th>
	<th><?php echo $paginator->sort('project_id');?></th>
	<th class="actions"><?php __('Analyze');?></th>
</tr>
<?php
$i = 0;
foreach ($population as $population):
	$class = null;
	if ($i++ % 2 == 0) {
		$class = ' class="altrow"';
	}
?>
	<tr<?php echo $class;?>>
		<td style="width:5%;text-align:center">
			<?php echo $population['Population']['updated']; ?>		
		</td>
		<td style="width:4%;text-align:right">
			<?php  echo(number_format($this->requestAction("/search/count/".$population['Population']['name']))); ?>
		</td>		
		<td style="width:10%;text-align:left">
			<?php echo $population['Population']['name']; ?>
		</td>
		<td style="width:20%;text-align:left">
			<?php echo $population['Population']['description']; ?>
		</td>		
		<td style="width:15%;text-align:left">
			<?php echo $population['Project']['name']; ?>
		</td>
		<td class="actions" style="width:4%;text-align:center">
	
			<?php 	echo("<select onChange=\"goThere(this.options[this.selectedIndex].value)\" name=\"s1\">
					<option value=\"\" SELECTED>--Select Action--</option>
					<option value=\"/metarep/view/index/{$population['Population']['name']}\">View</option>
					<option value=\"/metarep/search/index/{$population['Population']['name']}\">Search</option>
					<option value=\"/metarep/compare/index/{$population['Population']['name']}\">Compare</option>
					<option value=\"/metarep/browse/blastTaxonomy/{$population['Population']['name']}\">Browse Taxonomy (Blast)</option>");
					if($population['Population']['has_apis']) {
						echo("<option value=\"/metarep/browse/apisTaxonomy/{$population['Population']['name']}\">Browse Taxonomy (Apis)</option>");
					}
					echo("	
					<option value=\"/metarep/browse/pathways/{$population['Population']['name']}\">Browse Pathways</option>
					<option value=\"/metarep/browse/enzymes/{$population['Population']['name']}\">Browse Enzymes</option>
					<option value=\"/metarep/browse/geneOntology/{$population['Population']['name']}\">Browse Gene Ontology</option>
					</select>");?>	
			</td>				
	</tr>
<?php endforeach; ?>
</table>
</div>
<div class="paging">
	<?php echo $paginator->prev('<< '.__('previous', true), array(), null, array('class'=>'disabled'));?>
 | 	<?php echo $paginator->numbers();?>
	<?php echo $paginator->next(__('next', true).' >>', array(), null, array('class'=>'disabled'));?>
</div>

<script type="text/javascript">
function goThere(loc) {
	window.location.href=loc;
}
</script>