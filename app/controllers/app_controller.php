<?php
/***********************************************************
* File: app_controller.php
* Description: Parent class of all controllers. Handles data
* access and other permissions based on login credentials.
* Users that do no have access are redirected to the login
* page.
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
class AppController extends Controller {

	#global helpers
	var $helpers 	= array('Session','Html', 'Form','Crumb','Javascript','Ajax');
	var $components = array('Session','Cookie','RequestHandler','Authsome' => array('model' => 'User'));

	var $openAccessUrls = array(
							'users/login',
							'dashboard/index',
							'users/register',
							'users/feedback',
							'users/logout',
							'users/forgotPassword',
							'users/activatePassword',
							'users/changePassword',
							'users/guestLogin',
	);
	
	var $loginUrls = array(
							'projects/index',	
							'populations/index',
							'iframe/apis',
							'menus/quick',
							'compare/download',	
							'search/all',
							'search/downloadMetaInformationFacets',	
	); 

	//urls that need to be checked for matching user id
	var $userAccessUrl = array(
							'users/edit',						
	); 
	
	//urls that need to be checked for dataset permissions
	var $datasetAccessUrls = array(
							'view/index',
							'view/download',
							'search/index',
							'search/count',
							'search/dowloadFacets',
							'search/dowloadData',
							'search/link',
							'browse/blastTaxonomy',
							'browse/apisTaxonomy',
							'browse/enzymes',
							'browse/geneOntology',
							'browse/pathways',
							'browse/downloadChildCounts',
							'browse/dowloadFacets',
							'compare/index',
							'populations/view',	
							'libraries/view',											
	);		
	
	var $adminAccessUrls = array(
							'projects/add',	
							'projects/delete',
							'users/editProjectUsers'						
	);
	
	var $projectAccessUrls = array(
							'projects/view',
							'projects/delete',
							'libraries/edit',
							'projects/ftp',												
	);
	
	var $projectAdminUrls = array(
							'projects/edit',	
							'users/editProjectUsers',						
							'populations/add',
							'populations/edit',
							'populations/delete',	
							'libraries/edit',
							'libraries/delete',	
							'projects/editProjectUsers',						
	);	
		
	//handle permissions
	function beforeFilter() {
	
		//get current url
		$controller 	= $this->params['controller'] ;
		$action 		= $this->params['action'] ;
		$url 			= "$controller/$action";

		if(in_array($url,$this->openAccessUrls))  {	
			return;
		}		
		
		#handle all other permissions
		if($this->Authsome->get()) {
			
			
			
			if(in_array($url,$this->loginUrls))  {		
				return;
			}

			//handles ajax requests
			if($this->RequestHandler->isAjax()) {
				return;
			}
					
			//get parameters from URL
			$parameters = $this->params['pass'];
			
			//get user authentification
			$currentUser	= $this->Authsome->get();				
			$currentUserId 	= $currentUser['User']['id'];
			$userGroup  	= $currentUser['UserGroup']['name'];			

			#admin users have access to all sites and data
			if($userGroup === ADMIN_USER_GROUP) {
				return;
			}
			else if(in_array($url,$this->userAccessUrl))  {	
				$userId  = $parameters[0];		
				if($userId == $currentUserId ) {
					return;
				}
			}						
			else if(in_array($url,$this->datasetAccessUrls))  {	
				if($userGroup === INTERNAL_USER_GROUP) {
					return;
				}
				else {
					if($controller === 'populations') {
						$dataset = $this->Population->getNameById($parameters[0]);
					}
					elseif($controller === 'libraries') {
						$dataset = $this->Library->getNameById($parameters[0]);
					}
					elseif($controller === 'search' && $action === 'link') {
						//search all is not dataset restricted
						if($parameters[0] === 'all') {
							return;
						}
						else {
							$dataset = $parameters[2];
						}					
					}
					else {
						$dataset = $parameters[0];
					}
					
					if($this->Project->hasDatasetAccess($dataset,$currentUserId)) {
						return;
					}	
				}			
			}				
			else if(in_array($url,$this->projectAccessUrls))  {	
				if($userGroup === INTERNAL_USER_GROUP) {
					return;
				}	
				else {			
					$projectId  = $parameters[0];					
					if($this->Project->hasProjectAccess($projectId,$currentUserId)) {
						return;
					}
				}
			}
			else if(in_array($url,$this->projectAdminUrls))  {
				$projectId  = $parameters[0];	
				if($this->Project->isProjectAdmin($projectId,$currentUserId)) {
					return;
				}
			}
			else {				
				if($userGroup === INTERNAL_USER_GROUP) {
					return;
				}
			}

			$this->Session->setFlash("You don't have permissions to view this page.");
			$this->redirect("/dashboard/index");
		}
		else {	
			$this->Session->setFlash("Please log in.");
			$this->redirect("/dashboard/index");
			
		}
	}
	
	function redirect($url, $status = null, $exit = false) {
	    $temp = $url;
	    if(is_array($url)) {
	        $temp = '/';
	        $temp .= isset($url['controller']) ? $url['controller'] : $this->params['controller'];
	        $temp .= '/';
	        $temp .= isset($url['action']) ? $url['action'] : $this->params['action'];
	        $url = '';
	    }
	    $ajax = ($this->RequestHandler->isAjax()) ? ($temp{0} != '/') ? '/ajax/' : '/ajax' : null;
	    parent::redirect($ajax.$temp, $status, $exit);
	} 
}
?>