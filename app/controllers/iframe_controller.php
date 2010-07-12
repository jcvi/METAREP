<?php
/***********************************************************
* File: iframe_controller.php
* Description: Provides iFrame based access to JCVI dataset
* that have Apis results (JCVI-only feature).  
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

class IframeController extends AppController {	
	
	function apis($projectId,$link) {
		$link = base64_decode($link);
		$this->set('link',$link);
		$this->set('projectId',$projectId);
		$this->render('apis','empty');
	}
}
?>
