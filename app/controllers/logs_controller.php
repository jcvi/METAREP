<?php
/***********************************************************
* File: logs_controller.php
* Description: Displaus JCVI's metagenomics annotation
* pipeline progress log (JCVI-only feature)
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

class LogsController extends AppController {
       
	function index() {       
	    $this->Log->recursive = 1;
		$this->set('logs', $this->paginate());
    }
}
?>
