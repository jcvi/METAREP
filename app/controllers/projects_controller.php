<?php
/***********************************************************
* File: projects_controller.php
* Description: Controller that handles all project related 
* actions.
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

class ProjectsController extends AppController {
	
	var $name 		= 'Projects';
	var $uses 		= array();
	var $components = array('Solr','Format');
	
	
	/**
	 * List all projects
	 * 
	 * @return void
	 * @access public
	 */	
	function index() {
		$this->loadModel('Project');
		
		$currentUser	= Authsome::get();
		$currentUserId 	= $currentUser['User']['id'];	    	        	
        $userGroup  	= $currentUser['UserGroup']['name'];			
		
		if($userGroup === ADMIN_USER_GROUP || $userGroup === INTERNAL_USER_GROUP) {		
			$this->paginate['Project'] = array(
		    'contain' => array('Population.id','Population.project_id','Library.id','Library.project_id'),
		    'order' => 'Project.id');
			$this->set('projects', $this->paginate());
		}   
		else {
			$projects = $this->Project->findUserProjects();	  
			$this->set('projects', $projects);							
			$this->render('index_no_pagination');
        }		
	}
	  	
	/**
	 * View project
	 * 
	 * @param int $projectId project id
	 * @return void
	 * @access public
	 */
	
	function view($id = null) {
		$this->loadModel('Project');
		$this->Project->contain('Library',array('Population'=>'Library'));
		
		if(!$id) {
			$this->Session->setFlash(__('Invalid Project.', true));
			$this->redirect(array('action'=>'index'),null,true);
		}
		else {
			//cache project view page 
			if (($project = Cache::read($id.'project')) === false) {
				$project =  $this->Project->read(null, $id);	
				
				if($project['Library']) {					
					foreach($project['Library'] as &$library) {
						$library['count'] = number_format($this->Solr->documentCount($library['name']));				
					}
				}
				
				if($project['Population']) {					
					foreach($project['Population'] as &$population) {
						$population['count'] = number_format($this->Solr->documentCount($population['name']));	
						$populationLibraryCountResult = $this->Project->Library->Population->find(
														'all',array('contain'=>array('Library.id'),
														'fields'=>array('Population.id'),
														'conditions'=>array('Population.name'=>$population['name'])));	
						$population['libraryCount'] = 	sizeof($populationLibraryCountResult[0]['Library']);						
					}			
				}	
								
				Cache::write($id.'project', $project);
			}
		}		
		
		$this->set('project',$project);
	}
	
	/**
	 * Add new project 
	 * 
	 * @return void
	 * @access public
	 */	
	function add() {		
		if (!empty($this->data)) {
			$this->loadModel('Project');
			$this->Project->create();
			if ($this->Project->save($this->data)) {
				$this->Session->setFlash(__('The Project has been saved', true));
				$this->redirect(array('action'=>'index'),null,true);
			} else {
				$this->Session->setFlash(__('The Project could not be saved. Please, try again.', true));
			}
		}
		else {
			$this->loadModel('User');
			//get all users except admin
			$this->User->recursive=1;
			$this->User->unbindModel(array('belongsTo' => array('UserGroup'),),false);	
			#$users  = $this->User->findAll('NOT'=>array('User.username'=>'admin')));
			$users  = $this->User->findAll(array('NOT'=>array('User.username'=>'admin','User.username'=>'guest')));			
			$userSelectArray = array();
			
			foreach($users as $user) {
				$userSelectArray[$user['User']['id']]= "{$user['User']['first_name']} {$user['User']['last_name']}";
			}
			
			$projectUserId = $this->data['Project']['user_id'];
			$this->set(compact('userSelectArray','projectUserId'));		
		}
	}
	
	/**
	 * Edit project details 
	 * 
	 * @param int $projectId project id
	 * @return void
	 * @access public
	 */
	function edit($id = null) {
		$this->loadModel('Project');
		$this->loadModel('User');
		
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(__('Invalid Project', true));
			$this->redirect(array('action'=>'index'),null,true);
		}
		if (!empty($this->data)) {
			
			if ($this->Project->save($this->data)) {
				
				//delete project view cache
				Cache::delete($id.'project');
				
				$this->Session->setFlash(__('The Project has been saved',true));
				$this->redirect("/dashboard",null,true);	
			} else {
				$this->Session->setFlash(__('The Project could not be saved. Please, try again.', true));
			}
		}
		if (empty($this->data)) {
				
			$this->data = $this->Project->read(null, $id);
			
			//get all users except admin
			$this->User->contain(array('Project.user_id'));
			#$this->User->unbindModel(array('belongsTo' => array('UserGroup'),),false);	
			$users  = $this->User->findAll(array('NOT'=>array('User.username'=>'admin')));
						
			$userSelectArray = array();
			
			foreach($users as $user) {
				$userSelectArray[$user['User']['id']]= "{$user['User']['first_name']} {$user['User']['last_name']}";
			}
			
			$projectUserId = $this->data['Project']['user_id'];
			
			$this->set(compact('userSelectArray','projectUserId'));		
		}
	}
	
	function refresh($id) {
		//delete project view cache
		Cache::delete($id.'project');
		$this->redirect("/projects/view/$id",null,true);
	}
	
	/**
	 * Delete project 
	 * 
	 * @param int $projectId project id
	 * @return void
	 * @access public
	 */
	function delete($id = null) {
		$this->loadModel('User');
		if (!$id) {
			$this->Session->setFlash(__('Invalid id for Project', true));
			$this->redirect(array('action'=>'index'),null,true);
		}
		if ($this->Project->delete($id)) {
			$this->Session->setFlash(__('Project deleted', true));
			$this->redirect(array('action'=>'index'),null,true);
		}
	}
	
	/**
	 * Sets and activates ftp link on the project view page.
	 * Uses FTP connection paramters specified in the METAREP
	 * configuration file.
	 * 
	 * @param int $projectId project id
	 * @param String $dataset dataset name
	 */
	function ftp($projectId,$dataset) {
		$this->loadModel('Project');
		$this->Project->contain('Library','Population');
		if(defined('FTP_HOST') && defined('FTP_USERNAME') && defined('FTP_PASSWORD')) {
			$fileName = "$dataset.tgz";
			$filePath = "ftp://".FTP_USERNAME.":".FTP_PASSWORD."@".FTP_HOST."/$projectId/$fileName";	
			$this->set('ftpLink',$filePath);
		}
		
		//cache project view page 
		if (($project = Cache::read($projectId.'project')) === false) {
			$project =  $this->Project->read(null, $projectId);	
			
			if($project['Library']) {					
				foreach($project['Library'] as &$library) {
					$library['count'] = number_format($this->Solr->count($library['name']));				
				}
			}
			if($project['Population']) {
				foreach($project['Population'] as &$population) {
					$population['count'] = number_format($this->Solr->count($population['name']));				
				}			
			}						
			Cache::write($projectId.'project', $project);
		}		
		
		$this->set('project',$project);
		$this->render('view');
	}

	/**
	 * Under development | export project information
	 */	
//	function download($id) {
//		$project = $this->Project->findById($id);
//		#debug($project);
//		
//		$this->autoRender=false;
//
//		$content = $this->Format->infoString($ti$this->Project->contain('Library','Population');tle,$selectedDatasets,$filter,null);
//		$content.= $this->Format->comparisonResultsToDownloadString($counts,$selectedDatasets,$option);
//
//		$fileName = uniqid('jcvi_metagenomics_report_').'.txt';
//
//		header("Content-type: text/plain");
//		header("Content-Disposition: attachment;filename=$fileName");
//		echo $content;
//	}		
}
?>
