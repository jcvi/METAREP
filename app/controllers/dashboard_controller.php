<?php
/***********************************************************
* File: dash_board_controller.php
* Description: Home page. Handles user login, registration 
* and briefly describes METAREP features.
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
* @version METAREP v 1.4.0
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

class DashboardController extends AppController {
	
	var $components = array('Solr');  
	var $uses = array();
	
	function index() {
		$this->loadModel('Project');	
		$this->loadModel('Blog');
		
		$projects = array();
		
		$this->Project->contain('Library.id','Population.id');
		
		if($this->Authsome->get()) {				
			$news = $this->Blog->find('all',array('limit' => 4));
			
			$user 		= $this->Authsome->get();
			$userId 	= $user['User']['id'];
			$userGroup  = $user['UserGroup']['name'];

			//full administration controll for admin
			if($userGroup === ADMIN_USER_GROUP) {
				$projects = $this->Project->findAll();
			}			
			//project administration per user
			elseif($userGroup === EXTERNAL_USER_GROUP || $userGroup === INTERNAL_USER_GROUP) {
				$projects = $this->Project->find('all',array('conditions'=>array('Project.user_id'=>$userId)));				
			}	
			elseif($userGroup === GUEST_USER_GROUP) {
			}
			
			$this->set('projects', $projects);
			$this->set('news', $news);
			$this->render('user_dashboard');
		}
		else {			
			$news 	  = $this->Blog->find('all',array('limit' => 4));
			$projects = $this->Project->find('all',array('order'=>array('updated DESC'),'limit' => 5));
			$this->set('projects', $projects);			
			$this->set('news', $news);
		} 
	}
}
?>
