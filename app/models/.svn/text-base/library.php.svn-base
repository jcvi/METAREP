<?php

/***********************************************************
*  File: library.php
*  Description:
*
*  Author: jgoll
*  Date:   May 6, 2010
************************************************************/

class Library extends AppModel {

	var $name = 'Library';
	
	//The Associations below have been created with all possible keys, those that are not needed can be removed
	var $belongsTo = array('Project');
	
	var $hasAndBelongsToMany = array('Population');
	
    var $validate = array(
        'sample_altitude' => array(
           		'numeric' => array(
      			'required' => false,
   				 'allowEmpty' => true,
    			'rule' => 'numeric',
                'message' => 'Please enter a numeric value.'
             ),          
        ),
        'sample_depth' => array(
           		 'numeric' => array(
          		'required' => false,
        		 'allowEmpty' => true,
        		'rule' => 'numeric',
                'message' => 'Please enter a numeric value.'
             ),          
        )      ,  
        'sample_latitude' => array(
            	'alphaNumeric' => array(
          		'required' => false,
         		'allowEmpty' => true,
                'rule' => "/^[0-9.]+.*[0-9.]+'[0-9.]+\"[WE]$/i", 
                'message' => 'Please enter latitude in the specified format.'
             ),          
        ),
        'sample_longitude' => array(
     		 'alphaNumeric' => array(
       			 'required' => false,
        		 'allowEmpty' => true,
                'rule' => "/^[0-9.]+.*[0-9.]+'[0-9.]+\"[NS]$/i", 
                'message' => 'Please enter latitude in the specified format.'
                )
        ), 
	);	
	
	
	public function getProjectName($dataset) {
		$library = $this->find('first', array('conditions' => array('Library.name' => $dataset)));
		return $library['Project']['name'];
	}	
}
?>