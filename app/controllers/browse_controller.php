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
* @version METAREP v 1.3.0
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

define('BLAST_TAXONOMY', 'Blast Taxonomy');
define('APIS_TAXONOMY', 'Apis Taxonomy');
define('ENZYMES', 'Enzymes');
define('GENE_ONTOLOGY', 'Gene Ontology');

App::import('Sanitize');

class BrowseController extends AppController {
	
	var $name 		= 'Browse';
	var $helpers 	= array('Facet','Tree','Dialog');
	var $uses 		= array();	
	var $components = array('Session','RequestHandler','Solr','Format','Color');

	var $facetFields = array(
								'blast_species'=>'Species (Blast)',
								'com_name'=>'Common Name',
								'go_id'=>'Gene Ontology',
								'ec_id'=>'Enzyme',
								'hmm_id'=>'HMM',
							);	
	
	function filter($dataset,$action) {
		$query = $this->data['Filter']['filter'];
		
		if(empty($query)){
			$query = '*:*';
		}
		
		$this->Session->write($action.'.browse.query',$query);
		$this->setAction($action,$dataset);
	}
	
	function blastTaxonomy($dataset,$expandTaxon=1,$query='*:*') {	
		$function = __FUNCTION__;		
		
		$this->loadModel('Project');
		$this->loadModel('Taxonomy');
		$this->pageTitle = 'Browse Taxonomy (Blast)';
			
		$pipeline	=  $this->Project->getPipeline($dataset);
		
		if($pipeline === 'HUMANN') { 
 			$this->facetFields = array(
								'blast_species'=>'Species (Blast)',
								'ko_id'=>'Kegg Ortholog',
								'go_id'=>'Gene Ontology',
								'ec_id'=>'Enzyme',
							);			
		}
		
		if($this->Session->check($function.'.browse.query')){
			$query = $this->Session->read($function.'.browse.query');
		}

		//get session based taxonomic tree
		$displayedTree = $this->Session->read(BLAST_TAXONOMY.'.browse.tree');
		
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

			$solrAguments = array('fq'=>"blast_tree:{$taxon['Taxonomy']['taxon_id']}");		
			
			try{
				$count=  $this->Solr->count($dataset,$query,$solrAguments);
			}
			catch(Exception $e) {
				//debug("$query AND blast_tree:{$taxon['Taxonomy']['taxon_id']}");
				$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
				$this->redirect('/projects/index',null,true);
			}
				
			//set count
			$taxon['Taxonomy']['count'] = $count;
				
			$taxonId = $taxon['Taxonomy']['taxon_id'];
				
			//filter for children
			if($count > 0 && $taxonId != 1) {
				$taxon['Taxonomy']['count'] = $count;
				$taxon['Taxonomy']['children'] = NULL;
				
				$childCounts[$taxon['Taxonomy']['name']]=$count;
				//add children to child array
				$childArray[$taxonId] = $taxon['Taxonomy'];
				$numChildHits +=$count;
			}
		}
				
		$solrArguments = array(	
						'fq'=> "blast_tree:$expandTaxon",
						'facet' => 'true',
						'facet.field' => array_keys($this->facetFields),
						'facet.mincount' => 1,
						'facet.limit' => NUM_TOP_FACET_COUNTS);
		
		try{		
			$result = $this->Solr->search($dataset,$query, 0,0,$solrArguments,true);
		}
		catch(Exception $e) {
			$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
			$this->redirect('/projects/index',null,true);
		}
		$numHits= $result->response->numFound;
		$facets = $result->facet_counts;
	
		//show root level for 1
		if($expandTaxon==1) {
			$displayedTree = $childArray;			
		}
		//build tree
		else {
			$this->traverseArray($displayedTree,$childArray,$expandTaxon);
		}

		
		
		$this->Session->write(BLAST_TAXONOMY.'.browse.tree', $displayedTree);
		$this->Session->write(BLAST_TAXONOMY.'.browse.childCounts', $childCounts);
		$this->Session->write(BLAST_TAXONOMY.'.browse.facets', $facets);				
		$this->Session->write(BLAST_TAXONOMY.'.browse.facetFields',$this->facetFields);	
		
		$this->set('projectName', $this->Project->getProjectName($dataset));
		$this->set('projectId', $this->Project->getProjectId($dataset));
		$this->set('dataset',$dataset);
		$this->set('childCounts',$childCounts);
		$this->set('taxon',$parentName);
		$this->set('numHits',$numHits);
		$this->set('numChildHits',$numChildHits);
		$this->set('facets',$facets);	
		$this->set('pipeline',$pipeline);		
		$this->set('mode',BLAST_TAXONOMY);	
			
	}

	/**
	 * @param unknown_type $dataset
	 * @param unknown_type $expandTaxon
	 */
	
	function apisTaxonomy($dataset,$expandTaxon=1,$query='*:*') {
		$function = __FUNCTION__;
		$this->loadModel('Project');
		$this->loadModel('Taxonomy');		
		$this->pageTitle = 'Browse Taxonomy (Apis)';

		$pipeline	=  $this->Project->getPipeline($dataset);
		
		if($pipeline === 'HUMANN') { 	
 			$this->facetFields = array(
								'blast_species'=>'Species (Blast)',
								'ko_id'=>'Kegg Ortholog',
								'go_id'=>'Gene Ontology',
								'ec_id'=>'Enzyme',
							);			
		}		
		
		if($this->Session->check($function.'.browse.query')){
			$query = $this->Session->read($function.'.browse.query');
		}
		
		//get session based taxonomic tree
		$displayedTree = $this->Session->read(APIS_TAXONOMY.'.browse.tree');
		
		if(!isset($displayedTree)) {
			$expandTaxon=1;
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
				
			$solrAguments = array('fq'=>"apis_tree:{$taxon['Taxonomy']['taxon_id']}");		
			//get solr count
			try {
				$count=  $this->Solr->count($dataset,$query,$solrAguments);
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
			
		$solrArguments = array(	
						'fq'=> "apis_tree:$expandTaxon",
						'facet' => 'true',
						'facet.field' => array_keys($this->facetFields),
						'facet.mincount' => 1,
						'facet.limit' => NUM_TOP_FACET_COUNTS);
		
		try{		
			$result = $this->Solr->search($dataset,$query, 0,0,$solrArguments,true);
		}
		catch(Exception $e) {
			$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
			$this->redirect('/projects/index',null,true);
		}
		
		$numHits= $result->response->numFound;
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
		
		$this->Session->write(APIS_TAXONOMY.'.browse.tree', $displayedTree);
		$this->Session->write(APIS_TAXONOMY.'.browse.childCounts', $childCounts);
		$this->Session->write(APIS_TAXONOMY.'.browse.facets', $facets);		
		$this->Session->write(APIS_TAXONOMY.'.browse.facetFields',$this->facetFields);	
		
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
		
	function enzymes($dataset,$expandTaxon='root',$query = '*:*') {		
		$function = __FUNCTION__;
		$this->loadModel('Project');
		$this->loadModel('Enzymes');			
		$this->pageTitle = 'Browse Enzymes';
				
		$pipeline	=  $this->Project->getPipeline($dataset);
		
		if($pipeline === 'HUMANN') { 
 			$this->facetFields = array(
								'blast_species'=>'Species (Blast)',
								'ko_id'=>'Kegg Ortholog',
								'go_id'=>'Gene Ontology',
								'ec_id'=>'Enzyme',
							);			
		}		
		
		if($this->Session->check($function.'.browse.query')){
			$query = $this->Session->read($function.'.browse.query');
		}		
		
		//get session based taxonomic tree
		$displayedTree = $this->Session->read(ENZYMES.'.browse.tree');
				
		if(!isset($displayedTree)) {
			$expandTaxon = 'root';
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
				$count=  $this->Solr->count($dataset,$query,array('fq'=>"ec_id:{$ec[0]}*"));					
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
			if($count > 0 && $taxonId != 0) {
				$taxon['Enzymes']['count'] = $count;
				$taxon['Enzymes']['children'] = NULL;
				
				$childCounts[$taxon['Enzymes']['name']]=$count;
				//add children to child array
				$childArray[$taxonId] = $taxon['Enzymes'];
				$numChildHits +=$count;
			}
		}
			
		//get the facets for the selected node
		$solrArguments = array(	"facet" => "true",
						'facet.field' => array_keys($this->facetFields),
						'facet.mincount' => 1,						
						"facet.limit" => NUM_TOP_FACET_COUNTS);

		try {
			$result = $this->Solr->search($dataset,$solrQuery, 0,0,$solrArguments,true);
		}
		catch(Exception $e){
			$this->Session->setFlash(SOLR_CONNECT_EXCEPTION);
			$this->redirect('/projects/index',null,true);
		}
			
		$numHits = (double) $result->response->numFound;
		$facets = $result->facet_counts;
		
		//show root level for 1
		if($expandTaxon === 1) {			
			$displayedTree = $childArray;		
		}
		//build tree
		else {
			$this->traverseArray($displayedTree,$childArray,$expandTaxon);
		}
		
		$this->Session->write(ENZYMES.'.browse.tree', $displayedTree);
		$this->Session->write(ENZYMES.'.browse.childCounts', $childCounts);
		$this->Session->write(ENZYMES.'.browse.facets', $facets);		
		$this->Session->write(ENZYMES.'.browse.facetFields',$this->facetFields);	
		
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

	function geneOntology($dataset,$expandTaxon='root',$query='*:*') {
		$function = __FUNCTION__;
		$this->loadModel('Project');
		$this->loadModel('GoTerm');
		$this->loadModel('GoGraph');	
		$this->pageTitle = 'Browse Gene Ontology';

		$pipeline	=  $this->Project->getPipeline($dataset);
		
		if($pipeline === 'HUMANN') { 	
 			$this->facetFields = array(
								'blast_species'=>'Species (Blast)',
								'ko_id'=>'Kegg Ortholog',
								'go_id'=>'Gene Ontology',
								'ec_id'=>'Enzyme',
							);			
		}		
		
		if($this->Session->check($function.'.browse.query')){
			$query = $this->Session->read($function.'.browse.query');
		}			
		
		//get session based taxonomic tree
		$displayedTree = $this->Session->read(GENE_ONTOLOGY.'.browse.tree');
		
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
			
			$selectedNode['name'] = $goTerm[0]['GoTerm']['name']." ($goAcc)";				
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
						'facet.field' => array_keys($this->facetFields),
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
		
		$this->Session->write(GENE_ONTOLOGY.'.browse.tree', $displayedTree);
		$this->Session->write(GENE_ONTOLOGY.'.browse.childCounts', $childCounts);
		$this->Session->write(GENE_ONTOLOGY.'.browse.facets', $facets);	
		$this->Session->write(GENE_ONTOLOGY.'.browse.facetFields',$this->facetFields);		
		
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
	 * Browse MetaCyc Pathways
	 * 
	 * @param String $dataset dataset 
	 * @param String $expandNode selected node; default is the root node, here 16905 (Metabolism)
	 * @return void
	 * @access public
	 */	
	public function metacycPathways($dataset,$expandNode = 1,$query='*:*') {	
		$this->pathways($dataset,$expandNode,$query='*:*','metacyc_pathways');
	}

	/**
	 * Browse Kegg Pathways (EC)
	 * 
	 * @param String $dataset dataset 
	 * @param String $expandNode selected node; default is the root node, here 16905 (Metabolism)
	 * @return void
	 * @access public
	 */	
	public function keggPathwaysEc($dataset,$expandNode = 1,$query='*:*') {	
		$this->pathways($dataset,$expandNode,$query='*:*',KEGG_PATHWAYS);
	}	
	
	/**
	 * Browse Kegg Pathways (KO)
	 * 
	 * @param String $dataset dataset 
	 * @param String $expandNode selected node; default is the root node, here 16905 (Metabolism)
	 * @return void
	 * @access public
	 */	
	public function keggPathwaysKo($dataset,$expandNode = 1,$query='*:*') {	
		$this->pathways($dataset,$expandNode,$query='*:*',KEGG_PATHWAYS_KO);
	}		
	
	/**
	 * Browse Pathways
	 *
	 * @param String $dataset dataset
	 * @param String $parentId selected node; default is the root node, here 16905 (Metabolism)
	 * @return void
	 * @access public
	 */
	private function pathways($dataset,$parentId,$query,$pathwayModel) {
		$this->loadModel('Project');

		$function = $this->underscoreToCamelCase($pathwayModel);

		$pipeline	=  $this->Project->getPipeline($dataset);

		if($pipeline === PIPELINE_HUMANN) {
			$this->facetFields = array(
						'blast_species'=>'Species (Blast)',
						'ko_id'=>'Kegg Ortholog',
						'go_id'=>'Gene Ontology',
						'ec_id'=>'Enzyme',
			);
		}

		//initialize variables
		$childArray 	= array();
		$childCounts 	= array();
		$facets 		= array();
		$numChildHits 	= 0;

		$this->loadModel('Pathway');
		$this->loadModel('Project');

		//set title
		if($pathwayModel === KEGG_PATHWAYS) {
			$title = 'Browse Kegg Pathways (EC)';
		}
		else if($pathwayModel === KEGG_PATHWAYS_KO) {
			$title = 'Browse Kegg Pathways (KO)';
		}
		else if($pathwayModel === METACYC_PATHWAYS) {
			$title = 'Browse Metacyc Pathways (EC)';
		}

		$this->pageTitle = $title;

		if($this->RequestHandler->isAjax()) {
			$this->ajax= true;
		}

		if($parentId == 1) {
			#$this->Session->delete("$function.browse.query");
			$this->Session->delete("$function.browse.tree");
			$this->Session->delete("$function.browse.facets");
		}

		if($this->Session->check("$function.browse.query")){
			$query = $this->Session->read("$function.browse.query");
		}


		//read tree from session
		if($this->Session->check("$function.browse.tree")){
			$displayedTree = $this->Session->read("$function.browse.tree");
		}
		else {
			$expandTaxon=1;
		}

		//get pathway information for parent and children from database
		$parent 	= $this->Pathway->getById($parentId,$pathwayModel);

		$children 	= $this->Pathway->getChildrenByParentId($parentId,$pathwayModel);
		
		$parentName  		= $parent['name'];
		$parentExternalId 	= $parent['external_id'];
		$parentLevel 		= $parent['level'];

		if($pathwayModel === KEGG_PATHWAYS || $pathwayModel === METACYC_PATHWAYS) {
			$parentEcId 	= $parent['ec_id'];
		}
		else if($pathwayModel === KEGG_PATHWAYS_KO) {
			$parentkoId 	= $parent['ko_id'];
		}

		$pathwayUrl = $this->Pathway->getUrl($parentExternalId,$pathwayModel);
		$pathwayUrl .='/default%3white';
		$parentCount = $this->Pathway->getCount($parentId,$this->Solr,$dataset,$query,$pathwayModel);

		if($parentLevel === 'enzyme') {
			$parentName = "$parentName ($parentEcId)";
		}
		else if($parentLevel === 'kegg-ortholog') {
			$parentName = "$parentName ($parentkoId)";
		}
		else if($parentLevel === 'pathway') {
			$colorGradient =  $this->Color->gradient(HEATMAP_COLOR_YELLOW_RED);
			$this->set('colorGradient',$colorGradient);
		}

		$facets = $this->Pathway->getFacets($parentId,$this->Solr,$dataset,$query,$this->facetFields,$pathwayModel);


		//for each child get solr count
		foreach($children as $node) {
			$name 	= strip_tags($node[$pathwayModel]['name']);
			$level 	= $node[$pathwayModel]['level'];
			$nodeId = $node[$pathwayModel]['id'];
				
			if($pathwayModel === KEGG_PATHWAYS || $pathwayModel === METACYC_PATHWAYS) {
				$ecId 	= $node[$pathwayModel]['ec_id'];
			}
			else if($pathwayModel === KEGG_PATHWAYS_KO) {
				$koId 	= $node[$pathwayModel]['ko_id'];
			}

			$childCount = $this->Pathway->getCount($nodeId,$this->Solr,$dataset,$query,$pathwayModel);
			
			//filter for children
			if($childCount >= 0 ) {

				$node[$pathwayModel]['count'] = $childCount;
				$node[$pathwayModel]['children'] = NULL;

				if($pathwayModel === KEGG_PATHWAYS || $pathwayModel === METACYC_PATHWAYS) {
					if($level === 'enzyme') {
						$relCount = round($childCount/$parentCount,12);
						$color = $colorGradient[floor($relCount*19)];

						$childCounts["$name ($ecId)"] = $childCount;
						$node[$pathwayModel]['name'] = "$name ($ecId)";
						if($childCount > 0) {
							//$pathwayUrl.="+$ecId";
							$pathwayUrl.="/$ecId%09%23$color";
						}
						else {
							$pathwayUrl.="/$ecId%09%23FFFFFF";
						}
					}
					else {
						$childCounts[$name] = $childCount;
					}
				}
				else if($pathwayModel === KEGG_PATHWAYS_KO) {
					if($level === 'kegg-ortholog') {
						$relCount = round($childCount/$parentCount,12);
						$color = $colorGradient[floor($relCount*19)];

						$childCounts["$name ($koId)"] = $childCount;
						$node[$pathwayModel]['name'] = "$name ($koId)";
						if($childCount > 0) {
							//$pathwayUrl.="+$ecId";
							$pathwayUrl.="/$koId%09%23$color";
						}
						else {
							$pathwayUrl.="/$koId%09%23FFFFFF";
						}
					}
					else {
						$childCounts[$name] = $childCount;
					}
						
				}

				$childArray[$nodeId] = $node[$pathwayModel];
				$numChildHits += $childCount;
			}
				
		}



		if($parentId == 1) {
			$displayedTree = $childArray;
		}
		//build tree
		else {
			$this->traverseArray($displayedTree,$childArray,$parentId);
		}


		$this->Session->write("$function.browse.tree", $displayedTree);
		$this->Session->write("$function.browse.childCounts", $childCounts);
		$this->Session->write("$function.browse.facets", $facets);
		$this->Session->write("$function.browse.facetFields",$this->facetFields);

		$this->set('projectName', $this->Project->getProjectName($dataset));
		$this->set('projectId', $this->Project->getProjectId($dataset));
		$this->set('dataset',$dataset);
		$this->set('childCounts',$childCounts);
		$this->set('node',base64_encode($parentName));
		$this->set('level',$parentLevel);
		$this->set('url',$pathwayUrl);

		$this->set('numHits',$parentCount);
		$this->set('numChildHits',$numChildHits);

		$this->set('mode',$pathwayModel);

		$this->set('header',$title);

		$this->render('pathways');
	}

	public function downloadChildCounts($dataset,$node,$mode,$numHits,$query = "*:*") {
		$this->autoRender=false; 

		$query = urldecode($query);
		
		if($mode === KEGG_PATHWAYS || $mode === METACYC_PATHWAYS || $mode === KEGG_PATHWAYS_KO) {		
			$node = strip_tags(base64_decode($node));
			$function = $this->underscoreToCamelCase($mode);	
			$childCounts = $this->Session->read("$function.browse.childCounts");
			
			if($mode === KEGG_PATHWAYS) {
				$mode = 'Kegg Pathways (EC)';
			}
			else if ($mode === KEGG_PATHWAYS_KO) {
				$mode = 'Kegg Pathways (KO)';
			}				
			else if ($mode === METACYC_PATHWAYS) {
				$mode = 'Metacyc Pathways';
			}
		
		}
		else {
			$childCounts = $this->Session->read($mode.".browse.childCounts");
		}
		
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
		
		if($mode === KEGG_PATHWAYS || $mode === METACYC_PATHWAYS || $mode === KEGG_PATHWAYS_KO) {		
			$node = strip_tags(base64_decode($node));
			$function = $this->underscoreToCamelCase($mode);	
			
			#$function = $this->underscoreToCamelCase($mode);	
			$facets = $this->Session->read("$function.browse.facets");
			$facetFields = $this->Session->read($function.'.browse.facetFields');
			
			if($mode === KEGG_PATHWAYS) {
				$mode = 'Kegg Pathways (EC)';
			}
			else if ($mode === KEGG_PATHWAYS_KO) {
				$mode = 'Kegg Pathways (KO)';
			}				
			else if ($mode === METACYC_PATHWAYS) {
				$mode = 'Metacyc Pathways';
			}
		}	
		else{			
			$facets = $this->Session->read($mode.'.browse.facets');
			$facetFields = $this->Session->read($mode.'.browse.facetFields');
		}
		
		
		
		#die("Browse $mode Results - Top 10 Functional Categories,$dataset,$facets,$query,$numHits,$node");
		$content=$this->Format->facetListToDownloadString("Browse $mode Results - Top 10 Functional Categories",$dataset,$facets,$facetFields,$query,$numHits,$node);
		
		$fileName = "jcvi_metagenomics_report_".time().'.txt';
		
        header("Content-type: text/plain"); 
        header("Content-Disposition: attachment;filename=$fileName");
       
        echo $content;
	}	
	
	// Recursively traverses a multi-dimensional array.
	// Loops through each element. If element again is array, 
	// function is recalled. If not, result is echoed.
	private function traverseArray(&$array,&$childArray,$taxon,$count=0)	{ 				
		$keys = array_keys($array);
		$count++;
		foreach($keys as $key) { 
			if($key == $taxon) {
				$array[$key]['children'] =  $childArray;
			} 
			else {
				if(is_array($array[$key]['children'])){ 
					$this->traverseArray($array[$key]['children'],$childArray,$taxon,$count); 
				}
			}				
		}
	}
}
?>