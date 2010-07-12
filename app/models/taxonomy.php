<?php
/***********************************************************
* File: taxonomy.php
* Description: Taxonomy Model
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

class Taxonomy extends AppModel {

	var $name 		= 'taxonomy';
	var $primaryKey = 'taxon_id';
	var $useTable 	= 'taxonomy';

	public function findTopLevelTaxons() {
		return $this->find('all', array('conditions' => array('Taxonomy.taxon_id' => array(2157,2,2759,10239, 28384)),'fields' => array('taxon_id','name')));
	}
}
?>