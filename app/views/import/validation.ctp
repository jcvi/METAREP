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
    <li><?php echo $html->link('View Project', "/projects/view/$projectId");?></li>
    <li><?php echo $html->link('Import Library', "/import/index/$projectId");?></li>
    <li><?php echo $html->link('Validate Entries', "/import/validate/1/$projectId");?></li>
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
	.validation-panel {
		height:540px;
		width:600px;
		overflow:auto;
	}
	fieldset {
		width:600px ! important;	
		height:630px ! important;	
	}
	.button {
		float: right;
		margin-right:14px ! important;
	}
	.paging {
		float: left;
		margin:0px ! important;
	}		
	table tr.altrow td {
		background-color: #f4f4f4 !important;
	}
	
</style>

<h2><?php  __('Validate Entries');?><span class="selected_library"><?php echo("$library ($projectName)");?></span></h2>
<fieldset>
<legend>Validate Entries</legend> 
<?php echo($luceneResultPaginator->addPageInformation($page,$fileLineCount,20));?>
<div class="validation-panel">
<?php 

for($e = 0;$e< sizeof($entries);$e++) {	
	echo('<table width=200px style=\"padding-center:0px; width:800px !important;border-width:0px;>');
	echo('<tr>
			<th style=\"padding-center:0px;width: 80px; border-width:0px;font-size:0.9em;\">Field Name</th>
		  	<th style=\"padding-center:0px;width:260px; border-width:0px;font-size:0.9em;\">Value</th>
		  	<th style=\"padding-center:0px;width:260px; border-width:0px;font-size:0.9em;\">Validation</td>
		  </tr>');
	$fields = explode("\t",$entries[$e]);		
	$i=0;
	for($f = 0;$f< sizeof($fieldNames);$f++) {
		$fields[$f] = str_replace('||','<BR>',$fields[$f]);
		
		$class = null;
		if ($i++ % 2 == 0) {
			$class = 'style=\"width:260px important;background-color:#f4f4f4 !important;\"';
		}
		else {
			$class = 'style=\"width:260px important;\"';
		}
		$img = ($validation[$e][$f] === 'valid entry') ? $html->image("valid.png",array("title" => 'valid entry','Width'=>'15px')) : $html->image("non-valid.png",array("title" => $fieldNames[$f],'Width'=>'15px'));			
		#$col = 
		
		echo("<tr>
			<td style=\"border-width:0px;width:80px !important;font-size:0.9em;background-color:#C0C0C0;word-wrap:break-word;\">{$fieldNames[$f]}</td>
			<td $class>{$fields[$f]}</td>
			<td $class>$img {$validation[$e][$f]}</td>
		</tr>");
	}
	echo('</table>');
	$i++;
}
?>
</div>
<BR>
<?php echo $luceneResultPaginator->addPagination($page,$fileLineCount,'',"import",20,$projectId); 
echo($html->link("Import","/import/import/$projectId/$library",array('class'=>'button',"id"=>'try-it')));?> 
</fieldset>
<div id="validate-import">
</div>