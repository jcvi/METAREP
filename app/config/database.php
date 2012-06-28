<?php
/***********************************************************
* File: database.php
* Description: configuration file for METAREP datasources
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
* @version METAREP v 1.3.1
* @author Johannes Goll
* @lastmodified 2011-10-12
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

class DATABASE_CONFIG {
	
	//METAREP MySQL database connection parameters
	var $default = array(
		'driver' => 'mysqli',
		'persistent' => true,
		'host' => 'localhost',
		'login' => '<your-login>',
		'password' => '<your-password>',
		'database' => 'metarep',
	);
	
	//METAREP Blog connection parameters
	var $blog = array(
		'datasource' => 'rss',
		'feedUrl' => 'http://blogs.jcvi.org/tag/metarep/feed/',
		'encoding' => 'UTF-8',
		'cacheTime' => '+1 day',
	);	
}
?>
