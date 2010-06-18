<?php
class LibrariesController extends AppController {

	var $name = 'Libraries';
	var $helpers = array('Html', 'Form');
	var $uses = array('Library','Project');
	var $components = array('Solr');

	function index() {
		$this->Library->recursive = 1;
		//$this->Library->unbindModel(array('hasMany' => array('Read')));
		
		$this->set('libraries', $this->paginate());
	}

	function view($id = null) {
		
		if (!$id) {
			$this->flash(__('Invalid Library', true), array('action'=>'index'));
		}
		$this->set('library', $this->Library->read(null, $id));
	}

	function add() {
		if (!empty($this->data)) {
			$this->Library->create();
			if ($this->Library->save($this->data)) {
				$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
				$this->redirect('/projects/index');
			} else {
			}
		}
		$projects = $this->Library->Project->find('list');
		$this->set(compact('projects'));
	}

	function edit($id = null) {
		
		
		if (!$id && empty($this->data)) {
			$this->flash(__('Invalid Library', true), array('action'=>'index'));
		}
		if (!empty($this->data)) {

			if ($this->Library->save($this->data)) {
				$this->data = $this->Library->read(null, $id);
				//$this->flash(__('The Library has been saved.', true), array('action'=>'index'));
				$this->Session->setFlash("Library changes have been saved.");
				$this->redirect("/projects/view/".$this->data['Library']['project_id']);
			}
		}
		if (empty($this->data)) {
			$this->data = $this->Library->read(null, $id);
		}
		$projects = $this->Library->Project->find('list');
		$this->set(compact('projects'));
	}

	function delete($id = null) {
		if (!$id) {
			$this->flash(__('Invalid Library', true), array('action'=>'index'));
		}
		else{
			$this->data = $this->Library->read(null, $id);
			$projectId = $this->data['Project']['id'];
		
			if($this->Library->delete($id)) {		
				$this->Solr->deleteIndex($this->data['Library']['name']);
				$this->flash(__('Library deleted', true), array('action'=>'index'));
				$this->redirect("/projects/view/$projectId");
			}
		}
	}

}
?>