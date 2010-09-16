<?php
/***********************************************************
* File: enzyme.php
* Description: Enzyme Model
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

class Enzyme extends AppModel {
	var $primaryKey = 'ec_id';
	
	public function getIdQueryByName($name) {
		$results = $this->query("SELECT count(*) as hits,GROUP_CONCAT(DISTINCT CONCAT('ec_id:',ec_id) separator ' OR ') as query,  GROUP_CONCAT(DISTINCT concat(ec_id,' ',name) separator '@') as suggestions FROM enzymes WHERE name like '%$name%'");
		$search['hits']  =  $results[0][0]['hits'];
		$search['query'] =  $results[0][0]['query'];
		$search['suggestions'] =  explode('@',$results[0][0]['suggestions']);	
		return $search;		
	}	
}
?>