<?php
/***********************************************************
* File: population.php
* Description: Population Model
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
	
	public function getNameById($id) {
		$this->unbindModel(array('belongsTo' => array('Project'),),false);	
		$population = $this>findById($populationId);	
		return $population['Population']['name'];	
	} 
}
?>