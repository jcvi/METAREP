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
* @version METAREP v 1.0.1
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

class ProjectsController extends AppController {
	
	var $name 		= 'Projects';
	var $uses 		= array('Project','User');
	var $components = array('Solr','Format');
	
	/**
	 * List all projects
	 * 
	 * @return void
	 * @access public
	 */	
	function index() {
		
//		$this->Project->unbindModel(array('hasMany' => array('Library')));
//		$this->Project->unbindModel(array('hasMany' => array('Population')));
//		
//		$this->Project->bindModel(array('hasOne' => array(
//        							'User' => array(
//            						'foreignKey' => false,
//            						'conditions' => array('User.id = Project.user_id'),
//									)))
//        );
		
		$currentUser	= Authsome::get();
		$currentUserId 	= $currentUser['User']['id'];	    	        	
        $userGroup  	= $currentUser['UserGroup']['name'];			

		if($userGroup === ADMIN_USER_GROUP || $userGroup === INTERNAL_USER_GROUP) {		
			$this->Project->findAll();
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
		if (!$id) {
			$this->Session->setFlash(__('Invalid Project.', true));
			$this->redirect(array('action'=>'index'));
		}
		$this->set('project', $this->Project->read(null, $id));
	}
	
	/**
	 * Add new project 
	 * 
	 * @return void
	 * @access public
	 */	
	function add() {
		if (!empty($this->data)) {
			$this->Project->create();
			if ($this->Project->save($this->data)) {
				$this->Session->setFlash(__('The Project has been saved', true));
				$this->redirect(array('action'=>'index'));
			} else {
				$this->Session->setFlash(__('The Project could not be saved. Please, try again.', true));
			}
		}
		else {
			//get all users except admin
			$this->User->recursive=1;
			$this->User->unbindModel(array('belongsTo' => array('UserGroup'),),false);	
			$users  = $this->User->findAll(array('NOT'=>array('User.username'=>'admin')));
						
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
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(__('Invalid Project', true));
			$this->redirect(array('action'=>'index'));
		}
		if (!empty($this->data)) {
			if ($this->Project->save($this->data)) {
				$this->Session->setFlash(__('The Project has been saved',true));
				$this->redirect("/dashboard");	
			} else {
				$this->Session->setFlash(__('The Project could not be saved. Please, try again.', true));
			}
		}
		if (empty($this->data)) {
			
			$this->data = $this->Project->read(null, $id);
			
			//get all users except admin
			$this->User->recursive=1;
			$this->User->unbindModel(array('belongsTo' => array('UserGroup'),),false);	
			$users  = $this->User->findAll(array('NOT'=>array('User.username'=>'admin')));
						
			$userSelectArray = array();
			
			foreach($users as $user) {
				$userSelectArray[$user['User']['id']]= "{$user['User']['first_name']} {$user['User']['last_name']}";
			}
			
			$projectUserId = $this->data['Project']['user_id'];
			
			$this->set(compact('userSelectArray','projectUserId'));		
		}
	}
	
	/**
	 * Delete project 
	 * 
	 * @param int $projectId project id
	 * @return void
	 * @access public
	 */
	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(__('Invalid id for Project', true));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->Project->delete($id)) {
			$this->Session->setFlash(__('Project deleted', true));
			$this->redirect(array('action'=>'index'));
		}
	}
	
	/**
	 * Sets and activates ftp link on the project view page
	 * 
	 * @param int $projectId project id
	 * @param String $dataset dataset name
	 */
	function ftp($projectId,$dataset) {
		$fileName = "$dataset.tgz";
		$filePath = "ftp://".FTP_USERNAME.":".FTP_PASSWORD."@".FTP_HOST."/$projectId/$fileName";	
		$this->set('ftpLink',$filePath);
		$this->set('project', $this->Project->read(null,$projectId));
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
//		$content = $this->Format->infoString($title,$selectedDatasets,$filter,null);
//		$content.= $this->Format->comparisonResultsToDownloadString($counts,$selectedDatasets,$option);
//
//		$fileName = "jcvi_metagenomics_report_".time().'.txt';
//
//		header("Content-type: text/plain");
//		header("Content-Disposition: attachment;filename=$fileName");
//		echo $content;
//	}		
}
?>
