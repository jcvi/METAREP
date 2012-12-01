<?php
/***********************************************************
* File: metarep.php
* Description: METAREP configuration file
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

/**
 * METAREP Version
 * 
 */

define('METAREP_VERSION','1.4.0-beta');

/**
 * METAREP Running Title
 * 
 * customize your METAREP application title. It is used by Browser as the window title,
 * the default web layout uses it besides the METAEP logo. The title
 * is used at various other places throughout the application.
 */

define('METAREP_RUNNING_TITLE','JCVI Metagenomics Reports');

/**
 * METAREP Web Root
 * 
 * Point this variable to your Apache METAREP webroot directory
 * Default: /<your-installation-dir>/apache-2.2.14/htdocs/metarep
 */

define('METAREP_WEB_ROOT','/opt/www/metarep/htdocs/metarep');

/**
 * METAREP Url Root
 * 
 */

define('METAREP_URL_ROOT','http://www.jcvi.org/metarep');

/**
 * Directory to store temporary files
 * 
 * Temporary files include CAKEPHP cache/application and R files
 * Default: /tmp
 */

define('METAREP_TMP_DIR','/opt/www/metarep/tmp');

/**
 * Solr home dir
 * 
 * Contains Solr example data including <solr-home>/example/exampledocs/post.jar
 */

define('SOLR_HOME_DIR','/opt/software/apache-solr/solr');

/**
 * Solr instance dir
 * 
 * Contains Solr configuration files in conf/  subdirectory
 * Default: /your-installation-dir>/apache-solr-1.4.0/metarep-solr
 */

define('SOLR_INSTANCE_DIR','/opt/software/apache-solr/solr');

/**
 * Solr port
 * 
 * Defines the Solr port
 * Default: 1234
 */

define('SOLR_PORT','8989');

/**
 * Track Solr Qtime  
 * 
 * Track Solr query performance statistics in solr_Qtime table.
 * Should be set to 0 in production environment.
 */

define('SOLR_TRACK_QTIME',0);

/**
 * Solr data dir
 * 
 * Defines location of Solr index files
 * Default: /<your-installation-dir>/apache-solr-1.4.0/metarep-solr/data/
 */

define('SOLR_DATA_DIR','/solr-index');

/**
 * Solr master server host
 * 
 * Takes on role of the Solr master server in a 
 * load balanced/replication set-up.
 * Default: localhost
 */

define('SOLR_MASTER_HOST','172.20.13.24');

/**
 * Solr slave server host
 * 
 * Define the Solr slave host if you use METAREP
 * in a load balanced/replication set-up
 */

define('SOLR_SLAVE_HOST','172.20.13.25');


/**
* Maximum number of shards to use distributed
* searches
*/

define('SOLR_NUM_MAX_WEIGHTED_SHARDS',32);


/**
 * METAREP SQLite database 
 * 
 * Define the location of the METAREP SQLite
 * database on the server to allow users to
 * upload and index data.
 */

define('METAREP_SQLITE_DB_PATH','/usr/local/projects/DB/MGX/mgx-metarep/current/metarep.sqlite3.db');


/**
 * FTP host
 * 
 * Specify FTP host if you like to provide 
 * additional data for your METAREP dataset
 */

define('FTP_HOST','ftp.jcvi.org');

/**
 * FTP suser name 
 */

define('FTP_USERNAME','metarepftp');

/**
 * FTP password
 */

define('FTP_PASSWORD','P7ALdDVM');

/**
 * Email to send bug reports and feature requests. 
 * 
 * Email is displayed if METAREP can not access the Solr or
 * MySQL servers. It is also used to provide users an Email
 * address send bug reports and feature requests.
 */

define('METAREP_SUPPORT_EMAIL','metarep-support@jcvi.org');

/**
 * Internal Email Extension
 * 
 * METAREP distinguishes between four types of users: 
 * ADMIN, INTERNAL, EXTERNAL, and PUBLIC. 
 * 
 * ADMIN and INTERNAL users can access all METAREP datasets, while 
 * EXTERNAL and PUBLIC have restricted access. The variable defines
 * the Email extension that is used to identify INTERNAL users. This
 * is especially helpful if you like to grant dataset access to all
 * users of your institution - just specify your institute’s email
 * extension, e.g. jcvi.org for the J. Craig Venter Institute.
 */

define('INTERNAL_EMAIL_EXTENSION','jcvi.org');

/**
 * Google Analytics Tracker ID
 * 
*/

define('GOOGLE_ANALYTICS_TRACKER_ID','UA-9809410-3');

/**
 * Google Analytics Domain Name
 * 
*/

define('GOOGLE_ANALYTICS_DOMAIN_NAME','.jcvi.org');

/**
 * PHP HTTP transport implementation for retrieving Solr responses. 
 * Two cCURL implementations (CURL_REUSE,CURL_NO_REUSE) and one implementation based on
 * file_get_contents (FILE_GET_CONTENTS) can be specified. For the curl implemention
 * the PHP cCURL module has to be installed.
 */

define('PHP_HTTP_TRANSPORT','CURL_NO_REUSE');

/**
 * Number of Top Facet Counts
 * 
 * The METAREP search and browse pages summarize annotation data
 * types in the form of sorted top ten lists. Change this variable
 * to increase/decrease the number of top hits shown for each data type.
 * Default: 10
 */

define('NUM_TOP_FACET_COUNTS',10);

/**
 * Number of Search Results
 * 
 * The METAREP search page displays pages of found annotation results. 
 * By default, ten hits are shown per page. Change this variable to 
 * increase/decrease the number of results that are shown for each 
 * result page.
 * Default: 10
 */

define('NUM_SEARCH_RESULTS',10);

/**
 * Number of View Results
 * 
 * The METAREP view page displays on the first tab
 * a list of data entries. Specifiy  how many are shown 
 * by default
 * 
 * Default: 20
 */

define('NUM_VIEW_RESULTS',20);

/**
 * Number of METASTATS bootstrap permutations
 * Used for estimating null distribution of the 
 * METASTATS t statistic.
 */

define('NUM_METASTATS_BOOTSTRAP_PERMUTATIONS',10000);

/**
 * Path to R Executable
 * 
 * Define the path to your R executable
 * Default: /usr/local/bin/R
 */

define('R_PATH','/usr/local/packages/R-2.11.1/bin/R');

/**
 * Path to Rscript Executable
 * 
 * Define the path to your Rscript executable
 * Default: /usr/local/bin/Rscript
 */

define('RSCRIPT_PATH','/usr/local/packages/R-2.11.1/bin/Rscript');

/**
 * Activate/Deactivate JCVI-only features
 * 
 * Sett this variable to 1, activates JCVI-only
 * features that access JCVI resources that are
 * not included in this distribution.
 * Default: 0
 */

define('JCVI_INSTALLATION',1);

/**
 * Relative Count Precision 
 * 
 * The precision is used for rounding
 * relative counts after normalization.
 */

define('RELATIVE_COUNT_PRECISION',4);

/**
 * Weighted Count Precision
 * 
 * The precision is used for rounding
 * weighted counts after retrieval from 
 * the index files.
 */

define('WEIGHTED_COUNT_PRECISION',2);

/**
 * P-value Precision 
 * 
 * The precision is used for rounding
 * P-values. For METATSTATS precision is
 * automatically calculated using the 
 * NUM_METASTATS_BOOTSTRAP_PERMUTATIONS
 * field.
 */

define('PVALUE_PRECISION',4);

/**
 * Path to Perl Executable
 */

define('PERL_PATH','/usr/local/bin/perl');


/**
 * Path to formatdb formatted sequences 
 */

define('SEQUENCE_STORE_PATH','/opt/www/metarep/htdocs/metarep/app/webroot/seq-stor');

/**
 * Path to fastcmd
 */

define('FASTACMD_PATH','/usr/local/bin/fastacmd');

/**
 * Path to blastall
 */

define('BLASTALL_PATH','/usr/local/bin/blastall');


/**
 * Path to linux binaries (sed, etc.)
 */

define('LINUX_BINARY_PATH','/usr/local/bin');



//define('FIELD_CONFIG',serialize(
//		array('DEFAULT' => 
//			array('facetFields' => 
//				array(
//					'blast_species',
//					'com_name',
//					'go_id',
//					'ec_id',
//					'hmm_id'),
//			),
//			array('resultFields' => 
//				array(
//					'peptide_id',
//					'com_name',
//					'com_name_src',
//					'blast_species',
//					'blast_evalue',
//					'go_id',
//					'go_src',
//					'ec_id',
//					'ec_src',
//					'hmm_id',
//				),	
//			),			
//			array('viewTabs' => 
//				array(
//					'blast_species',
//					'com_name',
//					'go_id',
//					'ec_id', 
//					KEGG_PATHWAYS,
//					METACYC_PATHWAYS),
//				),				
//		),
//		array('HUMANN'=>
//			array('facetFields' => 
//				array('blast_species','ko_id','go_id','ec_id'),
//			),
//			array('resultFields' => 
//				array(
//					'peptide_id',
//					'com_name',
//					'com_name_src',
//					'blast_species',
//					'ko_id',
//					'go_id',
//					'go_src',
//					'ec_id',
//					'ec_src',
//					),
//			),
//			array('viewTabs' => 
//				array(
//					'blast_species',
//					'ko_id',
//					'go_id',
//					'ec_id',		
//					 KEGG_PATHWAYS,
//					 METACYC_PATHWAYS,
//				),
//			),				
//		)
//	)
//);

?>