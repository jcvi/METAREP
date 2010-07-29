<!----------------------------------------------------------
  
  File: index.ctp
  Description: Index Log Page
  
  Displays running, pending and completed JCVI Metagenomics 
  annotation pipeline runs. JCVI-only feature.
  
  PHP versions 4 and 5

  METAREP : High-Performance Comparative Metagenomics Framework (http://www.jcvi.org/metarep)
  Copyright(c)  J. Craig Venter Institute (http://www.jcvi.org)

  Licensed under The MIT License
  Redistributions of files must retain the above copyright notice.

  @link http://www.jcvi.org/metarep METAREP Project
  @package metarep
  @version METAREP v 1.0.1
  @author Johannes Goll
  @lastmodified 2010-07-09
  @license http://www.opensource.org/licenses/mit-license.php The MIT License
  
<!---------------------------------------------------------->

<ul id="breadcrumb">
 	<li><a href="/metarep/dashboard/index" title="Dashboard"><img src="/metarep/img/home.png" alt="Dashboard" class="home" /></a></li>
    <li><?php echo $html->link('Pipeline Progress Log', "/logs/index");?></li>
</ul>

<div class="libraries index">
<h2><?php __('Pipeline Progress Log'); ?></h2>
<p>
<?php
echo $paginator->counter(array(
'format' => __('Page %page% of %pages%, showing %current% records out of %count% total, starting on record %start%, ending on %end%', true)
));
?></p>
<table cellpadding="0" cellspacing="0">
<tr>
	<th><?php echo $paginator->sort('log_id');?></th>
	
	<th><?php echo $paginator->sort('Updated','ts_update');?></th>
	<th><?php echo $paginator->sort('user');?></th>
	<th><?php echo $paginator->sort('pipeline');?></th>
	<th><?php echo $paginator->sort('server');?></th>
	<th><?php echo $paginator->sort('input_file');?></th>
	<th><?php echo $paginator->sort('status');?></th>
</tr>
<?php
$i = 0;
foreach ($logs as $log):
	$class = null;
	if ($i++ % 2 == 0) {
		$class = ' class="altrow"';
	}
?>
	<tr<?php echo $class;?>>
		<td>
			<?php echo $log['Log']['log_id']; ?>
		</td  align="center">
		<td>
			<?php echo  $log['Log']['ts_update']; ?>
		</td>
		<td>
			<?php echo  $log['Log']['user']; ?>
		</td>		
		<td>
			<?php echo  $log['Log']['pipeline']; ?>
		</td>	
		<td>
			<?php echo  $log['Log']['server']; ?>
		</td>			
		<td>
			<?php echo  $log['Log']['input_file']; ?>
		</td>		
		<td>
			<?php echo  $log['Log']['status']; ?>
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

