<?php	
/***********************************************************
* File: menus_controller.php
* Description: This controller generates a quick menu that
* lists projects -> datasets -> actions.
*
* PHP versions 4 and 5
*
* METAREP : High-Performance Comparative Metagenomics Framework (http://www.jcvi.org/metarep)
* Copyright(c)  J. Craig Venter Institute (http://www.jcvi.org)
*
* Licensed under The MIT License
* Redistributions of files must retain the above copyright notice.
*
* @link http://www.jcvi.org/metarep METAREP Project
* @package metarep
* @version METAREP v 1.0.1
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/
class MenusController extends AppController {

	var $uses = array('Project');
    	
    function quick() {       	
       	$this->pageTitle = 'Quick Navigation';        	
       	$projects = $this->Project->findUserProjects();	  
    	$this->set('projects', $projects);
	}
}
?>
