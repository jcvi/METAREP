<?php
/***********************************************************
* File: populations_controller.php
* Description: The population Controller handles all population
* actions. To get higher level summaries, users are able to 
* create a new dataset by merging multiple existing datasets.
* Populations also provide a better basis for statistical inference.
* For example, METASTS a modified non-parametric t-test is available for
* comparing two populations.
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

class PopulationsController extends AppController {
	var $components = array('Solr');
	var $uses = array();
	
	var $paginate = array(
	    'contain' => array('Project.name','Project.id'),
	    'order' => 'Population.id');

	/**
	 * Initializes index population page
	 * 
	 * @return void
	 * @access public
	 */		
	function index() {		
		$this->loadModel('Population');		
		$populations = $this->paginate('Population');
		$this->set('population', $populations);		
	}

	/**
	 * View population
	 * 
	 * @return void
	 * @access public
	 */	
	function view($id = null) {	
		$this->loadModel('Population');	
		$this->Population->contain('Project','Library');
		
		if (!$id) {
			$this->flash(__('Invalid Population', true), array('action'=>'index'));
		}
		$population = $this->Population->read(null, $id);
		
		$this->set('population', $population);
	}

	/**
	 * View population after a new index has been created. Executed by ajax call.
	 * 
	 * @param int $id population id
	 * @return void
	 * @access public
	 */	
	function ajaxView($id = null) {
		$this->loadModel('Population');	
		$this->Population->contain('Project','Library');
		
		if (!$id) {
			$this->flash(__('Invalid Population', true), array('action'=>'index'));
		}
		$population = $this->Population->read(null, $id);
		
		$this->set('population', $population);
		$this->set('status','Created');
		$this->render('view','ajax');
	}	

	/**
	 * Add new population to a project
	 * 
	 * @param int $id population id
	 * @return void
	 * @access public
	 */		
	function add($projectId = 0) {
		$this->loadModel('Population');
		$this->Population->contain('Project','Library');

		//if post data has been provided
		if(!empty($this->data)) {
								
			//to store library information of selected libraries
			$libraries = array() ;
			$this->loadModel('Project');
			$this->loadModel('Library');

			$this->Population->create();
			$projectId = $this->data['Population']['project_id'];

			//get population libraries
			$datasets = $this->Population->Library->find('list',array('conditions'=>array('project_id'=>$projectId)));
				
			//get population project information
			$projects = $this->Population->Project->find('list');

			//continue if more than one library has been selected
			if(isset($this->data['Library']['Library']) && count($this->data['Library']['Library']) >= 2) {
				
				//get selected libraries from multi-select box				
				foreach($this->data['Library']['Library'] as $library) {
					$libraryEntry = $this->Library->findById($library);
					array_push($libraries,$libraryEntry['Library']['name']);
				}
					
				
				//set optional data types based on library types
				$optionalDatatypes  = $this->Project->checkOptionalDatatypes($libraries);
				$this->data['Population']['has_apis'] 	 = $optionalDatatypes['apis'];
				$this->data['Population']['has_clusters']= $optionalDatatypes['clusters'];
				$this->data['Population']['has_filter']  = $optionalDatatypes['filter'];
				$this->data['Population']['has_ko'] 	 = $optionalDatatypes['ko'];
				$this->data['Population']['is_viral'] 	 = $optionalDatatypes['viral'];
				$this->data['Population']['is_weighted'] = $optionalDatatypes['weighted'];
				$this->data['Population']['has_sequence']= $optionalDatatypes['sequence'];
				

				//if population could be saved
				if ($this->Population->save($this->data)) {
					$populationId = $this->Population->getLastInsertId();
					$dataset 	  = $this->data['Population']['name'];
					
					//start solr index merging process
					try {
						$this->Solr->mergeIndex($projectId,$dataset,$libraries);
					}
					catch (Exception $e) {
						$this->Population->delete($populationId);
						$this->Session->setFlash(__('Solr Index Merge Exception. Population could not be generated.'.$e->getMessage(), true));
						$this->setAction('delete',$populationId);
					}
								
					//delete project view cache
					Cache::delete($projectId.'project');
					
					$this->redirect("/populations/ajaxView/$populationId",null,true);
				}
				else {
					$this->set(compact('projectId','projects','datasets'));
					$this->render('add','ajax');
				}
			}
			//if no library or only one library has been selected
			else {
				$multiSelectErrorMessage = "Please select at least two datasets.";
				$this->set(compact('multiSelectErrorMessage','projectId','projects','datasets'));
				$this->render('add','ajax');
			}
		}
		
//		$libraries = $this->Population->Library->find('all',array('fields'=>array('id','name','description'),'conditions'=>array('project_id'=>$projectId)));
//		
//		foreach($libraries as $library) {
//			//define library label		
//			$datasets[$library['Library']['id']] = "{$library['Library']['name']} ({$library['Library']['description']})";
//		}
		
	
		$datasets = $this->Population->Library->find('list',array('conditions'=>array('project_id'=>$projectId)));
		$projects = $this->Population->Project->find('list');
		$this->set(compact('projectId','projects','datasets'));
	}

	/**
	 * Edit population
	 * 
	 * @param int $id population id
	 * @return void
	 * @access public
	 */		
	function edit($id = null) {
		$this->loadModel('Population');
		$this->Population->contain('Project','Library');
		
		if (!$id && empty($this->data)) {
			$this->flash(__('Invalid Population', true), array('action'=>'index'));
		}
		if (!empty($this->data)) {
			
			$projectId= $this->data['Population']['project_id'];
			#$this->LibraryPopulation->deleteAll($this->data, array( 'population_id' => $this->data['Population']['project_id']));
			
			if ($this->Population->save($this->data)) {
				
				//delete project view cache
				Cache::delete($projectId.'project');
				
				$this->Session->setFlash("Population changes have been saved.");
				$this->redirect("/projects/view/$projectId",null,true);
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

	/**
	 * Delete population
	 * 
	 * @param int $id population id
	 * @return void
	 * @access public
	 */		
	function delete($id = null) {
		$this->loadModel('Population');
		$this->Population->contain('Project');		
		
		if (!$id) {
			$this->flash(__('Invalid Population', true), array('action'=>'index'));
		}
		else {		
			$this->data = $this->Population->read(null, $id);
			$populationName = $this->data['Population']['name'];
			$projectId = $this->data['Project']['id'];
						
			if($this->Population->delete($id)) {
				//delete solr index
				$this->Solr->deleteIndex($this->data['Population']['name']);
				$this->Session->setFlash("Population changes have been saved.");
				
				//delete project view cache
				Cache::delete($projectId.'project');				
				
				$this->redirect("/projects/view/$projectId",null,true);
			}
		}
	}
}
?>