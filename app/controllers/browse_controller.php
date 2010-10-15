<?php
/***********************************************************
* File: browse_controller.php
* Description: Metagenomics annotations can be browsed using 
* four distinct hierarchies: NCBI Taxonomy, Gene Ontology, 
* Enzyme Classification and KEGG metabolic pathways. The number
* of hits are displayed for each node in the tree, and a user 
* can click on a tree node and expand further. On click a 
* summary of that node is shown featuring a pie chart calculated
* from its sub-nodes and top lists of functional and taxonomic
* assignments.
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
* @version METAREP v 1.2.0
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

define('BLAST_TAXONOMY', 'Blast Taxonomy');
define('APIS_TAXONOMY', 'Apis Taxonomy');
define('ENZYMES', 'Enzymes');
define('GENE_ONTOLOGY', 'Gene Ontology');
define('PATHWAY', 'Pathway');

class BrowseController extends AppController {
	
	var $name 		= 'Browse';
	var $helpers 	= array('Facet','Tree','Dialog');
	var $uses 		= array();	
	var $components = array('Session','RequestHandler','Solr','Format');
	
	function filter($dataset,$action) {
		$query = $this->data['Filter']['filter'];
		
		if(empty($query)){
			$query = '*:*';
		}
		
		$this->Session->write($action.'.browse.query',$query);
		$this->setAction($action,$dataset);
	}
	
	function blastTaxonomy($dataset='CBAYVIR',$expandTaxon=1,$query='*:*') {	
		$function = __FUNCTION__;		
		$this->loadModel('Project');
		$this->loadModel('Taxonomy');
		$this->pageTitle = 'Browse Taxonomy (Blast)';
			
		if($this->Session->check($function.'.browse.query')){
			$query = $this->Session->read($function.'.browse.query');
		}

		//get session based taxonomic tree
		$displayedTree = $this->Session->read(BLAST_TAXONOMY.'.tree');
		
		if(!isset($displayedTree)) {
			$expandTaxon=1;
		}

		//get taxonomy information from database
		$taxaChildren = $this->Taxonomy->find('all', array('conditions' => array('Taxonomy.parent_tax_id' => $expandTaxon)));
		$taxaParent = $this->Taxonomy->find('first', array('conditions' => array('Taxonomy.taxon_id' => $expandTaxon)));
		
		$parentName = $taxaParent['Taxonomy']['name'];				

		$childArray = array();
		$childCounts = array();
		$numChildHits =0;
		
		//for each child get solr count
		foreach($taxaChildren as $taxon) {
				
			try{
				$count=  $this->Solr->count($dataset,"($query) AND (blast_tree:{$taxon['Taxonomy']['taxon_id']})");
			}
			catch(Exception $e) {
				debug("$query AND blast_tree:{$taxon['Taxonomy']['taxon_id']}");
				$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
				$this->redirect('/projects/index',null,true);
			}
				
			//set count
			$taxon['Taxonomy']['count'] = $count;
				
			$taxonId = $taxon['Taxonomy']['taxon_id'];
				
			//filter for children
			if($count>0 && $taxonId!=1) {
				$taxon['Taxonomy']['count'] = $count;
				$taxon['Taxonomy']['children'] = NULL;
				
				$childCounts[$taxon['Taxonomy']['name']]=$count;
				//add children to child array
				$childArray[$taxonId] = $taxon['Taxonomy'];
				$numChildHits +=$count;
			}
		}
				
		$solrArguments = array(	"facet" => "true",
						'facet.field' => array('blast_species','com_name','go_id','ec_id','com_name_src','hmm_id'),
						'facet.mincount' => 1,
						"facet.limit" => NUM_TOP_FACET_COUNTS);
		
		try{		
			$result = $this->Solr->search($dataset,"($query) AND (blast_tree:$expandTaxon)", 0,0,$solrArguments,true);
		}
		catch(Exception $e) {
			$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
			$this->redirect('/projects/index',null,true);
		}
		$numHits = (int) $result->response->numFound;
		$facets = $result->facet_counts;

		
		//show root level for 1
		if($expandTaxon==1) {
			$displayedTree = $childArray;			
		}
		//build tree
		else {
			$this->traverseArray($displayedTree,$childArray,$expandTaxon);
		}

		$this->Session->write(BLAST_TAXONOMY.'.tree', $displayedTree);
		$this->Session->write(BLAST_TAXONOMY.'.childCounts', $childCounts);
		$this->Session->write(BLAST_TAXONOMY.'.facets', $facets);		
		
		$this->set('projectName', $this->Project->getProjectName($dataset));
		$this->set('projectId', $this->Project->getProjectId($dataset));
		$this->set('dataset',$dataset);
		$this->set('childCounts',$childCounts);
		$this->set('taxon',$parentName);
		$this->set('numHits',$numHits);
		$this->set('numChildHits',$numChildHits);
		$this->set('facets',$facets);	
		$this->set('mode',BLAST_TAXONOMY);			
	}

	/**
	 * @param unknown_type $dataset
	 * @param unknown_type $expandTaxon
	 */
	
	function apisTaxonomy($dataset='CBAYVIR',$expandTaxon=1,$query='*:*') {
		$function = __FUNCTION__;
		$this->loadModel('Project');
		$this->loadModel('Taxonomy');		
		$this->pageTitle = 'Browse Taxonomy (Apis)';
				
		if($this->Session->check($function.'.browse.query')){
			$query = $this->Session->read($function.'.browse.query');
		}
		
		//get session based taxonomic tree
		$displayedTree = $this->Session->read(APIS_TAXONOMY.'.tree');
		
		if(!isset($displayedTree)) {
			$expandTaxon=1;
		}

		if($this->Session->check($function.'.blast.query')){
			$query = $this->Session->read($function.'.blast.query');
		}	
		
		//get taxonomy information from database
		$taxaChildren = $this->Taxonomy->find('all', array('conditions' => array('Taxonomy.parent_tax_id' => $expandTaxon)));
		$taxaParent = $this->Taxonomy->find('first', array('conditions' => array('Taxonomy.taxon_id' => $expandTaxon)));
		//die(print($taxaParent['Taxonomy']['name']));
		$parentName = $taxaParent['Taxonomy']['name'];				

		$childArray = array();
		$childCounts = array();
		$numChildHits =0;
			
		//for each child get solr count
		foreach($taxaChildren as $taxon) {
				
			//get solr count
			try {
				$count=  $this->Solr->count($dataset,"($query) AND (apis_tree:{$taxon['Taxonomy']['taxon_id']})");
			}	
			catch(Exception $e) {
				$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
				$this->redirect('/projects/index',null,true);
			}
			//set count
			$taxon['Taxonomy']['count'] = $count;
				
			$taxonId = $taxon['Taxonomy']['taxon_id'];
				
			//filter for children
			if($count>0 && $taxonId!=1) {
				$taxon['Taxonomy']['count'] = $count;
				$taxon['Taxonomy']['children'] = NULL;
				
				$childCounts[$taxon['Taxonomy']['name']]=$count;
				//add children to child array
				$childArray[$taxonId] = $taxon['Taxonomy'];
				$numChildHits +=$count;
			}
		}
		
		
		$solrArguments = array(	"facet" => "true",
						'facet.field' => array('blast_species','com_name','go_id','ec_id','com_name_src','hmm_id'),
						'facet.mincount' => 1,
						"facet.limit" => NUM_TOP_FACET_COUNTS);
		
		try{		
			$result = $this->Solr->search($dataset,"($query) AND (apis_tree:$expandTaxon)", 0,0,$solrArguments,true);
		}
		catch(Exception $e) {
				$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
				$this->redirect('/projects/index',null,true);
		}
		$numHits = (int) $result->response->numFound;
		$facets = $result->facet_counts;

		//handle unresolved child nodes
		if(count($taxaChildren) > 0 && $numChildHits <	$numHits) {
			$childCounts['unresolved'] = $numHits - $numChildHits;
			$childArray[-1]['name']  = 'unresolved';
			$childArray[-1]['rank']  = 'no rank';
			$childArray[-1]['taxon_id'] = -1; 
			$childArray[-1]['count']  = $numHits - $numChildHits;
			$childArray[-1]['children'] = NULL;
		}
		
		//show root level for 1
		if($expandTaxon==1) {
			$displayedTree = $childArray;			
		}
		//build tree
		else {
			$this->traverseArray($displayedTree,$childArray,$expandTaxon);
		}
		
		$this->Session->write(APIS_TAXONOMY.'.tree', $displayedTree);
		$this->Session->write(APIS_TAXONOMY.'.childCounts', $childCounts);
		$this->Session->write(APIS_TAXONOMY.'.facets', $facets);		
		
		
		$this->set('projectName', $this->Project->getProjectName($dataset));
		$this->set('projectId', $this->Project->getProjectId($dataset));
		$this->set('dataset',$dataset);
		$this->set('childCounts',$childCounts);
		$this->set('taxon',$parentName);
		$this->set('numHits',$numHits);
		$this->set('numChildHits',$numChildHits);
		$this->set('facets',$facets);
		$this->set('mode',APIS_TAXONOMY);
		
	}
		
	function enzymes($dataset='CBAYVIR',$expandTaxon='root',$query = '*:*') {
		$function = __FUNCTION__;
		$this->loadModel('Project');
		$this->loadModel('Enzymes');			
		$this->pageTitle = 'Browse Enzymes';
		
		if($this->Session->check($function.'.browse.query')){
			$query = $this->Session->read($function.'.browse.query');
		}		
		
		//get session based taxonomic tree
		$displayedTree = $this->Session->read(ENZYMES.'.tree');
				
		if(!isset($displayedTree)) {
			$expandTaxon='root';
		}
		
		if($expandTaxon === 'root'){
			$solrQuery = "($query) NOT ec_id:unassigned";
			$expandTaxon=1;
		}
		else {
			$taxonPrefix = split("\\.-",$expandTaxon);
			$solrQuery = "($query) AND (ec_id:$taxonPrefix[0]*)";
		}		
		
		//get taxonomy information from database
		$taxaChildren 	= $this->Enzymes->find('all', array('conditions' => array('Enzymes.parent_ec_id' => $expandTaxon)));
		$taxaParent 	= $this->Enzymes->find('first', array('conditions' => array('Enzymes.ec_id' => $expandTaxon)));
		$parentName 	= $taxaParent['Enzymes']['name'];				

		$childArray = array();
		$childCounts = array();
		$numChildHits =0;
			
		//for each child get solr count
		foreach($taxaChildren as $taxon) {
			$ec = split("\\.-",$taxon['Enzymes']['ec_id']);
			
			try{
				//get solr count
				$count=  $this->Solr->count($dataset,"($query) AND (ec_id:{$ec[0]}*)");				
			}
			catch (Exception $e) {
				$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
				$this->redirect('/projects/index',null,true);
			}
			//set count
			$taxon['Enzymes']['count'] = $count;
			$taxon['Enzymes']['name'] = $taxon['Enzymes']['name'] ." (".$taxon['Enzymes']['ec_id'].")";
				
			$taxonId = $taxon['Enzymes']['ec_id'];
				
			//filter for children
			if($count>0 && $taxonId!=1) {
				$taxon['Enzymes']['count'] = $count;
				$taxon['Enzymes']['children'] = NULL;
				
				$childCounts[$taxon['Enzymes']['name']]=$count;
				//add children to child array
				$childArray[$taxonId] = $taxon['Enzymes'];
				$numChildHits +=$count;
			}
		}
			
		//get the facets
		$solrArguments = array(	"facet" => "true",
						'facet.field' => array('blast_species','com_name','go_id','ec_id','com_name_src','hmm_id'),
						'facet.mincount' => 1,						
						"facet.limit" => NUM_TOP_FACET_COUNTS);

		try {
			$result = $this->Solr->search($dataset,$solrQuery, 0,0,$solrArguments,true);
		}
		catch(Exception $e){
			$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
			$this->redirect('/projects/index',null,true);
		}
			
		$numHits = (int) $result->response->numFound;
		$facets = $result->facet_counts;
		
		//show root level for 1
		if($expandTaxon==1) {
			$displayedTree = $childArray;		
		}
		//build tree
		else {
			$this->traverseArray($displayedTree,$childArray,$expandTaxon);
		}
		
		$this->Session->write(ENZYMES.'.tree', $displayedTree);
		$this->Session->write(ENZYMES.'.childCounts', $childCounts);
		$this->Session->write(ENZYMES.'.facets', $facets);		
		
		$this->set('projectName', $this->Project->getProjectName($dataset));
		$this->set('projectId', $this->Project->getProjectId($dataset));
		$this->set('dataset',$dataset);
		$this->set('childCounts',$childCounts);
		$this->set('node',$parentName);
		$this->set('numHits',$numHits);
		$this->set('numChildHits',$numChildHits);
		$this->set('facets',$facets);
		$this->set('mode',ENZYMES);
	}	

	function geneOntology($dataset='CBAYVIR',$expandTaxon='root',$query='*:*') {
		$function = __FUNCTION__;
		$this->loadModel('Project');
		$this->loadModel('GoTerm');
		$this->loadModel('GoGraph');	
		$this->pageTitle = 'Browse Gene Ontology';
		
		if($this->Session->check($function.'.browse.query')){
			$query = $this->Session->read($function.'.browse.query');
		}			
		
		//get session based taxonomic tree
		$displayedTree = $this->Session->read(GENE_ONTOLOGY.'.tree');
		
		if(!isset($displayedTree)) {
			$expandTaxon='root';
		}
		
		if($expandTaxon==='root') {
			//get all entries with go assignment
			$solrQuery = "($query) NOT go_id:unassigned";
			$goAcc ="all";
			
			//set selected node attributes
			$selectedNode['acc'] = $expandTaxon;
			$selectedNode['name'] = 'root';				
		}
		else {
			$solrQuery="($query) AND (go_tree:$expandTaxon)";
			
			//GO integer to accession
			$goAcc = "GO:".str_pad($expandTaxon,7,"0",STR_PAD_LEFT);
			
			//get GO data for selected node
			$goTerm = $this->GoTerm->find('all', array('fields'=> array('name'),'conditions' => array('acc' => $goAcc)));
			$selectedNode['acc'] = $expandTaxon;
			
			//set selected node attributes
			$selectedNode['acc'] = $expandTaxon;
			$selectedNode['name'] = $goTerm[0]['GoTerm']['name'];				
		}	
		
		//get children from go database
		$taxaChildren = $this->GoGraph->find('all', array('conditions' => array('Ancestor.acc' => $goAcc,'distance'=>'1', 'Descendant.is_obsolete' => '0')));		
			
		$childArray  = array();
		$childCounts = array();
		
		$childHits =0;
		
		//for each child get solr count
		foreach($taxaChildren as $taxon) {				
			
			//transform GO accession fetched from database to integer
			$tmp =split("\\:",$taxon['Descendant']['acc']);
			$goId = ltrim($tmp[1], "0");
			
			$taxon['Descendant']['acc'] = $goId;
			
			//get solr count
			try{ 
				$count=  $this->Solr->count($dataset,"($query) AND (go_tree:$goId)");
			}
			catch(Exception $e){
				$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
				$this->redirect('/projects/index',null,true);
			}
			#FIXME	temporary fix for GO parsing error		
//			if($count==0) {				
//				$goIdFix = str_pad($goId,7,"0",STR_PAD_LEFT);
//				
//				try{ 
//					$count=  $this->Solr->count($dataset,"go_tree:".$goIdFix);
//				}
//				catch(Exception $e){
//					$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
//					$this->redirect('/projects/index',null,true);
//				}				
//			}
			
			//set count
			$taxon['Descendant']['count'] = $count;
				
			$taxonId = $taxon['Descendant']['acc'];
				
			//filter for children with counts
			if($count>0 && $taxonId!='all') {

				//check if leave node (has no children)
				$goAcc 	= "GO:".str_pad($goId,7,"0",STR_PAD_LEFT);
				
				//$displayName = $goAcc." | ".$taxon['Descendant']['name'];
				$displayName = $taxon['Descendant']['name']." ($goAcc)";
				
				//set display name, accession | name
				$taxon['Descendant']['name'] 	= $displayName;
				$taxon['Descendant']['count'] 	= $count;
				$taxon['Descendant']['children']= NULL;
				
				//create child counts
				$childCounts[$displayName]=$count;
				
				//add children to child array
				$childArray[$taxonId] = $taxon['Descendant'];
				
				//add up hits for children
				$childHits +=$count;
			}
		}
		
		$solrArguments = array(	"facet" => "true",
						'facet.field' => array('blast_species','com_name','go_id','ec_id','com_name_src','hmm_id'),
						'facet.mincount' => 1,
						"facet.limit" => NUM_TOP_FACET_COUNTS);
		try{
			$result = $this->Solr->search($dataset,$solrQuery, 0,0,$solrArguments,true);
		}
		catch(Exception $e){
			$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
			$this->redirect('/projects/index',null,true);
		}
		
		
		$numHits = (int) $result->response->numFound;

		#FIXME	temporary fix for GO parsing error		
//		if($numHits==0) {			
//			$goIdFix = str_pad($expandTaxon,7,"0",STR_PAD_LEFT);
//			$solrQuery="go_tree:$goIdFix";
//			
//			try{
//				$result = $this->Solr->search($dataset,$solrQuery, 0,0,$solrArguments,true);				
//			}
//			catch(Exception $e){
//				$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
//				$this->redirect('/projects/index',null,true);
//			}
//		}		
				
		$numHits = (int) $result->response->numFound;
		$facets = $result->facet_counts;
		
		//show root level for 1
		if($expandTaxon=='root') {
			$displayedTree = $childArray;				
		}
		
		//build tree
		else {	
			$this->traverseArray($displayedTree,$childArray,$expandTaxon);				
		}
		
		$this->Session->write(GENE_ONTOLOGY.'.tree', $displayedTree);
		$this->Session->write(GENE_ONTOLOGY.'.childCounts', $childCounts);
		$this->Session->write(GENE_ONTOLOGY.'.facets', $facets);		
		
		$this->set('dataset',$dataset);
		
		//if has children set child counts
		if(!empty($childCounts)) {			
			arsort($childCounts,SORT_NUMERIC);			
			$this->set('childCounts',$childCounts);
		}
		
		//add id to node
		$this->set('projectName', $this->Project->getProjectName($dataset));
		$this->set('projectId', $this->Project->getProjectId($dataset));
		$this->set('node',$selectedNode);				
		$this->set('numHits',$numHits);
		$this->set('numChildHits',$numHits);
		$this->set('facets',$facets);
		$this->set('mode',GENE_ONTOLOGY);
	}
	
	/**
	 * Browse Pathways
	 * 
	 * @param String $dataset dataset 
	 * @param String $expandNode selected node; default is the root node, here 16905 (Metabolism)
	 * @return void
	 * @access public
	 */	
	function pathways($dataset='CBAYVIR',$expandNode = 16905,$query='*:*') {
		$function = __FUNCTION__;
		$this->loadModel('Project');
		$this->loadModel('Pathway');		
		$this->pageTitle = 'Browse Pathways';

		if($this->Session->check($function.'.browse.query')){
			$query = $this->Session->read($function.'.browse.query');
		}			
		
		//get session based taxonomic tree
		$displayedTree = $this->Session->read(PATHWAY.'.tree');
		
		if(!isset($displayedTree)) {
			$expandTaxon=16905;
		}
		
		//get pathway information for parent and children from database		
		$parent   = $this->Pathway->find('first', array('conditions' => array('Pathway.id' => $expandNode)));
		$children = $this->Pathway->find('all', array('conditions' => array('Pathway.parent_id' => $expandNode)));
		
		$parentName  = $parent['Pathway']['name'];	
		$parentLevel = $parent['Pathway']['level'];	
		$pathwayUrl  = "http://www.genome.jp/kegg-bin/show_pathway?ec".str_pad($parent['Pathway']['kegg_id'],5,0,STR_PAD_LEFT);		
		
		//get pathway facets and overall counts
		try { 
			if($parentLevel === 'enzyme') {
				$parentSolrResults = $this->Solr->getPathwayFacets($query,$dataset,$parentLevel,$expandNode,$children,$parent['Pathway']['ec_id']);
				$parentName = $parent['Pathway']['name']." (".$parent['Pathway']['ec_id'].")";	
			}
			else {		
				$parentSolrResults = $this->Solr->getPathwayFacets($query,$dataset,$parentLevel,$expandNode,$children);
			}			
		}
		catch(Exception $e){
			$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
			if($this->Session->check($function.'.browse.query')) {
				$this->Session->delete($function.'.browse.query');
			}	
			$this->redirect('/projects/index',null,true);
			//$this->redirect('/browse/pathways',$dataset,false);
		}
				
		$childArray 	= array();
		$childCounts 	= array();
		$numChildHits 	= 0;
					
		//for each child get solr count
		foreach($children as $node) {	
			$name 	= $node['Pathway']['name'];		
			$level 	= $node['Pathway']['level'];
			$nodeId = $node['Pathway']['id'];
			$ecId 	= $node['Pathway']['ec_id'];
					
			$childCount = $this->Solr->getPathwayCount($query,$dataset,$level,$nodeId,0,$ecId);
				
			//filter for children
			if($childCount >= 0 ) {
				
				$node['Pathway']['count'] = $childCount;
				$node['Pathway']['children'] = NULL;
				
				if($level === 'enzyme') {
					$childCounts["$name ($ecId)"] = $childCount;
					$node['Pathway']['name'] = "$name ($ecId)";
					if($childCount > 0) {
						$pathwayUrl.="+$ecId";
					}
				}
				else {
					$childCounts[$name] = $childCount;
				}
				
				$childArray[$nodeId] = $node['Pathway'];
				$numChildHits += $childCount;				
			}	
			
		}
		
		//show root level for 1
		if($expandNode == 16905) {
			$displayedTree = $childArray;			
		}
		//build tree
		else {
			$this->traverseArray($displayedTree,$childArray,$expandNode);
		}
		
			
		$this->Session->write(PATHWAY.'.tree', $displayedTree);
		$this->Session->write(PATHWAY.'.childCounts', $childCounts);
		$this->Session->write(PATHWAY.'.facets', $parentSolrResults['facets']);		
			
		$this->set('projectName', $this->Project->getProjectName($dataset));
		$this->set('projectId', $this->Project->getProjectId($dataset));
		$this->set('dataset',$dataset);
		$this->set('childCounts',$childCounts);
		$this->set('node',base64_encode($parentName));
		$this->set('level',$parentLevel);
		$this->set('url',$pathwayUrl);
		$this->set('numHits',$parentSolrResults['numHits']);
		$this->set('numChildHits',$numChildHits);
		$this->set('facets',$parentSolrResults['facets']);	
		$this->set('mode',PATHWAY);	
	}
			
	public function downloadChildCounts($dataset,$node,$mode,$numHits,$query = "*:*") {
		$this->autoRender=false; 

		$query = urldecode($query);
		
		if($mode === PATHWAY) {
			$node = base64_decode($node);
		}
		
		#get childCounts
		$childCounts = $this->Session->read($mode.".childCounts");
		$content = $this->Format->infoString("Browse $mode Results",$dataset,$query,0,$numHits,$node);
		$content.=$this->Format->facetToDownloadString($node,$childCounts,$numHits);
		
		$fileName = "jcvi_metagenomics_report_".time().'.txt';
		
        header("Content-type: text/plain"); 
        header("Content-Disposition: attachment;filename=$fileName");
       
        echo $content;					
	}

	public function dowloadFacets($dataset,$node,$mode,$numHits,$query = "*:*") {
		$this->autoRender=false; 

		$query = urldecode($query);

		if($mode === PATHWAY) {
			$node = base64_decode($node);
		}		
		
		#get facet data from session
		$facets = $this->Session->read($mode.'.facets');
		
		$content=$this->Format->facetListToDownloadString("Browse $mode Results - Top 10 Functional Categories",$dataset,$facets,$query,$numHits,$node);
		
		$fileName = "jcvi_metagenomics_report_".time().'.txt';
		
        header("Content-type: text/plain"); 
        header("Content-Disposition: attachment;filename=$fileName");
       
        echo $content;
	}	
	
	// Recursively traverses a multi-dimensional array.
	private function traverseArray(&$array,&$childArray,$taxon)	{ 		
		// Loops through each element. If element again is array, function is recalled. If not, result is echoed.
		foreach($array as $key=>&$value) { 
			if($key == $taxon) {
					$value['children'] =  $childArray;
			} 
			else {
				if(is_array($value)){ 
					$this->traverseArray($value,$childArray,$taxon); 
				}
			}	
		}
	}
}
?>