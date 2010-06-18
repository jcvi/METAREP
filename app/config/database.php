<?php
/***********************************************************
*  File: database.php
*  Description:
*
*  Author: jgoll
*  Date:   Feb 16, 2010
************************************************************/
class DATABASE_CONFIG {
	
	var $default = array(
		'driver' => 'mysql',
		'persistent' => true,
		'host' => 'mysql51-dmz-pro',
		'login' => 'ifx_mg_reports',
		'password' => 'mgano',
		'database' => 'ifx_metagenomics_reports',
	);
	
	var $go = array(
		'driver' => 'mysql',
		'persistent' => true,
		'host' => 'mysql51-dmz-pro',
		'login' => 'access',
		'password' => 'access',
		'database' => 'gene_ontology',
	);
	
	var $gosBlog = array(
		'datasource' => 'rss',
		'feedUrl' => 'http://blogs.jcvi.org/tag/gos/feed/',
		'encoding' => 'UTF-8',
		'cacheTime' => '+1 day',
	);	
	
	var $keggSoap = array(
		'datasource' => 'soap',
		'wsdl' => 'http://soap.genome.jp/KEGG.wsdl',
		'location' => '',
		'uri' => '',
	);
		
//	var $solr = array(
//		'host' => 'metarep-prod1',
//		'port' => '8983',
//	);

//	var $default = array(
//		'driver' => 'sqlite3',
//	    'connect' =>'sqlite', 
//		'persistent' => false,
//		'host' => 'localhost',
//		'login' => '',
//		'password' => '',
//		//'database' => '/home/jhoover/test.db',
//		'database' => '/usr/local/annotation/METAGENOMIC/results/TEST/sqlite/ifx_metagenomics_reports_indexed',
//	);	
}
?>