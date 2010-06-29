<?php
/***********************************************************
*  File: populations_controller.php
*  Description:
*
*  Author: jgoll
*  Date:   Mar 4, 2010
************************************************************/

class PopulationsController extends AppController {

	var $helpers = array('Html', 'Form','Ajax');
	var $uses 	= array('Population','Library','Project');
	
	var $components = array('Solr');

	function index() {
		$this->Population->recursive = 1;
		$this->set('population', $this->paginate());
	}

	function view($id = null) {		
		if (!$id) {
			$this->flash(__('Invalid Population', true), array('action'=>'index'));
		}
		$this->set('population', $this->Population->read(null, $id));
	}

	function ajaxView($id = null) {
		
		if (!$id) {
			$this->flash(__('Invalid Population', true), array('action'=>'index'));
		}
		
		$this->set('population', $this->Population->read(null, $id));
		$this->set('status','Created');
		$this->render('view','ajax');
	}	
	
	function add($projectId=0) {	
		if (!empty($this->data)) {
			$this->Population->create();
			$projectId= $this->data['Population']['project_id'];
			
			$datasets = $this->Population->Library->find('list',array('conditions'=>array('project_id'=>$projectId)));
			$projects = $this->Population->Project->find('list');			
			
			#continue if more than one library has been selected
			if(isset($this->data['Library']['Library']) && $this->data['Library']['Library']>=2) {
				
				$libraries = array() ;
				
				#get selected libraries selected inmulti-select box
				foreach($this->data['Library']['Library'] as $library) {	
					$libraryEntry = $this->Library->findById($library);
					array_push($libraries,$libraryEntry['Library']['name']);
				}	
								
				#check if all libraries have a certain optional data type
				$optionalDatatypes  = $this->Project->checkOptionalDatatypes($libraries);
												
				#set optional data types
				$this->data['Population']['has_apis'] 	 = $optionalDatatypes['apis'];
				$this->data['Population']['has_clusters'] = $optionalDatatypes['clusters'];
				$this->data['Population']['has_filter'] 	 = $optionalDatatypes['filter'];
				$this->data['Population']['is_viral'] 	 = $optionalDatatypes['viral'];
							
				#if population could be saved
				if ($this->Population->save($this->data)) {					
						$populationId = $this->Population->getLastInsertId();
						$dataset 	  = $this->data['Population']['name'];
						
						#start solr index merging process
						$this->Solr->mergeIndex($projectId,$dataset,$libraries);						
						$this->redirect("/populations/ajaxView/$populationId");	
				}
				else {
					$this->set(compact('projectId','projects','datasets'));
					$this->render('add','ajax');
				}	
			}
			else {
				$multiSelectErrorMessage = "Please select at least two datasets.";			
				$this->set(compact('multiSelectErrorMessage','projectId','projects','datasets'));
				$this->render('add','ajax');
			}
		}
		$datasets = $this->Population->Library->find('list',array('conditions'=>array('project_id'=>$projectId)));
		$projects = $this->Population->Project->find('list');
		
		$this->set(compact('projectId','projects','datasets'));
	}

	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->flash(__('Invalid Population', true), array('action'=>'index'));
		}
		if (!empty($this->data)) {
			#debug($this->data);
			$projectId= $this->data['Population']['project_id'];
			#$this->LibraryPopulation->deleteAll($this->data, array( 'population_id' => $this->data['Population']['project_id']));
			
			if ($this->Population->save($this->data)) {
				
				$this->Session->setFlash("Population changes have been saved.");
				$this->redirect("/projects/view/$projectId");
			} else {
			}
		}
		if (empty($this->data)) {
			$this->Library->recursive = 1;
			$this->data = $this->Population->read(null, $id);
		}
		$datasets = $this->Population->Library->find('list',array('conditions'=>array('project_id'=>$this->data['Population']['project_id'])));
		$projects = $this->Population->Project->find('list');		
		$this->set(compact('projectId','projects','datasets'));
	}

	function delete($id = null) {
		if (!$id) {
			$this->flash(__('Invalid Population', true), array('action'=>'index'));
		}
		else {
			$this->data = $this->Population->read(null, $id);
			$populationName = $this->data['Population']['name'];
			$projectId = $this->data['Project']['id'];
						
			if ($this->Population->delete($id)) {
				#delete solr index file and core meta information
				$this->Solr->deleteIndex($this->data['Population']['name']);
				$this->Session->setFlash("Population changes have been saved.");
				$this->redirect("/projects/view/$projectId");
			}
		}
	}
}
?>