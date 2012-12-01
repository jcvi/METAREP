<!----------------------------------------------------------
  
  File: add.ctp
  Description: Add Project Page
  
  Let's you create a new project.

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

<div class="projects form">
<h2><?php __('New Project');?></h2>
<?php echo $form->create('Project');?>
	<fieldset>
 		<legend></legend>
	<?php
		echo $form->input('name');
		echo $form->input('user_id',array('options'=> $userSelectArray,'selected'=> $projectUserId,'empty'=>'--Select User--','label'=> 'Project Admin'));		
		echo $form->input('description',array('type' => 'textarea'));
		echo $form->input('charge_code');
		echo $form->input('jira_link',array('type' => 'text'));
	?>
	</fieldset>
<?php echo $form->end('Submit');?>
</div>
