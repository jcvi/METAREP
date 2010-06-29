<?php
/***********************************************************
*  File: database.php
*  Description: configuration file for cakephp datasources
*
*  Author: jgoll
*  Date:   Feb 16, 2010
************************************************************/

class DATABASE_CONFIG {
	
	//METAREP database connection parameters
	var $default = array(
		'driver' => 'mysqli',
		'persistent' => true,
		'host' => 'mysql51-dmz-pro',
		'login' => 'ifx_mg_reports',
		'password' => 'mgano',
		'database' => 'ifx_metagenomics_reports',
	);
	
	//GO database connection parameters
	var $go = array(
		'driver' => 'mysqli',
		'persistent' => true,
		'host' => 'mysql51-dmz-pro',
		'login' => 'access',
		'password' => 'access',
		'database' => 'gene_ontology',
	);
	
	//GOS Blog connection parameters
	var $gosBlog = array(
		'datasource' => 'rss',
		'feedUrl' => 'http://blogs.jcvi.org/tag/gos/feed/',
		'encoding' => 'UTF-8',
		'cacheTime' => '+1 day',
	);	
}
?>