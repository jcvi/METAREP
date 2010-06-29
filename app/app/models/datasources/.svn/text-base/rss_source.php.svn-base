<?php
/**
 * RSS Feed Datasource
 *
 * Helps reading RSS feeds in CakePHP as if it were a model.
 *
 * PHP versions 4 and 5
 *
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2009, Loadsys Consulting, Inc. (http://www.loadsys.com)
 * @version       $1.0$
 * @modifiedby    $LastChangedBy: Donatas Kairys (Loadsys) $
 * @lastmodified  $Date: 2009-06-01$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::import('Core',  'Xml');

class RssSource extends DataSource {

	var $_baseConfig = array(
		'feedUrl' => false,
		'encoding' => 'UTF-8',
		'cacheTime' => '+1 day',
		'version' => '2.0',
		);

	function read(&$model, $queryData = array()) {
		$data = $this->__readData();

		$channel = Set::extract($data, 'Rss.Channel');
		if ( isset($channel['Item']) ) {
			unset($channel['Item']);
		}

		$items = Set::extract($data, 'Rss.Channel.Item');

		if ( $items ) {
			$items = $this->__filterItems($items, $queryData['conditions']);

			$items = $this->__sortItems($model, $items, $queryData['order']);

			//used for pagination
			$items = $this->__getPage($items, $queryData);
	
			//return item count
			if ( Set::extract($queryData, 'fields') == '__count' ) {
				return array(array($model->alias => array('count' => count($items))));
			}
		}
		else {
			if ( Set::extract($queryData, 'fields') == '__count' ) {
				return array(array($model->alias => array('count' => count($items))));
			}
		}

		/*$result = array(
			'Channel' => $channel,
			$model->alias => $items,
		);*/
		if(!empty($items)) {
			foreach($items as $item) {
				$item['Channel'] = $channel;
				$result[] = array($model->alias => $item);
			}
		}
		else {
			$result = null;
		}
		return $result;
	}

	function name($name) {
		return $name;
	}	

	function __readData() {
		$config = $this->config;
		$feedUrl = $config['feedUrl'];
		$cacheTime = $config['cacheTime'];

		$cachePath = 'rss_'.md5($feedUrl);
		$data = cache($cachePath, null, $cacheTime);
		if ( !$data ) {
			$data = Set::reverse(new XML($this->config['feedUrl'], array('version' => $this->config['version'], 'encoding' => $this->config['encoding'])));
			cache($cachePath, serialize($data));
		}
		else {
			$data = unserialize($data);
		}
		return $data;
	}
	
	function __filterItems($items = null, $conditions = null) {
		if (empty($items) || empty($conditions)) {
			return $items;
		}

		$filteredItems = array();        

		foreach ($items as $item) {
			foreach ($conditions as $field => $condition) {
				$itemPassedFilters = $this->__passesCondition($item[$field], $condition);
			}
			
			if ($itemPassedFilters==true) {
                		array_push($filteredItems, $item);
			}
        	}
		return $filteredItems;
	}

	function __passesCondition($field, $condition) {
        	return preg_match($condition, $field);
	}

	function __getPage($items = null, $queryData = array()) {
		if ( empty($queryData['limit']) ) {
			return $items;
		}

		$limit = $queryData['limit'];
		$page = $queryData['page'];

		$offset = $limit * ($page-1);

		return array_slice($items, $offset, $limit);
	}

	function __sortItems(&$model, $items, $order) {
		if ( empty($order) || empty($order[0]) ) {
			return $items;
		}

		$sorting = array();
		foreach( $order as $orderItem ) {
			if ( is_string($orderItem) ) {
				$field = $orderItem;
				$direction = 'asc';
			}
			else {
				foreach( $orderItem as $field => $direction ) {
					continue;
				}
			}

			$field = str_replace($model->alias.'.', '', $field);

			$values =  Set::extract($items, '{n}.'.$field);
			if ( in_array($field, array('lastBuildDate', 'pubDate')) ) {
				foreach($values as $i => $value) {
					$values[$i] = strtotime($value);
				}
			}
			$sorting[] = $values;
			
			switch(low($direction)) {
				case 'asc':
					$direction = SORT_ASC;
					break;
				case 'desc':
					$direction = SORT_DESC;
					break;	
				default:
					trigger_error('Invalid sorting direction '. low($direction));
			}
			$sorting[] = $direction; 
		}
		
		$sorting[] = &$items;
		$sorting[] = $direction; 
		call_user_func_array('array_multisort', $sorting);
//		array_multisort($sorting[0], $sorting[1], $items);
	
		return $items;
	}

	function calculate(&$model, $func, $params = array()) {
		return '__'.$func;
	}	
}

?>
