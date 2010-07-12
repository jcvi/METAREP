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
* @version METAREP v 1.0.1
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

class PopulationsController extends AppController {

	var $helpers 	= array('Html', 'Form','Ajax');
	var $uses 		= array('Population','Library','Project');	
	var $components = array('Solr');

	/**
	 * Initializes index population page
	 * 
	 * @return void
	 * @access public
	 */		
	function index() {
		$this->Population->recursive = 1;
		$this->set('population', $this->paginate());
	}

	/**
	 * View population
	 * 
	 * @return void
	 * @access public
	 */	
	function view($id = null) {		
		if (!$id) {
			$this->flash(__('Invalid Population', true), array('action'=>'index'));
		}
		$this->set('population', $this->Population->read(null, $id));
	}

	/**
	 * View population after a new index has been created. Executed by ajax call.
	 * 
	 * @param int $id population id
	 * @return void
	 * @access public
	 */	
	function ajaxView($id = null) {
		
		if (!$id) {
			$this->flash(__('Invalid Population', true), array('action'=>'index'));
		}
		
		$this->set('population', $this->Population->read(null, $id));
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

	/**
	 * Edit population
	 * 
	 * @param int $id population id
	 * @return void
	 * @access public
	 */		
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

	/**
	 * Delete population
	 * 
	 * @param int $id population id
	 * @return void
	 * @access public
	 */		
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