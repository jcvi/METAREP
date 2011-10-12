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
* @version METAREP v 1.3.0
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

require_once('../config/constants.php');

class AppController extends Controller {
	
	//$persistModel = true speeds up site by caching model classes. However it has to be 
	//treated with caution. It caused a chache exception and returned imcomplete
	//model objects google"$persistModel cakephp incomplete object". Setting this to false
	//until the root cause for this exception has been identified.
	var $persistModel 	= true;	
	
	var $helpers 		= array('Session','Html', 'Form','Javascript','Ajax');
	var $components 	= array('Session','Cookie','RequestHandler','Authsome' => array('model' => 'User'));

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
							'users/stats',
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
							'search/dowloadSequences',
							'search/link',
							'browse/blastTaxonomy',
							'browse/apisTaxonomy',
							'browse/enzymes',
							'browse/geneOntology',
							'browse/keggPathwaysEc',
							'browse/keggPathwaysKo',
							'browse/metacycPathways',
							'browse/downloadChildCounts',
							'browse/dowloadFacets',
							'itol/index',
							'compare/index',
							'populations/view',	
							'libraries/view',											
	);		
	
	var $adminAccessUrls = array(
							'projects/add',	
							'projects/delete',
							'users/editProjectUsers',
							'libraries/delete'						
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
		
		//handle all other permissions
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

			//admin users have access to all sites and data
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
						$this->loadModel('Population');
						$dataset = $this->Population->getNameById($parameters[0]);
					}
					elseif($controller === 'libraries') {
						$this->loadModel('Library');
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
					
					$this->loadModel('Project');				
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
					$this->loadModel('Project');				
					if($this->Project->hasProjectAccess($projectId,$currentUserId)) {
						return;
					}
				}
			}
			else if(in_array($url,$this->projectAdminUrls))  {
				$this->loadModel('Project');

				if($controller === 'libraries') {
					$this->loadModel('Library');
					$projectId = $this->Library->getProjectIdById($parameters[0]);			
				}
				elseif($controller === 'populations') {
					if($action === 'add') {
						$projectId  = $parameters[0];	
					}
					else {
						$this->loadModel('Population');
						$projectId = $this->Population->getProjectIdById($parameters[0]);			
					}		
				}
				else {
					$projectId  = $parameters[0];	
				}
				
				if($this->Project->isProjectAdmin($projectId,$currentUserId)) {						
						return;
				}
			}
			else if(!in_array($url,$this->adminAccessUrls)) {				
				if($userGroup === INTERNAL_USER_GROUP) {
					return;
				}
			}
			$this->Session->setFlash("You don't have permissions to view this page.");
			$this->redirect("/dashboard/index",null,true);
		}
		else {	
			$this->Session->setFlash("Please log in.");
			$this->redirect("/dashboard/index",null,true);
			
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
	
  /**
   * Translates a camel case string into a string with underscores (e.g. firstName -&gt; first_name)
   * @param    string   $str    String in camel case format
   * @return   string            $str Translated into underscore format
   */
  function camelCaseToUnderscore($str) {
    $str[0] = strtolower($str[0]);
    $func = create_function('$c', 'return "_" . strtolower($c[1]);');
    return preg_replace_callback('/([A-Z])/', $func, $str);
  }
 
  /**
   * Translates a string with underscores into camel case (e.g. first_name -&gt; firstName)
   * @param    string   $str                     String in underscore format
   * @param    bool     $capitalise_first_char   If true, capitalise the first char in $str
   * @return   string   $str translated into camel caps
   */
  function underscoreToCamelCase($str, $capitalise_first_char = false) {
    if($capitalise_first_char) {
      $str[0] = strtoupper($str[0]);
    }
    $func = create_function('$c', 'return strtoupper($c[1]);');
    return preg_replace_callback('/_([a-z])/', $func, $str);
  }	
  
  
  function getmicrotime(){
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
  }
}
?>