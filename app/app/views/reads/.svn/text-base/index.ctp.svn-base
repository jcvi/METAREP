<div class="reads index">
<h2><?php __('Reads');?></h2>
<p>
<?php
echo $paginator->counter(array(
'format' => __('Page %page% of %pages%, showing %current% records out of %count% total, starting on record %start%, ending on %end%', true)
));
?></p>
<table cellpadding="0" cellspacing="0">
<tr>
	<th><?php echo $paginator->sort('id');?></th>
	<th><?php echo $paginator->sort('sequence');?></th>
	<th><?php echo $paginator->sort('checksum');?></th>
	<th class="actions"><?php __('Actions');?></th>
</tr>
<?php
$i = 0;
foreach ($reads as $read):
	$class = null;
	if ($i++ % 2 == 0) {
		$class = ' class="altrow"';
	}
?>
	<tr<?php echo $class;?>>
		<td>
			<?php echo $read['Read']['id']; ?>
		</td>
		<td>
			<?php echo $read['Read']['sequence']; ?>
		</td>
		<td>
			<?php echo $read['Read']['checksum']; ?>
		</td>
		<td class="actions">
			<?php echo $html->link(__('View', true), array('action'=>'view', $read['Read']['id'])); ?>
			<?php echo $html->link(__('Edit', true), array('action'=>'edit', $read['Read']['id'])); ?>
			<?php echo $html->link(__('Delete', true), array('action'=>'delete', $read['Read']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $read['Read']['id'])); ?>
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
<div class="actions">
	<ul>
		<li><?php echo $html->link(__('New Read', true), array('action'=>'add')); ?></li>
		<li><?php echo $html->link(__('List Peptides', true), array('controller'=> 'peptides', 'action'=>'index')); ?> </li>
		<li><?php echo $html->link(__('New Peptide', true), array('controller'=> 'peptides', 'action'=>'add')); ?> </li>
		<li><?php echo $html->link(__('List Rrnas', true), array('controller'=> 'rrnas', 'action'=>'index')); ?> </li>
		<li><?php echo $html->link(__('New Rrna', true), array('controller'=> 'rrnas', 'action'=>'add')); ?> </li>
		<li><?php echo $html->link(__('List Trnas', true), array('controller'=> 'trnas', 'action'=>'index')); ?> </li>
		<li><?php echo $html->link(__('New Trna', true), array('controller'=> 'trnas', 'action'=>'add')); ?> </li>
	</ul>
</div>
