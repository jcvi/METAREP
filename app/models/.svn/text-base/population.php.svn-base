<?php
/***********************************************************
*  File: population.php
*  Description: Model for populations
*
*  Author: jgoll
*  Date:   Mar 16, 2010
************************************************************/

class Population extends AppModel {

	var $name = 'Population';
	
    var $validate = array(
        'name' => array(
    		'notEmpty' =>array('rule'=>'notEmpty','message' => 'Please enter a name'),
            'alphaNumeric' => array(
                'rule' => '/^[a-z0-9_-]{1,30}$/i', 
                'required' => true,
                'message' => 'Alphabets, underscores and numbers only (max. 30 characters).'
             ),          
            'unique' => array('rule'=>array('isUnique','name'),'message' => 'Name already exist'),
        ),
        'description' => array(
    		'notEmpty' =>array('rule'=>'notEmpty','message' => 'Please enter a description'),
         ),
	);
		
	//The Associations below have been created with all possible keys, those that are not needed can be removed
	var $belongsTo = array('Project');

	#a population has many datasets
    var $hasAndBelongsToMany = array('Library');   

	public function getProjectName($dataset) {
		$population = $this->find('first', array('conditions' => array('Population.name' => $dataset)));
		return $population['Project']['name'];
	} 

	public function getLibraries($dataset) {
		$libraries = array();
		$this->unbindModel(array('belongsTo' => array('Project'),));
		$population = $this->findByName($dataset);
		
		foreach($population['Library'] as $library) {
			array_push($libraries,$library['name']);
		}
		return $libraries;
	}
}
?>