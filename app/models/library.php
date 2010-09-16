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
* @version METAREP v 1.2.0
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

class Library extends AppModel {
	var $name 				 = 'Library';
	var $belongsTo 			 = array('Project');	
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
		$this->contain('Project');
		$library = $this->find('first', array('conditions' => array('Library.name' => $dataset)));
		return $library['Project']['name'];
	}

	public function getNameById($id) {
		$library = $this>findById($id);	
		return $library['Library']['name'];	
	} 
}
?>