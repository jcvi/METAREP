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
* @version METAREP v 1.0.1
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
	
	var $recursive			 = 2;
	var $name 				 = 'Project';
	var $hasAndBelongsToMany = array('User');	
	var $hasMany 			 = array('Library' =>array('order' =>array('Library.name ASC','Library.sample_filter DESC')),
						 			'Population' =>array('order' =>'Population.name ASC'));
		
	public function getProjectName($dataset) {
		$library = $this->Library->findByName($dataset);
		$projectName = $library['Project']['name'];
		
		if(empty($projectName)) {
			$population = $this->Population->findByName($dataset);
			$projectName = $population['Project']['name'];
		}
		
		return $projectName;
	}   
	
	public function getProjectId($dataset) {
		$library = $this->Library->findByName($dataset);
		$projectId = $library['Project']['id'];
		
		if(empty($projectId)) {
			$population = $this->Population->findByName($dataset);
			$projectId = $population['Project']['id'];
		}
		
		return $projectId;
	}

	
	public function isProjectAdmin($projectId,$userId) {
		$this->Project->recursive=-1;
		$count= $this->find('count',array('conditions' => array('Project.user_id' => $userId,'Project.id'=>$projectId)));
		return $count;
	}	
	public function isProjectUser($projectId,$userId) {
		#$this->User->bindModel(array('hasAndBelongsToMany' => array('UserGroup'),),false);
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
	
	#checks if all datasets have a certain datatype assigned
	public function checkOptionalDatatypes($datasets) {
		$isPopulation	= true;
		$allViral	 	= true;
		$allHaveApis 	= true;
		$allHaveClusters = true;
		$allHaveFilters = true;
		
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
					if(!$result['Population']['has_filter']){
						$allHaveFilters=false;
					}					
				}
			}			
		}
		
		$datatypes['population']= $isPopulation;
		$datatypes['viral']		= $allViral;
		$datatypes['apis'] 		= $allHaveApis;
		$datatypes['clusters'] 	= $allHaveClusters;
		$datatypes['filter'] 	= $allHaveFilters;
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
		
		#adjust data model		
		$this->unbindModel(array('hasAndBelongsToMany' => array('User'),));
		$this->Population->unbindModel(array('belongsTo' => array('Project'),'hasAndBelongsToMany' => array('Library')));
		$this->Library->unbindModel(array('belongsTo' => array('Project'),'hasAndBelongsToMany' => array('Population')));
		$this->User->unbindModel(array('belongsTo' => array('UserGroup'),));
				
		#get current user information
		$currentUser	= Authsome::get();
		$currentUserId 	= $currentUser['User']['id'];	    	        	
        $userGroup  	= $currentUser['UserGroup']['name'];		
		   
		#return public projects for guest users
		if($userGroup === GUEST_USER_GROUP) {
			return $projects = $this->find('all', array('conditions' => array('is_public' => 1)));
		} 
		
		#return selective projects for external users
		if($userGroup === EXTERNAL_USER_GROUP) {
			$projects = array(); 
			
			$userProjects = $this->query("SELECT distinct Project.id as id FROM projects as Project LEFT JOIN projects_users as pu on(Project.id=pu.project_id) WHERE pu.user_id = $currentUserId OR Project.user_id = $currentUserId OR Project.is_public=1"); 

			foreach($userProjects as $userProject) {
				$project = $this->findById($userProject['Project']['id']);
				array_push($projects,$project);
			} 
			
			return $projects;    	
		}
		
		#return all project for admin and jcvi users
		if($userGroup === ADMIN_USER_GROUP || $userGroup === INTERNAL_USER_GROUP) {
			return $projects = $this->find('all',array('fields'=>array('Project.name')));
		}
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
		$this->Library->recursive = 2;

		$currentUser	= Authsome::get();
		$currentUserId 	= $currentUser['User']['id'];	
		$userGroup  	= $currentUser['UserGroup']['name'];

		if($userGroup === ADMIN_USER_GROUP || $userGroup === INTERNAL_USER_GROUP) {
			if(is_null($projectId)) {
				if($datasetType == POPULATION_AND_LIBRARY_DATASETS) {
					$query = "select datasets.name,datasets.description,datasets.project,datasets.project_id,datasets.type from (SELECT 'population' as type,populations.name as name, populations.description as description, projects.name as project,projects.id as project_id from populations INNER JOIN projects ON(projects.id=populations.project_id) UNION SELECT 'library' as type,libraries.name as name, libraries.description as description,projects.name as project,projects.id as project_id from libraries INNER JOIN projects ON(projects.id=libraries.project_id))  as datasets ORDER BY datasets.project ASC, datasets.name ASC";
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
			$allDatasets[$datasetName]=$result['datasets'];
		
		}	

		return $allDatasets;
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
				if($allDataset['description']) {
					$displayedDatasetDescription = substr($allDataset['description'], 0, 50)."...";				
					$displayName = "{$allDataset['project']} ({$allDataset['type']}:{$allDataset['name']} $displayedDatasetDescription)";
				}
				else {
					$displayName = "{$allDataset['project']} ({$allDataset['type']}:{$allDataset['name']})";
				}
				$allDatasets[$allDataset['name']] = $displayName;
		}	
		return $allDatasets;	
	}
}
?>