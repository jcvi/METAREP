<?php
/***********************************************************
* File: hmm.php
* Description: HMM Model
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

class Cluster extends AppModel {
	
	public function getDescription($clusterId) {		
		$result = $this->findByClusterId($clusterId);
		
		if($result) {
			$name = $result['Cluster']['name'];
			$percentCoverage = $result['Cluster']['percent_coverage'];
			$type = $result['Cluster']['db_type'];
			$id = $result['Cluster']['db_id'];	
			$p_value = $result['Cluster']['p_value'];	
			return "$clusterId | <B>$name</B> <BR><i>$percentCoverage% cluster coverage, $p_value p-value [$type:$id]</i>";
		}
		else {
			return $clusterId;
		}
	}
	
	function getClusterQueryByName($name) {
		//init results arrays
		$search 	= array();
		$clusterIds = array();
		$suggestions= array();
		
		$results = $this->find('all',array('fields'=> array('Cluster.cluster_id'),'conditions' => array('name LIKE' => "%$name%")));
				
		$hits = count($results);
		
		if($hits > 0) {
			foreach($results as $result) {
				$clusterId = $result['Cluster']['cluster_id'];
				$description = $this->getDescription($clusterId);
				array_push($clusterIds,$clusterId);
				array_push($suggestions,$description);
			}	
		}
		
		$query = 'cluster_id:'.implode(' OR cluster_id:',$clusterIds);
		
		$search['hits'] 	   =  $hits;
		$search['query']	   =  $query;
		$search['suggestions'] =  $suggestions;
		
		return $search;
	}	
}
?>