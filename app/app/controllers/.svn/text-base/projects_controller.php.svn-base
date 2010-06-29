<?php

/***********************************************************
*  File: projects_controller.php
*  Description: 
*
*  Author: jgoll
*  Date:   May 2, 2010
************************************************************/

class ProjectsController extends AppController {
	var $name = 'Projects';
	
	var $uses 	= array('Project','User');
	var $components = array('Solr','Format');

	function index() {
		$currentUser	= Authsome::get();
		$currentUserId 	= $currentUser['User']['id'];	    	        	
        $userGroup  	= $currentUser['UserGroup']['name'];			
		
		if($userGroup === 'Admin' || $userGroup === 'JCVI') {
			$this->Project->findAll();
			$this->set('projects', $this->paginate());
		}   
		else {
			$projects = $this->Project->findUserProjects();	  
			
			if($userGroup === 'User') {     	
				$this->set('projects', $projects);
				$this->render('index_no_pagination');
			}
			elseif($userGroup === 'Guest') {
				$this->set('projects', $projects);
				$this->render('index_no_pagination');
			}
        }		
	}

	function view($id = null) {
		if (!$id) {
			$this->Session->setFlash(__('Invalid Project.', true));
			$this->redirect(array('action'=>'index'));
		}
		$this->set('project', $this->Project->read(null, $id));
	}
	
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

	function download($id) {
		$project = $this->Project->findById($id);
		debug($project);
		
		$this->autoRender=false;

		$content = $this->Format->infoString($title,$selectedDatasets,$filter,null);
		$content.= $this->Format->comparisonResultsToDownloadString($counts,$selectedDatasets,$option);

		$fileName = "jcvi_metagenomics_report_".time().'.txt';

		header("Content-type: text/plain");
		header("Content-Disposition: attachment;filename=$fileName");
		echo $content;
	}
	
	
}
?>
