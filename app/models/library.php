<?php

/***********************************************************
* File: library.php
* Description: library Model
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

class Library extends AppModel {
	var $name 				 = 'Library';
	var $belongsTo 			 = array('Project');	
	var $hasAndBelongsToMany = array('Population');
	
    var $validate = array(        
        'sample_id' => array(
           		'alphaNumeric' => array(
      				'required' => false,
   				 	'allowEmpty' => true,
    				'rule' => 'alphaNumeric',
    				'maxLength'=> 30, 
                	'message' => 'Please enter a alphanumeric value (max 30 characters).'
             ),          
        ),      
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
	    			'rule' => 'numeric',
	                'message' => 'Please enter a numeric value.'
             ),          
        ),
        'sample_longitude' => array(
     		 'alphaNumeric' => array(
       			 'required' => false,
        		 'allowEmpty' => true,
	    			'rule' => 'numeric',
	                'message' => 'Please enter a numeric value.'
                )
        ),       
        'label' => array(
            'alphaNumeric' => array(
                'rule' => '/^[a-z0-9_-]{0,30}$/i', 
       			'maxLength'=> 30,
        		'allowEmpty' => true,
                'message' => 'Alphabets, underscores and numbers only (max. 30 characters).',
             ),          
        	
         ), 	         
	);	
	
	public function getProjectIdById($id) {
		$this->contain('Project.id');
		$library = $this->find('first', array('fields'=>array('Library.id'),'conditions' => array('Library.name' => $id)));
		return $library['Project']['id'];
	}	
	
	public function getProjectName($dataset) {
		$this->contain('Project.name');
		$library = $this->find('first', array('conditions' => array('Library.name' => $dataset)));
		return $library['Project']['name'];
	}
	
	public function getNameById($id) {
		$library = $this>findById($id);	
		return $library['Library']['name'];	
	} 
}
?>