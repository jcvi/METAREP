<?php

/***********************************************************
*  File: project.php
*  Description: Model for project
*
*  Author: jgoll
*  Date:   Mar 16, 2010
************************************************************/

class Project extends AppModel {

	var $name = 'Project';
	
	#model associations
	var $hasAndBelongsToMany = array('User');
	
	var $hasMany = array('Library' =>array('order' =>array('Library.name ASC','Library.sample_filter DESC')),
						 'Population' =>array('order' =>'Population.name ASC'));
	
	var $recursive=2;
	
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
		if($userGroup === GUEST) {
			return $projects = $this->find('all', array('conditions' => array('is_public' => 1)));
		} 
		
		#return selective projects for normal users
		if($userGroup === USER_GROUP) {
			$projects = array(); 
			
			$userProjects = $this->query("SELECT distinct Project.id as id FROM projects as Project LEFT JOIN projects_users as pu on(Project.id=pu.project_id) WHERE pu.user_id = $currentUserId OR Project.user_id = $currentUserId OR Project.is_public=1"); 

			foreach($userProjects as $userProject) {
				$project = $this->findById($userProject['Project']['id']);
				array_push($projects,$project);
			} 
			
			return $projects;    	
		}
		
		#return all project for admin and jcvi users
		if($userGroup === ADMIN_USER_GROUP || $userGroup === JCVI_USER_GROUP) {
			return $projects = $this->find('all',array('fields'=>array('Project.name')));
		}
	}
	
	public function findUserDatasets() {
		$this->Library->recursive = 2;

		$currentUser	= Authsome::get();
		$currentUserId 	= $currentUser['User']['id'];	
		$userGroup  	= $currentUser['UserGroup']['name'];

		if($userGroup === ADMIN_USER_GROUP || $userGroup === JCVI_USER_GROUP) {
			
			$results = $this->query("select datasets.name,datasets.description,datasets.project,datasets.type from (SELECT 'population' as type,populations.name as name, populations.description as description, projects.name as project from populations INNER JOIN projects ON(projects.id=populations.project_id) UNION SELECT 'library' as type,libraries.name as name, libraries.description as description,projects.name as project  from libraries INNER JOIN projects ON(projects.id=libraries.project_id))  as datasets ORDER BY datasets.project ASC, datasets.name ASC"); 
		}
		else {
			$results = $this->query("SELECT datasets.name,datasets.description,datasets.project,datasets.type from
			 (SELECT populations.name as name, populations.description as description, projects.name as project,'population' as type from populations
			  INNER JOIN projects on(projects.id=populations.project_id) LEFT JOIN projects_users on(projects_users.project_id=projects.id)
			   where projects.user_id = $currentUserId OR projects_users.user_id = $currentUserId OR projects.is_public=1 UNION
			    SELECT libraries.name as name, libraries.description as description,projects.name as project,'library' as type from libraries
			     INNER JOIN projects on(projects.id=libraries.project_id) LEFT JOIN projects_users on(projects_users.project_id=projects.id)
			      where projects.user_id = $currentUserId OR projects_users.user_id = $currentUserId OR projects.is_public=1) as datasets
			      ORDER BY datasets.project ASC, datasets.name ASC"); 
		}
		
		foreach($results as $result) {
			$datasetName 		= $result['datasets']['name'];
			$datasetDescription = $result['datasets']['description'];
			$projectName 		= $result['datasets']['project'];			
			$type				= $result['datasets']['type'];		

			$displayName = "$projectName ($type:$datasetName $datasetDescription)";
			
//			if($datasetDescription) {
//				$displayName .= " ($datasetDescription)";
//			}
			
			$allDatasets[$datasetName]=$displayName;
		}	
		
		
		return $allDatasets;
	}
	
}
?>