<div class="reads view">

<h2><?php __('blast_species Distribution');?></h2>
<BR>
<?php echo("<p><img src=\"http://chart.apis.google.com/chart?chco=4D89F9&chs=950x250&chd=t:".$stats[0][0]['count']/$sum.",".$stats[1][0]['count']/$sum.",".$stats[2][0]['count']/$sum.",".$stats[3][0]['count']/$sum.",".$stats[4][0]['count']/$sum.",".$stats[5][0]['count']/$sum.",".$stats[6][0]['count']/$sum.",".$stats[7][0]['count']/$sum.",".$stats[8][0]['count']/$sum.",".$stats[9][0]['count']/$sum.",".$stats[10][0]['count']/$sum."&cht=p&chl=".urlencode($stats[0]['f']['scientific_name'])."|". urlencode($stats[1]['f']['scientific_name'])."|". urlencode($stats[2]['f']['scientific_name'])."|". urlencode($stats[3]['f']['scientific_name'])."|". urlencode($stats[4]['f']['scientific_name'])."|". urlencode($stats[5]['f']['scientific_name'])."|". urlencode($stats[6]['f']['scientific_name'])."|". urlencode($stats[7]['f']['scientific_name'])."|". urlencode($stats[8]['f']['scientific_name'])."|". urlencode($stats[9]['f']['scientific_name'])."|". urlencode($stats[10]['f']['scientific_name'])."\"</p>"); ?>
<dl><?php $i = 0; $class = ' class="altrow"';?>
            <dt></dt>
        </dl>
</div>
<BR><BR>
<table cellpadding="0" cellspacing="0">
<tr>
	<th class="hist_count"><?php __('Count');?></th>
	<th class="hist_value"><?php __('blast_species');?></th>
	<th width=0%><?php __('Lineage');?></th>
</tr>
<?php
$i = 0;
foreach ($stats as $stat):
	$class = null;
	if ($i % 2 == 0) {
		$class = ' class="altrow"';
	}
?>
	<tr<?php echo $class;?>>
		<td >
			<?php echo $stats[$i][0]['count']; ?>
		</td>
		<td>
			<?php echo $stats[$i]['f']['scientific_name']; ?>
		</td>
		<td>
			<?php echo $stats[$i]['f']['lineage']; ?>
		</td>
	</tr>
<?php $i++;endforeach; ?>
</table>
</div>

