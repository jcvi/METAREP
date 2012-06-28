<?php
/***********************************************************
* File: user.php
* Description: Application Model - parent class of all model classes
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
* @lastmodified 2010-08-26
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/



class AppModel extends Model {	
	public $recursive 	= -1;	
	public $actsAs 		= array('Containable');
	
	//overrrides paginate function to cache paginated views
//	function paginate ($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
//			$args = func_get_args();
//			$uniqueCacheId = '';
//			foreach ($args as $arg) {
//				$uniqueCacheId .= serialize($arg);
//			}
//			if (!empty($extra['contain'])) {
//				$contain = $extra['contain'];
//			}
//			$uniqueCacheId = md5($uniqueCacheId);
//			$pagination = Cache::read('pagination-'.$this->alias.'-'.$uniqueCacheId);
//			if (empty($pagination)) {
//				$pagination = $this->find('all', compact('conditions', 'fields', 'order', 'limit', 'page', 'recursive', 'group', 'contain'));
//				Cache::write('pagination-'.$this->alias.'-'.$uniqueCacheId, $pagination, 'paginate_cache');
//			}
//			return $pagination;
//		}
//	
//		function paginateCount ($conditions = null, $recursive = 0, $extra = array()) {
//			$args = func_get_args();
//			$uniqueCacheId = '';
//			foreach ($args as $arg) {
//				$uniqueCacheId .= serialize($arg);
//			}
//			$uniqueCacheId = md5($uniqueCacheId);
//			if (!empty($extra['contain'])) {
//				$contain = $extra['contain'];
//			}
//	
//			$paginationcount = Cache::read('paginationcount-'.$this->alias.'-'.$uniqueCacheId, 'paginate_cache');
//			if (empty($paginationcount)) {
//				$paginationcount = $this->find('count', compact('conditions', 'contain', 'recursive'));
//				Cache::write('paginationcount-'.$this->alias.'-'.$uniqueCacheId, $paginationcount);
//			}
//			return $paginationcount;
//		}
}
?>