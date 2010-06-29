<?php 
/***********************************************************
*  File: baseModelComponent.php
*  Description: Helper class to allow to use models in components.
*
*  Author: jgoll
*  Date:   Feb 16, 2010
************************************************************/
class BaseModelComponent extends Object {
 
	var $uses = false;
 
	function initialize(&$controller) {
 
		//load required for component models
		if ($this->uses !== false) {
			foreach($this->uses as $modelClass) {
				$controller->loadModel($modelClass);
				$this->$modelClass = $controller->$modelClass;
			}
		}
 
	}
}
?>