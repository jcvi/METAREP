<?php	
/***********************************************************
*  File: menus_controller.php
*  Description:
*
*  Author: jgoll
*  Date:   May 2, 2010
************************************************************/

class MenusController extends AppController {

	var $uses = array('Project');
    	
    function quick() {       	
       	$this->pageTitle = 'Quick Navigation';        	
       	$projects = $this->Project->findUserProjects();	  
    	$this->set('projects', $projects);
	}
}
?>
