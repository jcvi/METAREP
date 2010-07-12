<!----------------------------------------------------------
  
  File: add.ctp
  Description: Add Library Page
  
  NOT USED - FUTURE IMPLEMENTATION
  
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

<div class="libraries form">
<h2><?php __('New Library');?></h2>
<?php echo $form->create('Library');?>
	<fieldset>
 		<legend></legend>
	<?php
		echo $form->input('name');
		echo $form->input('description',array('type' => 'textaerea'));
		echo $form->input('apis_database',array('type' => 'text'));
//		echo $form->input('reads_file_path',array('type' => 'text'));
//		echo $form->input('evidence_file_path',array('type' => 'text'));
//		echo $form->input('annotation_file_path',array('type' => 'text'));
		echo $form->input('project_id');
	?>
	</fieldset>
<?php echo $form->end('Submit');?>

		</div>
