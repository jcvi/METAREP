<?php
/***********************************************************
* File: download.php
* Description: Prepare strings, files and pdfs for download.
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
* @version METAREP v 1.3.2
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

class DownloadComponent extends Object {
	
	public function string($fileName,$string)  {
		header("Content-type: text/plain");
		header("Content-Disposition: attachment;filename=$fileName");
		echo $string;
		exit;
	}
	
	public function textFile($fileName,$filePath) {			
		header('Content-description: File Transfer');		
		header('Content-type: text/plain');
		header("Content-disposition: attachment; filename=$fileName");
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($filePath));	
		readfile($filePath);	
		exit;		
	}
	
	public function pdfFile($fileName,$filePath) {			
		header('Content-type: application/pdf');
		header("Content-Disposition: attachment; filename=$fileName");		
		readfile($filePath);
		exit;		
	}	
	
}
?>