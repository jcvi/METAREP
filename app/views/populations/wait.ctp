<div class="libraries view">
<h2><?php  __('Creating Index');?><span class="selected_library">(this may take several minutes)</span><span id="spinner" style="display: true;">
 	<?php echo $html->image('ajax-loader.gif');?>
 </span></h2>
<h3><?php __("Population Details");?></h3> 
 <div class="libraries view">
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Project'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $population['Project']['name']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Library'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $population['Population']['name']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Description'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $population['Population']['description']; ?>
			&nbsp;
		</dd>		
	</dl>
</div>

<div class="related">
	<h3><?php __('Project Libraries');?></h3>
	<?php if (!empty($population['Library'])):?>
	<table cellpadding = "0" cellspacing = "0" style="width:600px;">
	<tr>
		<th><?php __('ID'); ?></th>
		<th><?php __('Updated'); ?></th>
		<th><?php __('Name'); ?></th>
		<th><?php __('Description'); ?></th>
		<th><?php __('#Peptides'); ?></th>


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
			
		<td>
			<?php echo $library['id']; ?>
		</td  align="center">
		<td>
			<?php echo $library['updated']; ?>		
		</td>
		<td>
			<?php echo $library['name']; ?>
		</td>
		<td>
			<?php echo $library['description']; ?>
		</td>
		<td >
			<?php  echo(number_format($this->requestAction("/search/count/".$library['name']))); ?>
		</td>		
		</tr>
	<?php endforeach; ?>
	</table>
<?php endif; ?>
</div>
</div>

