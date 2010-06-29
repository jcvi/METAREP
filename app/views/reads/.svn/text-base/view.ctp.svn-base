<div class="reads view">
<h2><?php  __('Read');?></h2>
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Id'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $read['Read']['id']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Sequence'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo ">".$read['Read']['id']."<BR>".wordwrap($read['Read']['sequence'],60, "<br>",true); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Checksum'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $read['Read']['checksum']; ?>
			&nbsp;
		</dd>
	</dl>
</div>
<div class="actions">
	<ul>
		<li><?php echo $html->link(__('List Reads', true), array('action'=>'index')); ?> </li>
		<li><?php echo $html->link(__('List Peptides', true), array('controller'=> 'peptides', 'action'=>'index')); ?> </li>
		<li><?php echo $html->link(__('List Rrnas', true), array('controller'=> 'rrnas', 'action'=>'index')); ?> </li>
		<li><?php echo $html->link(__('List Trnas', true), array('controller'=> 'trnas', 'action'=>'index')); ?> </li>
	</ul>
</div>

<div class="related">
	<h3><?php __('Related Rrnas');?></h3>
	<?php if (!empty($read['Rrna'])):?>
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php __('Id'); ?></th>
		<th><?php __('Read Id'); ?></th>
		<th><?php __('Sequence'); ?></th>
		<th><?php __('Checksum'); ?></th>
		<th><?php __('Type'); ?></th>
		<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
		$i = 0;
		foreach ($read['Rrna'] as $rrna):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
		?>
		<tr<?php echo $class;?>>
			<td><?php echo $rrna['id'];?></td>
			<td><?php echo $rrna['read_id'];?></td>
			<td><?php echo $rrna['sequence'];?></td>
			<td><?php echo $rrna['checksum'];?></td>
			<td><?php echo $rrna['type'];?></td>
			<td class="actions">
				<?php echo $html->link(__('View', true), array('controller'=> 'rrnas', 'action'=>'view', $rrna['id'])); ?>
				<?php echo $html->link(__('Edit', true), array('controller'=> 'rrnas', 'action'=>'edit', $rrna['id'])); ?>
				<?php echo $html->link(__('Delete', true), array('controller'=> 'rrnas', 'action'=>'delete', $rrna['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $rrna['id'])); ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
<?php endif; ?>
</div>
<div class="related">
	<h3><?php __('Related Trnas');?></h3>
	<?php if (!empty($read['Trna'])):?>
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php __('Id'); ?></th>
		<th><?php __('Read Id'); ?></th>
		<th><?php __('Sequence'); ?></th>
		<th><?php __('Checksum'); ?></th>
		<th><?php __('Type'); ?></th>
		<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
		$i = 0;
		foreach ($read['Trna'] as $trna):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
		?>
		<tr<?php echo $class;?>>
			<td><?php echo $trna['id'];?></td>
			<td><?php echo $trna['read_id'];?></td>
			<td><?php echo $trna['sequence'];?></td>
			<td><?php echo $trna['checksum'];?></td>
			<td><?php echo $trna['type'];?></td>
			<td class="actions">
				<?php echo $html->link(__('View', true), array('controller'=> 'trnas', 'action'=>'view', $trna['id'])); ?>
				<?php echo $html->link(__('Edit', true), array('controller'=> 'trnas', 'action'=>'edit', $trna['id'])); ?>
				<?php echo $html->link(__('Delete', true), array('controller'=> 'trnas', 'action'=>'delete', $trna['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $trna['id'])); ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
<?php endif; ?>
</div>
<div class="related">
	<h3><?php __('Related Peptides');?></h3>
	<?php if (!empty($read['Peptide'])):?>
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php __('Id'); ?></th>
		<th><?php __('Read Id'); ?></th>
		<th><?php __('Checksum'); ?></th>
		<th><?php __('Sequence'); ?></th>
		<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
		$i = 0;
		foreach ($read['Peptide'] as $peptide):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
		?>
		<tr<?php echo $class;?>>
			<td><?php echo $peptide['id'];?></td>
			<td><?php echo $peptide['read_id'];?></td>
			<td><?php echo $peptide['checksum'];?></td>
			<td><?php echo $peptide['sequence'];?></td>
			<td class="actions">
				<?php echo $html->link(__('View', true), array('controller'=> 'peptides', 'action'=>'view', $peptide['id'])); ?>
				<?php echo $html->link(__('Edit', true), array('controller'=> 'peptides', 'action'=>'edit', $peptide['id'])); ?>
				<?php echo $html->link(__('Delete', true), array('controller'=> 'peptides', 'action'=>'delete', $peptide['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $peptide['id'])); ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
<?php endif; ?>
</div>
