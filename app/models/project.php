<?php
/***********************************************************
* File: project.php
* Description: Project Model
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

define('COMPARE_FORMAT', 0);
define('SEARCH_FORMAT', 1);


define('POPULATION_AND_LIBRARY_DATASETS',0);
define('LIBRARY_DATASETS',1);
define('POPULATION_DATASETS',2);

class Project extends AppModel {
	
	var $name 				 = 'Project';
	var $hasAndBelongsToMany = array('User');	
	var $hasMany 			 = array('Library' =>array('order' =>array('Library.name ASC','Library.sample_filter DESC')),
						 			 'Population' =>array('order' =>'Population.name ASC'));
		
	public function getProjectName($dataset) {		
		$this->Library->contain('Project.name');
		$library = $this->Library->findByName($dataset);
		$projectName = $library['Project']['name'];
		
		if(empty($projectName)) {
			$this->Population->contain('Project.name');
			$population = $this->Population->findByName($dataset);
			$projectName = $population['Project']['name'];
		}
		
		return $projectName;
	}   
	 
	public function getProjectNameById($id) {
		$project = $this->findById($id);
		return $project['Project']['name'];
	}
	
	public function getProjectId($dataset) {
		$this->contain('Library.project_id');
		
		$library = $this->Library->findByName($dataset);
		$projectId = $library['Library']['project_id'];
		
		if(empty($projectId)) {
			$this->contain('Population.project_id');
			$population = $this->Population->findByName($dataset);
			$projectId = $population['Population']['project_id'];
		}
		
		return $projectId;
	}

	
	public function isProjectAdmin($projectId,$userId) {
		$count = $this->find('count',array('conditions' => array('Project.user_id' => $userId,'Project.id'=>$projectId)));
		return $count;
	}	
	public function isProjectUser($projectId,$userId) {
		$this->contain('ProjectsUser');
		return $this->User->ProjectsUser->find('count',array('conditions' => array('project_id'=>$projectId,'user_id'=>$userId)));
	}
	public function isPublicProject($projectId) {
		return $this->find('count',array('conditions' => array('Project.id'=>$projectId,'Project.is_public'=>1)));
	}
	public function hasProjectAccess($projectId,$userId) {
		if($this->isProjectAdmin($projectId,$userId) || $this->isProjectUser($projectId,$userId) || $this->isPublicProject($projectId)) {
			return true;
		}
		else {
			return false;
		}
	}	
	public function hasDatasetAccess($dataset,$userId) {
		if($this->Population->findByName($dataset)) {
			$population = $this->Population->findByName($dataset);
			$projectId = $population['Population']['project_id'];
		}
		else {
			$library	= $this->Library->findByName($dataset);
			$projectId = $library['Library']['project_id'];
		}
		if($projectId) {							
			if($this->hasProjectAccess($projectId,$userId)) {
				return true;
			}	
		}
		return false;					
	}
	
	public function isPopulation($dataset) {
		if($this->Population->findByName($dataset)) {
			return 1;
		}
		else {
			return 0;
		}			
	}
		
	public function isWeighted($dataset) {
		if($this->Population->findByName($dataset)) {
			$population = $this->Population->findByName($dataset);
			$isWeighted= $population['Population']['is_weighted'];
		}
		else {
			$library	= $this->Library->findByName($dataset);
			$isWeighted = $library['Library']['is_weighted'];
		}		
		return $isWeighted;					
	}	
	
	public function getPipeline($dataset) {
		if($this->Population->findByName($dataset)) {
			$population = $this->Population->findByName($dataset);
			$pipeline = $population['Population']['pipeline'];
		}
		else {
			$library	= $this->Library->findByName($dataset);
			$pipeline = $library['Library']['pipeline'];
		}		
		return $pipeline;					
	}	

	public function populationsToDatasets($datasets) {
		$allDatasets = array();
			
		if(sizeof($datasets) == 1 && !is_array($datasets)) {
			$datasets = array($datasets);
		}
		
		foreach($datasets as $dataset) {
			if($this->isPopulation($dataset)) {
				$populationDatasets = $this->Population->getLibraries($dataset);
				foreach($populationDatasets as $populationDataset) {
					$allDatasets[$populationDataset] = '';
				}
			}
			else {	
				
				$allDatasets[$dataset] = '';
			}
		}
		
		return array_keys($allDatasets);		
	}	
	
	public function checkDatasetPipelines($datasets) {
		
		$datasetPipelines[PIPELINE_DEFAULT]			= true;
		$datasetPipelines[PIPELINE_JCVI_META_PROK]	= true;
		$datasetPipelines[PIPELINE_JCVI_META_VIRAL]	= true;
		$datasetPipelines[PIPELINE_HUMANN]			= true;
				
		foreach($datasets as $dataset) {		
			$result = $this->Library->findByName($dataset);
						
			if(!empty($result)) {	
				if($result['Library']['pipeline'] != PIPELINE_DEFAULT){
					$datasetPipelines[PIPELINE_DEFAULT]	= false;		
				}	
				if($result['Library']['pipeline'] != PIPELINE_HUMANN){
					$datasetPipelines[PIPELINE_HUMANN] = false;		
				}	
				if($result['Library']['pipeline'] != PIPELINE_JCVI_META_PROK){
					$datasetPipelines[PIPELINE_JCVI_META_PROK]	= false;
				}			
				if($result['Library']['pipeline'] != PIPELINE_JCVI_META_VIRAL){
					$datasetPipelines[PIPELINE_JCVI_META_VIRAL]	= false;
				}														
			}
			else {
				$result = $this->Population->findByName($dataset);
				
				if($result['Population']['pipeline'] != PIPELINE_DEFAULT){
					$datasetPipelines[PIPELINE_DEFAULT]	= false;		
				}	
				if($result['Population']['pipeline'] != PIPELINE_HUMANN){
					$datasetPipelines[PIPELINE_HUMANN] = false;		
				}	
				if($result['Population']['pipeline'] != PIPELINE_JCVI_META_PROK){
					$datasetPipelines[PIPELINE_JCVI_META_PROK]	= false;
				}			
				if($result['Population']['pipeline'] != PIPELINE_JCVI_META_VIRAL){
					$datasetPipelines[PIPELINE_JCVI_META_VIRAL]	= false;
				}		
			}			
		}
		
		return $datasetPipelines;
	}	
	
	#checks if all datasets have a certain datatype assigned
	public function checkOptionalDatatypes($datasets) {
		$isPopulation	 = true;
		$allViral	 	 = true;
		$allHaveApis 	 = true;
		$allHaveClusters = true;
		$allHaveFilters  = true;
		$allAreWeighted  = true;
		$allAreHumann 	 = true;
		$allHaveSequence = true;
		$allHaveKo = true;
		
		foreach($datasets as $dataset) {		
			$result = $this->Library->findByName($dataset);
			
			if(!empty($result)) {

				$isPopulation = false;
				
				if(empty($result['Library']['is_viral'])){					
					$allViral=false;					
				}
				if(empty($result['Library']['apis_database'])){					
					$allHaveApis=false;					
				}
				if(empty($result['Library']['cluster_file'])){
					$allHaveClusters=false;					
				}
				if(empty($result['Library']['filter_file'])){
					$allHaveFilters=false;					
				}
				if(empty($result['Library']['has_ko'])){
					$allHaveKo =false;					
				}					
				if(empty($result['Library']['is_weighted'])){
					$allAreWeighted=false;					
				}								
				if($result['Library']['pipeline'] != 'HUMANN'){
					$allAreHumann=false;					
				}	
				if(empty($result['Library']['has_sequence'])){
					$allHaveSequence=false;					
				}					
			}
			else {
				$result = $this->Population->findByName($dataset);
				
				
				if(!empty($result)) {
					
					if(!$result['Population']['is_viral']){					
						$allViral=false;					
					}				
					if(!$result['Population']['has_apis']){
						$allHaveApis=false;
					}
					if(!$result['Population']['has_clusters']){
						$allHaveClusters=false;
					}
					if(!$result['Population']['has_ko']){
						$allHaveKo =false;				
					}					
					if(!$result['Population']['has_filter']){
						$allHaveFilters=false;
					}	
					if(empty($result['Population']['is_weighted'])){
						$allAreWeighted=false;					
					}	
					if($result['Population']['pipeline'] != 'HUMANN'){
						$allAreHumann=false;					
					}
					if(!$result['Population']['has_sequence']){					
						$allHaveSequence=false;					
					}																				
				}
			}			
		}
		
		$datatypes['population']= $isPopulation;
		$datatypes['viral']		= $allViral;
		$datatypes['apis'] 		= $allHaveApis;
		$datatypes['clusters'] 	= $allHaveClusters;
		$datatypes['ko'] 		= $allHaveKo;
		$datatypes['filter'] 	= $allHaveFilters;
		$datatypes['weighted'] 	= $allAreWeighted;
		$datatypes['humann'] 	= $allAreHumann;
		$datatypes['sequence'] 	= $allHaveSequence;
		
		return $datatypes;
	}
	
	
	public function isDatasetAdmin($dataset,$userId) {
		if($this->Population->findByName($dataset)) {
			$population = $this->Population->findByName($dataset);
			$projectId = $population['Population']['project_id'];
		}
		else {
			$library	= $this->Library->findByName($dataset);
			$projectId = $library['Library']['project_id'];
		}
		if($projectId) {								
			if($this->isProjectAdmin($projectId,$userId)) {
				return true;
			}	
		}
		return false;					
	}	
	
	
	#returns projects depending on permissions
	public function findUserProjects() {
		
		$currentUser	= Authsome::get();
		$currentUserId 	= $currentUser['User']['id'];	    	        	
        $userGroup  	= $currentUser['UserGroup']['name'];			
		
		//do not query database if results have been cached
		if(($userProjects = Cache::read($currentUserId.'projects')) === false) {
			
			$this->contain('Population.id','Population.project_id','Population.name','Population.has_apis','Population.has_ko','Population.has_sequence',
						   'Library.id','Library.project_id','Library.name','Library.apis_database','Library.apis_dataset','Library.has_ftp','Library.is_weighted','Library.has_ko','Library.has_sequence');	  
	
			//return all project for admin and jcvi users
			if($userGroup === ADMIN_USER_GROUP || $userGroup === INTERNAL_USER_GROUP) {
				$userProjects = $this->find('all',array('fields'=>array('Project.name')));
			}
						   
			//return public projects for guest users
			else if($userGroup === GUEST_USER_GROUP) {		
				$userProjects = $this->find('all', array('conditions' => array('is_public' => 1)));
			} 
			//return selective projects for external users
			else if($userGroup === EXTERNAL_USER_GROUP) {
				$userProjects = array(); 
				
				$results = $this->query("SELECT distinct Project.id as id FROM projects as Project LEFT JOIN projects_users as pu on(Project.id=pu.project_id) WHERE pu.user_id = $currentUserId OR Project.user_id = $currentUserId OR Project.is_public=1"); 
				
				foreach($results as $result) {	
					
					$this->contain('Population.id','Population.project_id','Population.name','Population.has_apis',
						   'Library.id','Library.project_id','Library.name','Library.apis_database','Library.apis_dataset','Library.has_ftp','Library.is_weighted','Library.has_ko');	  
					
					$projectId = $result['Project']['id'];
					$project = $this->findById($projectId);
					array_push($userProjects,$project);
				} 
			}

			Cache::write($currentUserId.'projects', $userProjects);
		}
		
		return $userProjects;
	}
	
	/**
	* Returns associative array with datasets as keys (those for which
	* the logged in user has permissions and associative information
	* as values
	* 
	* @param int $datasetType 0 all datasets, 1 only libraries, 2 only populations
	* @param int $projectId restrict returned datasets to a certain project
	* @return Array associative array with datasets as keys and associative information
	* as values
	* @access public
	*/	
	public function findUserDatasets($datasetType = POPULATION_AND_LIBRARY_DATASETS,$projectId=null) {		

		$userDatasets = array();
		
		$currentUser	= Authsome::get();
		$currentUserId 	= $currentUser['User']['id'];	
		$userGroup  	= $currentUser['UserGroup']['name'];	
		
		//check if chached
		if (($userDatasets = Cache::read($currentUserId.$projectId.'userDatasets')) === false) {	
			
			if($userGroup === ADMIN_USER_GROUP || $userGroup === INTERNAL_USER_GROUP) {				
				if(is_null($projectId)) {
					if($datasetType == POPULATION_AND_LIBRARY_DATASETS) {
						$query = "select datasets.name,datasets.description,datasets.project,datasets.project_id,datasets.type from
						 (SELECT 'population' as type,populations.name as name, populations.description as description, projects.name
						  as project,projects.id as project_id from populations INNER JOIN projects ON(projects.id=populations.project_id)
						   UNION SELECT 'library' as type,libraries.name as name, libraries.description as description,projects.name as project,
						   projects.id as project_id from libraries INNER JOIN projects ON(projects.id=libraries.project_id)) 
						    as datasets ORDER BY datasets.project ASC, datasets.name ASC";
					}
					else if($datasetType == LIBRARY_DATASETS) {
						$query = "select datasets.name,datasets.description,datasets.project,datasets.project_id,datasets.type from (SELECT 'library' as type,libraries.name as name, libraries.description as description,projects.name as project,projects.id as project_id from libraries INNER JOIN projects ON(projects.id=libraries.project_id))  as datasets ORDER BY datasets.project ASC, datasets.name ASC";
					}
					else if($datasetType == POPULATION_DATASETS) {		
						$query = "select datasets.name,datasets.description,datasets.project,datasets.project_id,datasets.type from (SELECT 'population' as type,populations.name as name, populations.description as description, projects.name as project,projects.id as project_id from populations INNER JOIN projects ON(projects.id=populations.project_id)) as datasets ORDER BY datasets.project ASC, datasets.name ASC";					
					}
				}
				else {
					if($datasetType == POPULATION_AND_LIBRARY_DATASETS) {
						$query = "select datasets.name,datasets.description,datasets.project,datasets.project_id,datasets.type from (SELECT 'population' as type,populations.name as name, populations.description as description, projects.name as project,projects.id as project_id from populations INNER JOIN projects ON(projects.id=populations.project_id) where projects.id={$projectId} UNION SELECT 'library' as type,libraries.name as name, libraries.description as description,projects.name as project,projects.id as project_id from libraries INNER JOIN projects ON(projects.id=libraries.project_id) where projects.id={$projectId})  as datasets ORDER BY datasets.project ASC, datasets.name ASC"; 
					}
					else if($datasetType == LIBRARY_DATASETS) {
						$query = "select datasets.name,datasets.description,datasets.project,datasets.project_id,datasets.type from (SELECT 'library' as type,libraries.name as name, libraries.description as description,projects.name as project,projects.id as project_id from libraries INNER JOIN projects ON(projects.id=libraries.project_id) where projects.id={$projectId})  as datasets ORDER BY datasets.project ASC, datasets.name ASC"; 
					}	
					else if($datasetType == POPULATION_DATASETS) {		
						$query = "select datasets.name,datasets.description,datasets.project,datasets.project_id,datasets.type from (SELECT 'population' as type,populations.name as name, populations.description as description, projects.name as project,projects.id as project_id from populations INNER JOIN projects ON(projects.id=populations.project_id))  as datasets ORDER BY datasets.project ASC, datasets.name ASC";
					}	
				}
			}
			else {
				if(is_null($projectId)) {
					if($datasetType == POPULATION_AND_LIBRARY_DATASETS) {
						$query = "SELECT datasets.name,datasets.description,datasets.project,datasets.project_id,datasets.type from
					 	(SELECT populations.name as name, populations.description as description, projects.name as project,projects.id as project_id,'population' as type from populations
					 	INNER JOIN projects on(projects.id=populations.project_id) LEFT JOIN projects_users on(projects_users.project_id=projects.id)
					   	where projects.user_id = $currentUserId OR projects_users.user_id = $currentUserId OR projects.is_public=1 UNION
					    SELECT libraries.name as name, libraries.description as description,projects.name as project,projects.id as project_id,'library' as type from libraries
					    INNER JOIN projects on(projects.id=libraries.project_id) LEFT JOIN projects_users on(projects_users.project_id=projects.id)
					    where projects.user_id = $currentUserId OR projects_users.user_id = $currentUserId OR projects.is_public=1) as datasets
					    ORDER BY datasets.project ASC, datasets.name ASC"; 
					}
					else if($datasetType == LIBRARY_DATASETS) {		
						$query = "SELECT datasets.name,datasets.description,datasets.project,datasets.project_id,datasets.type from
					 	(SELECT libraries.name as name, libraries.description as description,projects.name as project,projects.id as project_id,'library' as type from libraries
					    INNER JOIN projects on(projects.id=libraries.project_id) LEFT JOIN projects_users on(projects_users.project_id=projects.id)
					    where projects.user_id = $currentUserId OR projects_users.user_id = $currentUserId OR projects.is_public=1) as datasets
					    ORDER BY datasets.project ASC, datasets.name ASC"; 					
					}	
					else if($datasetType == POPULATION_DATASETS) {		
						$query = "SELECT datasets.name,datasets.description,datasets.project,datasets.project_id,datasets.type from
					 	(SELECT populations.name as name, populations.description as description, projects.name as project,projects.id as project_id,'population' as type from populations
					 	INNER JOIN projects on(projects.id=populations.project_id) LEFT JOIN projects_users on(projects_users.project_id=projects.id)
					   	where projects.user_id = $currentUserId OR projects_users.user_id = $currentUserId OR projects.is_public=1) as datasets
					    ORDER BY datasets.project ASC, datasets.name ASC"; 					
					}	
				}
				else {
					if($datasetType == POPULATION_AND_LIBRARY_DATASETS) {
						$query = "SELECT datasets.name,datasets.description,datasets.project,datasets.project_id,datasets.type from
					 	(SELECT populations.name as name, populations.description as description, projects.name as project,projects.id as project_id,'population' as type from populations
					  	INNER JOIN projects on(projects.id=populations.project_id) LEFT JOIN projects_users on(projects_users.project_id=projects.id)
					   	where projects.id={$projectId} AND (projects.user_id = $currentUserId OR projects_users.user_id = $currentUserId OR projects.is_public=1) UNION
					    SELECT libraries.name as name, libraries.description as description,projects.name as project,projects.id as project_id,'library' as type from libraries
					    INNER JOIN projects on(projects.id=libraries.project_id) LEFT JOIN projects_users on(projects_users.project_id=projects.id)
					    where projects.id={$projectId} AND (projects.user_id = $currentUserId OR projects_users.user_id = $currentUserId OR projects.is_public=1)) as datasets
					    ORDER BY datasets.project ASC, datasets.name ASC";
					}
					else if($datasetType == LIBRARY_DATASETS) {		
						$query = "SELECT datasets.name,datasets.description,datasets.project,datasets.project_id,datasets.type from
					 	(SELECT libraries.name as name, libraries.description as description,projects.name as project,projects.id as project_id,'library' as type from libraries
					    INNER JOIN projects on(projects.id=libraries.project_id) LEFT JOIN projects_users on(projects_users.project_id=projects.id)
					    where projects.id={$projectId} AND (projects.user_id = $currentUserId OR projects_users.user_id = $currentUserId OR projects.is_public=1)) as datasets
					    ORDER BY datasets.project ASC, datasets.name ASC";					
					}
					else if($datasetType == POPULATION_DATASETS) {		
						$query = "SELECT datasets.name,datasets.description,datasets.project,datasets.project_id,datasets.type from
					 	(SELECT populations.name as name, populations.description as description, projects.name as project,projects.id as project_id, 'population' as type from populations
					  	INNER JOIN projects on(projects.id=populations.project_id) LEFT JOIN projects_users on(projects_users.project_id=projects.id)
					   	where projects.id={$projectId} AND (projects.user_id = $currentUserId OR projects_users.user_id = $currentUserId OR projects.is_public=1)) as datasets
					    ORDER BY datasets.project ASC, datasets.name ASC";				
					}				
				}
			}

			$results = $this->query($query);
			
			foreach($results as $result) {	
				$datasetName = $result['datasets']['name'];
				$userDatasets[$datasetName]=$result['datasets'];
			
			}
			//cache query results
			Cache::write($currentUserId.$projectId.'userDatasets', $userDatasets);
		}	

		return $userDatasets;
	}
	
	/**
	* Returns associative array with datasets as keys (those for which
	* the logged in user has permissions and a special display string 
	* formmated for the compare multi-select box 
	* project (type:library description)
	* 
	* @param int $datasetType 0 all datasets, 1 only libraries, 2 only populations
	* @param int $projectId restrict returned datasets to a certain project
	* @return Array associative array with datasets as keys and formatted dataset
	* display strings as values
	* @access public
	*/			
	public function findUserDatasetsCompareFormat($datasetType = POPULATION_AND_LIBRARY_DATASETS,$projectId=null) {
		
		$allDatasets = $this->findUserDatasets($datasetType,$projectId);
		
		foreach($allDatasets as $allDataset) {
			
//				if($allDataset['label']) {
//					$displayedDatasetDescription = substr($allDataset['label'], 0, 50)."...";		
//					
//					if(is_null($projectId)){
//						$displayName = "{$allDataset['project']} ({$allDataset['type']}:{$allDataset['name']} $displayedDatasetDescription)";
//					}
//					else{
//						$displayName = "{$allDataset['type']}:{$allDataset['name']} $displayedDatasetDescription";
//					}
//				}
				if($allDataset['description']) {
					$displayedDatasetDescription = substr($allDataset['description'], 0, 50)."...";		
					
					if(is_null($projectId)){
						$displayName = "{$allDataset['project']} ({$allDataset['type']}:{$allDataset['name']} $displayedDatasetDescription)";
					}
					else{
						$displayName = "{$allDataset['type']}:{$allDataset['name']} $displayedDatasetDescription";
					}
				}
				else {
					if(is_null($projectId))	{
						$displayName = "{$allDataset['project']} ({$allDataset['type']}:{$allDataset['name']})";
					}
					else {
						$displayName = "{$allDataset['type']}:{$allDataset['name']}";
					}
				}
				$allDatasets[$allDataset['name']] = $displayName;
		}	
		return $allDatasets;	
	}	
	
	public function getDatasetLabel($dataset,$labelType) {
		if($this->Library->findByName($dataset)) {
			$results = $this->query("SELECT replace(if($labelType = '',name,$labelType),' ','_') AS name FROM libraries WHERE name ='$dataset'");
			return $results[0][0]['name'];
		}
		else {
			return $dataset;
		}						
	}
}
?>