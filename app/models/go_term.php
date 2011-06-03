<?php
/***********************************************************
* File: go_term.php
* Description: Go Term
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

class GoTerm extends AppModel {
	var $useDbConfig 	= 'go'; 
	var $name 			= 'GoTerm';
	var $useTable 		= 'term';
	var $primaryKey 	= 'id';
	
	function getIdQueryByName($name) {
		$results = $this->query("SELECT count(*) as hits,GROUP_CONCAT(DISTINCT CONCAT('go_id:',acc) separator ' OR ') as query,  GROUP_CONCAT(DISTINCT concat(acc,' ',name) separator '@') as suggestions FROM term WHERE name like '%$name%'");	
		$query = preg_replace('/[gG][oO]\:/i','GO\:',$results[0][0]['query']);
		$search['hits']  =  $results[0][0]['hits'];
		$search['query'] =  $query;
		$search['suggestions'] =  explode('@',$results[0][0]['suggestions']);		
		return $search;
	}

	function getTreeQueryByName($name) {
		$results = $this->query("SELECT count(*) as hits,GROUP_CONCAT(DISTINCT CONCAT('go_tree:',trim(LEADING '0' from replace(acc,'GO:',''))) separator ' OR ') as query,  GROUP_CONCAT(DISTINCT concat(acc,' ',name) separator '@') as suggestions FROM term WHERE name like '%$name%'");	
		$search['hits']  =  $results[0][0]['hits'];
		$search['query'] =  $results[0][0]['query'];
		$search['suggestions'] =  explode('@',$results[0][0]['suggestions']);		
		return $search;
	}	
}
?>