<div class="reads form">
<?php echo $form->create('Read');?>
	<fieldset>
 		<legend><?php __('Edit Read');?></legend>
	<?php
		echo $form->input('id');
		echo $form->input('sequence');
		echo $form->input('checksum');
	?>
	</fieldset>
<?php echo $form->end('Submit');?>
</div>
<div class="actions">
	<ul>
		<li><?php echo $html->link(__('Delete', true), array('action'=>'delete', $form->value('Read.id')), null, sprintf(__('Are you sure you want to delete # %s?', true), $form->value('Read.id'))); ?></li>
		<li><?php echo $html->link(__('List Reads', true), array('action'=>'index'));?></li>
		<li><?php echo $html->link(__('List Peptides', true), array('controller'=> 'peptides', 'action'=>'index')); ?> </li>
		<li><?php echo $html->link(__('New Peptide', true), array('controller'=> 'peptides', 'action'=>'add')); ?> </li>
		<li><?php echo $html->link(__('List Rrnas', true), array('controller'=> 'rrnas', 'action'=>'index')); ?> </li>
		<li><?php echo $html->link(__('New Rrna', true), array('controller'=> 'rrnas', 'action'=>'add')); ?> </li>
		<li><?php echo $html->link(__('List Trnas', true), array('controller'=> 'trnas', 'action'=>'index')); ?> </li>
		<li><?php echo $html->link(__('New Trna', true), array('controller'=> 'trnas', 'action'=>'add')); ?> </li>
	</ul>
</div>
