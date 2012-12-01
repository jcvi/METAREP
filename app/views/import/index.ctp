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
	.upload-forms {
		float:left !important;
		
		display: block !important;
	}
</style>

<h2><?php  __('Import Library');?><span class="selected_library"><?php echo "$projectName project"; ?></span></h2>
<?php #echo $html->div('download', $html->link($html->image("download-large.png",array("title" => "Download Project Information")), array('controller'=> 'projects','action'=>'download',$project['Project']['id']),array('escape' => false)));?>
<fieldset>
<legend>Upload Tab Delimited File</legend> 
<div class="upload-forms">
<?php 
echo $form->create('Import',array('enctype' => 'multipart/form-data','url'=>array('controller'=>'import','action'=>"validation/1/$projectId")));
echo $form->file('File');
#echo $ajax->submit('Imports', array('url'=> array('controller'=>'imports', 'action'=>'validate',$projectId),'update' => 'upload-forms', 'loading' => 'Element.show(\'spinner\')', 'complete' => 'Element.hide(\'spinner\'); Effect.Appear(\'validate-import\',{ duration: 1.5 })', 'before' => 'Element.hide(\'validate-import\');'));
echo $form->submit('Upload');
echo $form->end();
?>
</div>
</fieldset>
<div id="validate-import">
</div>
