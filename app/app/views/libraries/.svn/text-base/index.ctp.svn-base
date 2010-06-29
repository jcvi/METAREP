<!----------------------------------------------------------
  File: index.ctp
  Description: libraries index

  Author: jgoll
  Date:   Mar 8, 2010
<!---------------------------------------------------------->

<div class="libraries index">
<h2><?php __('Libraries'); ?></h2>
<p>
<?php
echo $paginator->counter(array(
'format' => __('Page %page% of %pages%, showing %current% records out of %count% total, starting on record %start%, ending on %end%', true)
));
?></p>
<table cellpadding="0" cellspacing="0">
<tr>
	<th><?php echo $paginator->sort('id');?></th>
	<th><?php echo $paginator->sort('updated');?></th>
	<th><?php echo $paginator->sort('name');?></th>
	<th><?php echo $paginator->sort('project_id');?></th>
	<th><?php echo $paginator->sort('description');?></th>
	<th class="actions"><?php __('#Peptides');?></th>
	<th class="actions"><?php __('Manage');?></th>
	<th class="actions"><?php __('Analyze');?></th>
</tr>
<?php
$i = 0;
foreach ($libraries as $library):
	$class = null;
	if ($i++ % 2 == 0) {
		$class = ' class="altrow"';
	}
?>
	<tr<?php echo $class;?>>
		<td>
			<?php echo $library['Library']['id']; ?>		
		</td>
		<td>
			<?php echo $library['Library']['updated']; ?>		
		</td>
		<td>
			<?php echo $library['Library']['name']; ?>
		</td>
		<td>
			<?php echo $library['Project']['name']; ?>
		</td>
		<td>
			<?php echo $library['Library']['description']; ?>
		</td>		
		<td >
			<?php  echo(number_format($this->requestAction("/search/count/".$library['Library']['name']))); ?>
		</td>

		<td class="actions">
			<?php echo $html->link(__('Edit', true), array('action'=>'edit', $library['Library']['id'])); ?>
		</td>
		
	
		<td class="actions">
			<?php echo $html->link(__('View', true), array('controller'=>'view',$library['Library']['name'])); ?>
			<?php echo $html->link(__('Search', true), array('controller'=>'search',$library['Library']['name'])); ?>
			<?php echo $html->link(__('Compare', true), array('controller'=>'compare', $library['Library']['name'])); ?>
			<?php if(!empty($library['Library']['apis_database'])) {echo $html->link(__('Apis Taxonomy', true), array('controller'=>'browse','action'=>'apisTaxonomy', $library['Library']['name']));} ?>
			<?php echo $html->link(__('Blast Taxonomy', true), array('controller'=>'browse','action'=>'blastTaxonomy', $library['Library']['name'])); ?>
			<?php echo $html->link(__('Enzymes', true), array('controller'=>'browse','action'=>'enzymes', $library['Library']['name'])); ?>
			<?php echo $html->link(__('Gene Ontology', true), array('controller'=>'browse','action'=>'geneOntology', $library['Library']['name'])); ?>
		</td>
	</tr>
<?php endforeach; ?>
</table>
</div>
<div class="paging">	
	<?php echo $paginator->prev('< '.__('previous', true), array(), null, array('class'=>'disabled'));?>
 | 	<?php echo $paginator->numbers();?>
	<?php echo $paginator->next(__('next', true).' >>', array(), null, array('class'=>'disabled'));?>
</div>

