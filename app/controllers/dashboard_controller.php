<?php
/***********************************************************
*  File: dash_board_controller.php
*  Description:
*
*  Author: jgoll
*  Date:   Feb 26, 2010
************************************************************/

class DashboardController extends AppController {
	
	var $components = array('Solr');
    
	var $uses = array('GosBlog','Project');
	
	function index() {

		#$this->layout = 'jcvi';
		
		$projects = $this->Project->recursive = 2;
		$projects = $this->User->recursive = 1;
		
		if($this->Authsome->get()) {	
			$news 	  = $this->GosBlog->find('all',array('limit' => 10));
			
			$projects = $this->User->recursive = 1;
			$projects = $this->Project->recursive = 2;
			#$projects = $this->User->Group->recursive = 0;
			
			$user 		= $this->Authsome->get();
			$userId 	= $user['User']['id'];
			$userGroup  = $user['UserGroup']['name'];

			//full adminsitration controll for admin
			if($userGroup === 'Admin') {
				$projects = $this->Project->findAll();
			}			
			//project administration per user
			elseif($userGroup === 'User' || $userGroup === 'JCVI') {
				$projects = $this->Project->find('all',array('conditions'=>array('Project.user_id'=>$userId)));				
			}	
			elseif($userGroup === 'Guest') {
				$projects = null;
			}
			
			$this->set('projects', $projects);
			$this->set('news', $news);
			$this->render('user_dashboard');
		}
		else {			
			$news 	  = $this->GosBlog->find('all',array('limit' => 10));
			$projects = $this->Project->find('all',array('order'=>array('updated DESC'),'limit' => 5));
			$this->set('projects', $projects);			
			$this->set('news', $news);
		} 
	}
}
?>
