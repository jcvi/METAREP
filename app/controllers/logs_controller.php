<?php
    class LogsController extends AppController
    {
        #var $scaffold;
        function index() {
        	
        	$this->Log->recursive = 1;
			//$this->Library->unbindModel(array('hasMany' => array('Read')));
		
			$this->set('logs', $this->paginate());
        }
    }
?>
