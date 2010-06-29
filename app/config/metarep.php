<?php
/***********************************************************
*  File: metarep.php
*  Description: METAREP configuration file
*
*  Author: jgoll
*  Date:   Jun 29, 2010
************************************************************/

/**
 * METAREP Web Root
 */

define('METAREP_WEB_ROOT',' /<your-installation-dir>/apache-2.2.14/htodocs/metarep');

/**
 * Directory to store tmporary files
 */

define('METAREP_TMP_DIR','/tmp');

/**
 * Solr instance dir; contains Solr configuration files
 */

define('SOLR_INSTANCE_DIR','/<your-installation-dir>/apache-solr-1.4.0/metarep-solr');

/**
 * Solr data dir; contains index files
 */

define('SOLR_DATA_DIR','/<your-installation-dir>/apache-solr-1.4.0/metarep-solr/data');

/**
 * Solr port
 */

define('SOLR_PORT','1234');

/**
 * Solr master server url
 */

define('SOLR_MASTER_HOST','localhost');

/**
 * Solr slave server url; define if you use a 
 * load balanced master/slave replication set up 
 */

//define('SOLR_SLAVE_HOST','172.20.13.25');

/**
 * Solr big ip; define if you use a 
 * load balanced master/slave replication set up ;
 */

//define('SOLR_BIG_IP_HOST','172.20.12.25:8989');

/**
 * Email to send bug reports and feature requests
 */

//define('METAREP_SUPPORT_EMAIL','metarep-support@jcvi.org');

/**
 * Email extension that is used to identify internal users
 * Internal users can access to all datasets
 */

//define('INTERNAL_EMAIL_EXTENSION','jcvi.org');

/**
 * Top number of hits shown for annotation data types
 * in browse and search pages
 */

define('NUM_TOP_FACET_COUNTS',10);

/**
 * Number of results shown per search result page
 */

define('NUM_SEARCH_RESULTS',10);

/**
 * Path to Rscript executable
 */

define('RSCRIPT_PATH','/usr/local/bin/Rscript');

/**
 * Path to R
 */

define('R_PATH','/usr/local/bin/R');

/**
 * Activate/Deactivate JCVI-only features
 */

define('JCVI_INSTALLATION',0);


?>