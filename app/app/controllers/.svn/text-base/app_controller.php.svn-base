<?php
/***********************************************************
*  File: app_controller.php
*  Description:
*
*  Author: jgoll
*  Date:   Mar 22, 2010
************************************************************/

class AppController extends Controller {
	
	#global helpers 
	var $helpers = array('Session','Html', 'Form','Crumb','Javascript','Ajax');
	var $components = array('Session','Cookie','RequestHandler','Authsome' => array('model' => 'User'));
	
	
	var $openUrls = array(	'users/login',
							'dashboard/index',
							'users/register',
							'users/forgotPassword',
							'users/activate_password');
	//handle permissions
	function beforeFilter() {	
		if($this->RequestHandler->isAjax()) {
			return;
		}		
		
		//get current url
		$controller 	= $this->params['controller'] ;
		$action 		= $this->params['action'] ;			
		$url 			= "$controller/$action";

		#check if url is publicly accessable	
		if(in_array($url,$this->openUrls)) {
			return;
		}

		#handle all other permissions
		if($this->Authsome->get()) {			
			$currentUser	= $this->Authsome->get();
			$currentUserId 	= $currentUser['User']['id'];	
			$userGroup  	= $currentUser['UserGroup']['name'];	
			
			#admin users have access to all sites and data
			if($userGroup === ADMIN_USER_GROUP) {
				return;
			}
			else {				
				#handle data access for view controller
				if($controller==='view') {	
					if($action==='index' || $action==='download' ) {				
						$parameters 	= $this->params['pass'];
						$dataset = 	$parameters[0];	
						
						if($userGroup === JCVI_USER_GROUP) {
							return;
						}
						if($this->Project->hasDatasetAccess($dataset,$currentUserId)) {
							return;
						}
					}
					if($action==='facet' )	{
						return;
					}									
				}	

				#handle data access for search controller
				if($controller==='search') {	
					$parameters = $this->params['pass'];
					$dataset 	= 	$parameters[0];	
					
					if($userGroup === JCVI_USER_GROUP) {
						return;
					}
					if($this->Project->hasDatasetAccess($dataset,$currentUserId)) {
							return;
					}													
				}					

				#handle data access for browse controller
				if($controller==='browse') {	
					$parameters = $this->params['pass'];
					$dataset 	= 	$parameters[0];	
					
					if($userGroup === JCVI_USER_GROUP) {
						return;
					}
					if($this->Project->hasDatasetAccess($dataset,$currentUserId)) {
						return;
					}													
				}	

				#handle data access for browse controller
				if($controller==='compare') {
					if($action === 'index') {						
						$parameters = $this->params['pass'];
						$dataset 	= $parameters[0];	
						
						if($userGroup === JCVI_USER_GROUP) {
							return;
						}				
						if($this->Project->hasDatasetAccess($dataset,$currentUserId)) {
							return;
						}
					}	
					else {
						return;
					}												
				}					
				
				#handle data access for projects
				if($controller==='projects') {					
					if($action != 'index' && $action != 'add') {
						#get project id
						$parameters 	= $this->params['pass'];
						$projectId 		= $parameters[0];						
						
						#only valid project ids can be passed
						if($action==='view') {		
							#JCVI users can see all data
							if($userGroup === JCVI_USER_GROUP) {
								return;
							}
							if($this->Project->hasProjectAccess($projectId,$currentUserId)) {
								return;
							}										
						}
						#edit only allowed for project managers
						if($action==='edit') {	
							if($this->Project->isProjectAdmin($projectId,$currentUserId)) {
								return;
							}
						}	
					}							
				}	
				
				//handle population access
				if($controller==='populations') {
					if($action === 'index') {
						if($userGroup === JCVI_USER_GROUP) {
							return;
						}
					}
					else {
						$parameters = $this->params['pass'];
						$arg1		= $parameters[0];
												
						if($action==='view') {
							$population = $this->Population->findById($arg1);
							#JCVI users can see all data
							if($userGroup === JCVI_USER_GROUP) {
								return;
							}														
							if($this->Project->hasDatasetAccess($population['Population']['name'],$currentUserId)) {
								return;
							}	
						}						
						if($action==='add') {							
							#grant access for project admins		
							if($this->Project->isProjectAdmin($arg1,$currentUserId)) {
								return;
							}
						}
						if($action==='delete') {
							$population = $this->Population->findById($arg1);
														
							#grant access for dataset admins
							if($this->Project->isDatasetAdmin($population['Population']['name'],$currentUserId)) {
								return;
							}
						}						
					}
				}	
				
				//handle library access
				if($controller==='libraries') {
					if($action === 'index') {
						#JCVI users can see all data
						if($userGroup === JCVI_USER_GROUP) {
							return;
						}
					}
					else {							
						if($action==='edit') {	
							$parameters = $this->params['pass'];
							$libraryId	= $parameters[0];
							$library	= $this->Library->findById($libraryId);
							
							#grant access for project admins		
							if($this->Project->isDatasetAdmin($library['Library']['name'],$currentUserId)) {
								return;
							}
						}
					}

				}
				
				if($controller==='users') {					
					if($action==='editProjectUsers') {							
						$parameters 	= $this->params['pass'];
						$projectId	 	= $parameters[0];						
						#grant access for project admins		
						if($this->Project->isProjectAdmin($projectId,$currentUserId)) {
							return;
						}
					}
				}	
				if($controller === 'rest') {
					return;
				}
				
				if($this->Authsome->check($url)) {
					return;
				}			
				$this->Session->setFlash("You don't have permissions to view this page.");
				$this->redirect("/dashboard");			
			}
		}	
		else {
			$this->Session->setFlash("Please log in.");
			$this->redirect("/dashboard");	
		}
	}
}
?>