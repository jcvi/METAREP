<?php
/***********************************************************
* File: rest_controller.php
* Description: In testing - Class for provideing programmatic
* access via a REST-lile interface that returns XML for 
* common user requests.
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
class RestController extends AppController {
	var $name		= 'Rest';
	var $components = array('RequestHandler','Solr');
	var $uses 		= array('Project');
	var $helpers    = array('Xml');

	function projects($username="",$password="") {
		$this->layout = 'xml'; 
		
		$this->RequestHandler->setContent('xml');
		//$this->Solr->search($dataset,$filter,0,0,$solrArguments);
		//$this->Sol->executeUrl();
		//$content = $this->Solr->search("01-GS108-G-4-6kb","cluster_id:CAM_CL*", 0, 10,array('wt'=>'xml'));  		
		#debug($content);
		 
		#if($this->login($username,$password)) {

		$projects = $this->Project->find('all');
		$this->set(compact('projects'));
			#$this->set('content',$content);
			#$this->render('xml/index','xml');
//		}
//		else {
//			die('not loged in');
//		}	
	}
	
	function query($user,$password,$query) {
		passthrough('http://172.20.12.25:8989/solr/01-GS108-G-4-6kb/select?facet=true&facet.field=apis_tree&facet.limit=1000&q=cluster_id:CAM_CL_159&start=0&rows=0');
	}
	
	function projectDatasets($user,$password) {
		
	}	
	function queryDataset($user,$password,$query) {
		
	}
	function annotation($user,$password,$id) {
		
	}
	
	
	private function login($username,$password) {
		$this->data['User']['username']= $username;
		$this->data['User']['password']= $password;
		return Authsome::login($this->data['User']);
	}
	
}
?>