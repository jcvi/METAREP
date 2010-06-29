<!----------------------------------------------------------
  File: index.ctp
  Description: View Index Page.

  Author: jgoll
  Date:   Mar 18, 2010
<!---------------------------------------------------------->

<?php echo $html->css('view.css'); ?>
<div class="view-panel">

<ul id="breadcrumb">
 	<li><a href="/metarep/dashboard/index" title="Dashboard"><img src="/metarep/img/home.png" alt="Dashboard" class="home" /></a></li>
    <li><?php echo $html->link('List Projects', "/projects/index");?></li>
    <li><?php echo $html->link('View Project', "/projects/view/$projectId");?></li>
    <li><?php echo $html->link('View Dataset', "/view/index/$dataset");?></li>
</ul>

<h2><?php __("View"); ?><span class="selected_library"><?php echo "$dataset ($projectName)"; ?></span>
<span id="spinner" style="display: none;">
 	
</span></h2>
<div id="view-tabs">

<?php

$optionalDatatypes	= $session->read('optionalDatatypes');

echo("
	<ul>
		<li><a href=\"#view-data-tab\">Data</a></li>
		<li>".$ajax->link('<span>Species (Blast)</span>',array('action'=>'facet',$dataset,'blast_species'), array('update' => 'view-facet-panel', 'title' => 'view-facet-panel','tooltip'=>'View Blast Species Summary','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'view-facet-panel\',{ duration: 1.2 })', 'before' => 'Element.hide(\'view-facet-panel\')'), null, null, false)."</li>
		<li>".$ajax->link('<span>Gene Ontology</span>',array('action'=>'facet',$dataset,'go_id'), array('update' => 'view-facet-panel', 'title' => 'view-facet-panel','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'view-facet-panel\',{ duration: 1.2 })', 'before' => 'Element.hide(\'view-facet-panel\')'), null, null, false)."</li>
		<li>".$ajax->link('<span>Enzymes</span>',array('action'=>'facet',$dataset,'ec_id'), array('update' => 'view-facet-panel', 'title' => 'view-facet-panel','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'view-facet-panel\',{ duration: 1.2 })', 'before' => 'Element.hide(\'view-facet-panel\')'), null, null, false)."</li>
		<li>".$ajax->link('<span>HMMs</span>',array('action'=>'facet',$dataset,'hmm_id'), array('update' => 'view-facet-panel', 'title' => 'view-facet-panel','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'view-facet-panel\',{ duration: 1.2 })', 'before' => 'Element.hide(\'view-facet-panel\')'), null, null, false)."</li>
		<li>".$ajax->link('<span>Pathways</span>',array('action'=>'pathways',$dataset,'pathway_id'), array('update' => 'view-facet-panel', 'title' => 'view-facet-panel','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'view-facet-panel\',{ duration: 1.2 })', 'before' => 'Element.hide(\'view-facet-panel\')'), null, null, false)."</li>
		<li>".$ajax->link('<span>Common Names</span>',array('action'=>'facet',$dataset,'com_name'), array('update' => 'view-facet-panel', 'title' => 'view-facet-panel','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'view-facet-panel\',{ duration: 1.2 })', 'before' => 'Element.hide(\'view-facet-panel\')'),null, null, false)."</li>");
		
		#handle optional data types
		if($optionalDatatypes['clusters']) {
			echo("<li>".$ajax->link('<span>Core Clusters</span>',array('action'=>'facet',$dataset,'cluster_id','CAM_CR'), array('update' => 'view-facet-panel', 'title' => 'view-facet-panel','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'view-facet-panel\',{ duration: 1.2 })', 'before' => 'Element.hide(\'view-facet-panel\')'), null, null, false)."</li>");
			echo("<li>".$ajax->link('<span>Final Clusters</span>',array('action'=>'facet',$dataset,'cluster_id','CAM_CL'), array('update' => 'view-facet-panel', 'title' => 'view-facet-panel','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'view-facet-panel\',{ duration: 1.2 })', 'before' => 'Element.hide(\'view-facet-panel\')'), null, null, false)."</li>");			
		}		
		if($optionalDatatypes['viral']) {
			echo("<li>".$ajax->link('<span>Environmental Libraries</span>',array('action'=>'facet',$dataset,'env_lib'), array('update' => 'view-facet-panel', 'title' => 'view-facet-panel','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'view-facet-panel\',{ duration: 1.2 })', 'before' => 'Element.hide(\'view-facet-panel\')'), null, null, false)."</li>");
		}		
		if($optionalDatatypes['population']) {
			echo("<li>".$ajax->link('<span>Libraries</span>',array('action'=>'facet',$dataset,'library_id'), array('update' => 'view-facet-panel', 'title' => 'view-facet-panel','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'view-facet-panel\',{ duration: 1.2 })', 'before' => 'Element.hide(\'view-facet-panel\')'),null, null, false)."</li>");
		}
		if($optionalDatatypes['filter']) {			
			echo("<li>".$ajax->link(__('Filter', true),array('action'=>'facet',$dataset,'filter'), array('update' => 'view-facet-panel', 'title' => 'view-facet-panel','loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'view-facet-panel\',{ duration: 1.2 })', 'before' => 'Element.hide(\'view-facet-panel\')'))."</li>");
		}
		echo("</ul><div id=\"view-data-tab\">");?>
		
		<?php echo($luceneResultPaginator->addPageInformation($page,$numHits,""));?>
		
		<table cellpadding="0" cellspacing="0">
		
		<tr>	
			<th>Peptide Id</th>
			<th>Common Name</th>
			<th>Common Name Source</th>
			<th>Blast Species</th>
			<th>Blast E-Value</th>
			<th>Go Id</th>
			<th>Go Source</th>
			<th>Ec Id</th>
			<th>Ec Source</th>
			<th>HMM</th>
		</tr>
			
		<?php
		
		function printMultiValue($value){
			if(is_array($value)) {
				return implode('<BR>',$value);
			}
			else {
				return $value;
			}
		}
		
		$i = 0;
		
		foreach ( $hits as $hit ) {	
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
			
			echo "<tr  $class>";
			echo "<td>".$hit->peptide_id."</td>";
			echo "<td>".printMultiValue($hit->com_name)."</td>";
			echo "<td>".printMultiValue($hit->com_name_src)."</td>";
			echo "<td>".printMultiValue($hit->blast_species)."</td>";
			echo "<td>".$hit->blast_evalue."</td>";	
			echo "<td>".printMultiValue($hit->go_id)."</td>";
			echo "<td>".printMultiValue($hit->go_src)."</td>";
			echo "<td>".printMultiValue($hit->ec_id)."</td>";
			echo "<td>".printMultiValue($hit->ec_src)."</td>";
			echo "<td>".printMultiValue($hit->hmm_id)."</td>";
			echo '</tr>';
		}
		echo '</table>';
		echo $luceneResultPaginator->addPagination($page,$numHits,$dataset,"view","");	
		echo '</div>';	
	echo '</div>';	
echo '</div>';	
?>

<script type="text/javascript">
jQuery(function() {
	jQuery("#view-tabs").tabs({ spinner: '<img src="/metarep/img/ajax.gif"/>' });
});
</script>	