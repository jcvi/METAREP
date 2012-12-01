<?php
/***********************************************************
* File: gradient.php
* Handles interactions with the KEGG URL based API.
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

class KeggComponent extends Object {

	## fetch <input type="hidden" name="image" value=/tmp/WWW/mark_pathway132379783823793/ec00624.png>
	## http://www.genome.jp/tmp/mark_pathway132379900919385/ec00624.png
	function pathwayImageFromUrl($url) {
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
		$data = curl_exec($ch);
		$matches = array();		
		preg_match_all('/name="image" value=\/share\/www\/(.*).png>/', $data , $matches);
		curl_close($ch);
		return "http://133.103.200.20/tmp/{$matches[1][0]}.png";		
	}
}
?>
