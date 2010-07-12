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
* @version METAREP v 1.0.1
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

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

define('METAREP_WEB_ROOT','/<your-installation-dir>/apache-2.2.14/htodocs/metarep');

/**
 * METAREP Url Root
 * Default: http://localhost:8080/metarep
 */

define('METAREP_URL_ROOT','http://localhost:8080/metarep');

/**
 * Directory to store temporary files
 * 
 * Temporary files include CAKEPHP cache/application and R files
 * Default: /tmp
 */

define('METAREP_TMP_DIR','/tmp');

/**
 * Solr instance dir
 * 
 * Contains Solr configuration files in conf/  subdirectory
 * Default: /your-installation-dir>/apache-solr-1.4.0/metarep-solr
 */

define('SOLR_INSTANCE_DIR','/<your-installation-dir>/apache-solr-1.4.0/metarep-solr');

/**
 * Solr port
 * 
 * Defines the Solr port
 * Default: 1234
 */

define('SOLR_PORT','1234');

/**
 * Solr data dir
 * 
 * Defines location of Solr index files
 * Default: /<your-installation-dir>/apache-solr-1.4.0/metarep-solr/data
 */

define('SOLR_DATA_DIR','/<your-installation-dir>/apache-solr-1.4.0/metarep-solr/data');

/**
 * Solr master server host
 * 
 * Takes on role of the Solr master server in a 
 * load balanced/replication set-up.
 * Default: localhost
 */

define('SOLR_MASTER_HOST','localhost');

/**
 * Solr slave server host
 * 
 * Define the Solr slave host if you use METAREP
 * in a load balanced/replication set-up
 */

define('SOLR_SLAVE_HOST','');

/**
 * Solr big ip; define if you use a 
 * Define Solr BIG-IP if you use METAREP in a 
 * load balanced/replication set-up
 */

define('SOLR_BIG_IP_HOST','');

/**
 * FTP host
 * 
 * Specify FTP host if you like to provide 
 * additional data for your METAREP dataset
 */

define('FTP_HOST','');

/**
 * FTP suser name 
 */

define('FTP_USERNAME','');

/**
 * FTP password
 */

define('FTP_PASSWORD','');

/**
 * Email to send bug reports and feature requests. 
 * 
 * Email is displayed if METAREP can not access the Solr or
 * MySQL servers. It is also used to provide users an Email
 * address send bug reports and feature requests.
 * Default: metarep-support@jcvi.org
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

define('INTERNAL_EMAIL_EXTENSION','');

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
 * Number of METASTATS bootstrap permutations
 * Used for estimating null distribution of the 
 * METASTATS t statistic.
 */

define('NUM_METASTATS_BOOTSTRAP_PERMUTATIONS',1000);

/**
 * Path to R Executable
 * 
 * Define the path to your R executable
 * Default: /usr/local/bin/R
 */

define('R_PATH','/usr/local/bin/R');

/**
 * Path to Rscript Executable
 * 
 * Define the path to your Rscript executable
 * Default: /usr/local/bin/Rscript
 */

define('RSCRIPT_PATH','/usr/local/bin/Rscript');

/**
 * Activate/Deactivate JCVI-only features
 * 
 * Sett this variable to 1, activates JCVI-only
 * features that access JCVI resources that are
 * not included in this distribution.
 * Default: 0
 */

define('JCVI_INSTALLATION',0);
?>