<?php
/***********************************************************
* File: environmental_library.php
* Description: Environmental Library model (JCVI-only data model)
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

class Log extends AppModel {
	var $name 		= 'Log';
	var $useTable 	= 'log';
	var $primaryKey = 'log_id';	
	var $order 		= "Log.ts_update DESC";
}
?>