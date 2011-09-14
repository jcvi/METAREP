<?php
/***********************************************************
* File: constants.php
* Description: METAREP constants
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
* @version METAREP v 1.3.0
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

//define pathway modes
define('KEGG_PATHWAYS','kegg_pathways_ec');
define('KEGG_PATHWAYS_KO','kegg_pathways_ko');
define('METACYC_PATHWAYS','metacyc_pathways');
define('FINAL_CLUSTERS','CAM_CL');
define('CORE_CLUSTERS','CAM_CR');

//define compare heatmap colors
define('HEATMAP_COLOR_YELLOW_RED', 1);
define('HEATMAP_COLOR_YELLOW_BLUE', 2);
define('HEATMAP_COLOR_BLUE', 3);
define('HEATMAP_COLOR_GREEN', 4);

//define compare options
define('FISHER', 0);
define('ABSOLUTE_COUNTS', 1);
define('RELATIVE_COUNTS', 2);
define('HEATMAP', 3);
define('CHISQUARE', 4);
define('WILCOXON', 5);
define('METASTATS', 6);
define('HIERARCHICAL_CLUSTER_PLOT', 7);
define('MDS_PLOT', 8);
define('HEATMAP_PLOT', 9);

//define compare dataset mode
define('SHOW_ALL_DATASETS',0);
define('SHOW_PROJECT_DATASETS',1);
define('SHOW_POPULATION_DATASETS',2);

//define pvalues
define('PVALUE_HIGH_SIGNIFICANCE',1);
define('PVALUE_MEDIUM_SIGNIFICANCE',2);
define('PVALUE_LOW_SIGNIFICANCE',3);
define('PVALUE_BONFERONI_HIGH_SIGNIFICANCE',4);
define('PVALUE_BONFERONI_MEDIUM_SIGNIFICANCE',5);
define('PVALUE_BONFERONI_LOW_SIGNIFICANCE',6);
define('PVALUE_ALL',7);

//define distance matrices
define('DISTANCE_HORN','horn');
define('DISTANCE_JACCARD','jaccard');
define('DISTANCE_BRAY','bray');
define('DISTANCE_EUCLIDEAN','euclidean');

//define cluster methods
define('CLUSTER_COMPLETE','complete');
define('CLUSTER_AVERAGE','average');
define('CLUSTER_SINGLE','single');
define('CLUSTER_WARD','ward');
define('CLUSTER_MEDIAN','median');
define('CLUSTER_MCQUITTY','mcquitty');
define('CLUSTER_CENTROID','centroid');

//define pipelines
define('PIPELINE_HUMANN','HUMANN');
define('PIPELINE_JCVI_META_PROK','JCVI_META_PROK');
define('PIPELINE_JCVI_META_VIRAL','JCVI_META_VIRAL');
define('PIPELINE_DEFAULT','DEFAULT');

//define plot labels
define('PLOT_LIBRARY_NAME','name');
define('PLOT_LIBRARY_LABEL','label');
define('PLOT_LIBRARY_SAMPLE_ID','sample_id');

define('HEATMAP_YELLOW_RED_START', 'F03B20');
define('HEATMAP_YELLOW_RED_END', 'FFEDA0');

define('HEATMAP_YELLOW_BLUE_START', '2C7FB8');
define('HEATMAP_YELLOW_BLUE_END', 'EDF8B1');

define('HEATMAP_BLUE_START', '3182BD');
define('HEATMAP_BLUE_END', 'DEEBF7');

define('HEATMAP_GREEN_START', '31A354');
define('HEATMAP_GREEN_END', 'E5F5E0');

define('PHP_HTTP_TRANSPORT_CURL_REUSE', 'CURL_NO_REUSE');
define('PHP_HTTP_TRANSPORT_CURL_NOREUSE', 'CURL_NO_REUSE');
define('PHP_HTTP_TRANSPORT_FILE_GET_CONTENTS', 'FILE_GET_CONTENTS');
