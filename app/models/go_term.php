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
* @version METAREP v 1.0.1
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

class GoTerm extends AppModel {
	var $useDbConfig 	= 'go'; 
	var $name 			= 'GoTerm';
	var $useTable 		= 'term';
	var $primaryKey 	= 'id';
}
?>