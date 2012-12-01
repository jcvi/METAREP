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
* @version METAREP v 1.4.0
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/
class Population extends AppModel {
	var $name   			= 'Population';
	var $belongsTo 			= array('Project');
    var $hasAndBelongsToMany= array('Library'); 
    	
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

	public function getProjectIdById($id) {
		$this->contain('Project.id');
		$population = $this->find('first', array('fields'=>array('Population.id'),'conditions' => array('Population.id' => $id)));
		return $population['Project']['id'];
	} 	
	
	public function getProjectName($dataset) {
		$this->contain('Project.name');
		$population = $this->find('first', array('fields'=>array('Population.id'),'conditions' => array('Population.name' => $dataset)));
		return $population['Project']['name'];
	} 

	public function getLibraries($dataset) {
		$libraries = array();
		$this->contain('Project.id','Library.name','Library.project_id');
		
		#$this->unbindModel(array('belongsTo' => array('Project'),));
		$population = $this->findByName($dataset);
		
		foreach($population['Library'] as $library) {
			array_push($libraries,$library['name']);
		}
		return $libraries;
	}
	
	public function getNameById($id) {
		$this->contain();
		$population = $this>findById($populationId);	
		return $population['Population']['name'];	
	} 
}
?>