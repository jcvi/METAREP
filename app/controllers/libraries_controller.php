<?php
/***********************************************************
* File: libraries_controller.php
* Description: Handles all actions that are related to
* individual libraries.
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
class LibrariesController extends AppController {

	var $name 		= 'Libraries';
	var $helpers 	= array('Html', 'Form');
	var $uses 		= array('Library','Project');
	var $components = array('Solr');

	function edit($id = null,$projectId = null) {	
		$this->loadModel('Library');
		$this->Library->contain('Project');
		
		if (!$id && empty($this->data)) {
			$this->Session->setFlash("Invalid library id.");
			$this->redirect("/projects/view/$projectId",null,true);
		}
		if(!empty($this->data)) {
			
			if ($this->Library->save($this->data)) {
				$this->data = $this->Library->read(null, $id);
				
				$projectId = $this->data['Library']['project_id'];
				
				//delete project view cache
				Cache::delete($projectId.'project');
				
				$this->Session->setFlash("Library changes have been saved.");
				$this->redirect("/projects/view/$projectId",null,true);
			}

		}
		if (empty($this->data)) {
			$this->data = $this->Library->read(null, $id);	
			Cache::delete($id.'project');
			if(empty($this->data)) {
				$this->Session->setFlash("Invalid library id.");
				$this->redirect("/projects/index",null,true);
			}
		}
		
		$projects = $this->Library->Project->find('list');
		$this->set(compact('projects'));
	}

	function delete($id = null) {
		$this->loadModel('Library');
		
		if (!$id) {
			$this->flash(__('Invalid Library', true), array('action'=>'index'));
		}
		else{
			$this->data = $this->Library->read(null, $id);
			$projectId  = $this->data['Library']['project_id'];

			if($this->Library->delete($id)) {	
							
				$this->Solr->deleteIndex($this->data['Library']['name']);
				
				//delete project view cache
				Cache::delete($projectId.'project');
								
				$this->flash(__('Library deleted', true), array('action'=>'index'));
				$this->redirect("/projects/view/$projectId",null,true);
			}
		}
	}
	
	//future implementation
	//function add() {
	//	if (!empty($this->data)) {
	//		$this->Library->create();
	//		if ($this->Library->save($this->data)) {
	//			$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
	//			$this->redirect('/projects/index',null,true);
	//		} else {
	//		}
	//	}
	//	$projects = $this->Library->Project->find('list');
	//	$this->set(compact('projects'));
	//}	
}
?>