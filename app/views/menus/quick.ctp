<!----------------------------------------------------------
  File: quick.ctp
  Description: Quick Navigation Menu. Builds a quick navigation menu using the jquery based jGlideMenu
  library [http://www.sonicradish.com/labs/jGlideMenu/current]. The menu contains 
  three levels: the first level displays projects, the second datasets 
  (populations and libraries), the third displays datasets options.
  
  css: glide-menu.css
  js:  jGlideMenu.069/jQuery.jGlideMenu.069.js

  PHP versions 4 and 5

  METAREP : High-Performance Comparative Metagenomics Framework (http://www.jcvi.org/metarep)
  Copyright(c)  J. Craig Venter Institute (http://www.jcvi.org)

  Licensed under The MIT License
  Redistributions of files must retain the above copyright notice.

  @link http://www.jcvi.org/metarep METAREP Project
  @package metarep
  @version METAREP v 1.3.0
  @author Johannes Goll
  @lastmodified 2010-07-09
  @license http://www.opensource.org/licenses/mit-license.php The MIT License
  
<!---------------------------------------------------------->

<?php echo $javascript->link(array('jGlideMenu.069/jQuery.jGlideMenu.069.js')) ;echo $html->css('glide-menu.css');?>
     
<script type="text/javascript">
jQuery(document).ready(function(){
	// Initialize Menu
	jQuery('#jGlide_001').jGlideMenu({
	tileSource      : '.jGlide_001_tiles'     
	}).show();
	
	 // Connect "Toggle" Link
	jQuery('#switch').click(function(){jQuery(this).jGlideMenuToggle();});                   
});
</script>	      

<!-- Main Menu [jGlide_001] CSS Configuration -->
<style  type="text/css">
	#jGlide_001 { top: 118px; left: 40px; height: 450px;width: 650px;display: none; /* Hide Menu Until Ready(Optional) */ }             
 </style>

<!-- Build Menu -->
<div class="jGM_box" id="jGlide_001">
			               
<?php    

foreach ($projects as $project) {	
              			
	//build population entries
	echo("<ul id=\"tile_001\" class=\"jGlide_001_tiles\" title=\"Quick Navigation\" alt=\"Select project from list of ".count($projects)." projects \">");	                
	echo("<li rel=\"project_id_{$project['Project']['id']}\">".$project['Project']['name']." (". count($project['Library'])." Datasets)</li>");				               	
	echo('</ul>');	
	
	//build population entries
	foreach ($project['Population'] as $population) {	
		echo("<ul id=\"project_id_{$project['Project']['id']}\" class=\"jGlide_001_tiles\" title=\" {$project['Project']['name']} \" alt=\"Select dataset from list of ".(count($project['Population']) + count($project['Library']))." datatsets \">");                			
		echo("<li rel=\"population_id_{$population['id']}\">{$population['name']} (Population)</li>"); 
		echo('</ul>');
		echo("<ul id=\"population_id_{$population['id']}\" class=\"jGlide_001_tiles\" title=\"{$population['name']}\" alt=\"Select action from list\">"); 
		echo("<li>".$html->link(__('View', true), array('controller'=> 'view', 'action'=>'index',$population['name']))."</li>"); 
		echo("<li>".$html->link(__('Search', true), array('controller'=> 'search', 'action'=>'index',$population['name']))."</li>"); 	
		echo("<li>".$html->link(__('Compare', true), array('controller'=> 'compare',$population['name']))."</li>"); 
		echo("<li>".$html->link(__('Browse Blast Taxonomy', true), array('controller'=> 'browse', 'action'=>'blastTaxonomy',$population['name']))."</li>"); 			      
		if($population['has_apis']) {echo("<li>".$html->link(__('Browse Apis Taxonomy', true), array('controller'=> 'browse', 'action'=>'apisTaxonomy',$population['name']))."</li>"); }				               			
			if($library['pipeline'] === PIPELINE_HUMANN || $library['has_ko']) {
			echo("<li>".$html->link(__('Browse Kegg Pathways (KO)', true), array('controller'=> 'browse', 'action'=>'keggPathwaysKo',$library['name']))."</li>");		
		}
		if($population['pipeline'] === PIPELINE_HUMANN || $population['has_ko']) {
			echo("<li>".$html->link(__('Browse Kegg Pathways (KO)', true), array('controller'=> 'browse', 'action'=>'keggPathwaysKo',$library['name']))."</li>");		
		}			
		echo("<li>".$html->link(__('Browse Kegg Pathways', true), array('controller'=> 'browse', 'action'=>'keggPathwaysEc',$population['name']))."</li>");		      
		echo("<li>".$html->link(__('Browse Metacyc Pathways', true), array('controller'=> 'browse', 'action'=>'metacycPathways',$population['name']))."</li>");		      
		echo("<li>".$html->link(__('Browse Enzymes', true), array('controller'=> 'browse', 'action'=>'enzymes',$population['name']))."</li>");
		echo("<li>".$html->link(__('Browse Gene Ontology', true), array('controller'=> 'browse', 'action'=>'geneOntology',$population['name']))."</li>");  		               			
		echo('</ul>');             			
	} 	
	//build library entries			               		               
	foreach ($project['Library'] as $library) {	
		echo("<ul id=\"project_id_{$project['Project']['id']}\" class=\"jGlide_001_tiles\" title=\" {$project['Project']['name']} \" alt=\"Select dataset from list:\">");                			
		echo("<li rel=\"library_id_{$library['id']}\">{$library['name']} </li>"); 
		echo('</ul>');		               			
		echo("<ul id=\"library_id_{$library['id']}\" class=\"jGlide_001_tiles\" title=\"{$library['name']}\" alt=\"Select action from list\">"); 
		echo("<li>".$html->link(__('View', true), array('controller'=> 'view', 'action'=>'index',$library['name']))."</li>"); 
		echo("<li>".$html->link(__('Search', true), array('controller'=> 'search', 'action'=>'index',$library['name']))."</li>"); 	
		echo("<li>".$html->link(__('Compare', true), array('controller'=> 'compare',$library['name']))."</li>");  			               			
		echo("<li>".$html->link(__('Browse Blast Taxonomy', true), array('controller'=> 'browse', 'action'=>'blastTaxonomy',$library['name']))."</li>");
		if($library['apis_database'] !='') {echo("<li>".$html->link(__('Browse Apis Taxonomy', true), array('controller'=> 'browse', 'action'=>'apisTaxonomy',$library['name']))."</li>"); }				
		
		if($library['pipeline'] === PIPELINE_HUMANN || $library['has_ko']) {
			echo("<li>".$html->link(__('Browse Kegg Pathways (KO)', true), array('controller'=> 'browse', 'action'=>'keggPathwaysKo',$library['name']))."</li>");		
		}					
		echo("<li>".$html->link(__('Browse Kegg Pathways (EC)', true), array('controller'=> 'browse', 'action'=>'keggPathwaysEc',$library['name']))."</li>");		
		echo("<li>".$html->link(__('Browse Metacyc Pathways (EC)', true), array('controller'=> 'browse', 'action'=>'metacycPathways',$library['name']))."</li>");		            
		echo("<li>".$html->link(__('Browse Enzymes', true), array('controller'=> 'browse', 'action'=>'enzymes',$library['name']))."</li>");
		echo("<li>".$html->link(__('Browse Gene Ontology', true), array('controller'=> 'browse', 'action'=>'geneOntology',$library['name']))."</li>");  	               			
		if($library['apis_dataset'] && JCVI_INSTALLATION) {	
			echo("<li>".$html->link(__('View External APIS Page', true), array('controller'=> 'iframe', 'action'=>'apis',$library['project_id'],base64_encode("http://www.jcvi.org/apis/".$library['apis_database']."/".$library['apis_dataset'])))."</li>");  	               			
		}
		if($library['has_ftp']) {	
			echo("<li>".$html->link(__('Download', true), array('controller'=> 'projects', 'action'=>'ftp',$project['Project']['id'],$library['name']))."</li>");  	
		}			
		echo('</ul>');             			
	} 
} 
?>
</div>		