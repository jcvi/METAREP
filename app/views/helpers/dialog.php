<?php
/***********************************************************
* File: dialog.php
* Description: The Dialog Helper class defines methods
* to print help dialog messages.
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

class DialogHelper extends AppHelper {


	var $helpers = array('Html');	
	
	var $searchParagraph = "<p>The 'Filter Panel' allows you to filter datasets using fielded data. When defining a filter you can either specify a field, or use the default field (com_name).
				You can search any field by typing the field name followed by a colon \":\" and then the term you are looking for.</p>
				<p>
				<table>
				<tr><td valign=\"top\">
				<ul>
				<h5>Core Evidence Fields</h5>
				<BR>
					<li><strong>com_name_txt</strong> Common name (default field).</li>
					<li><strong>com_name_src</strong> Common names source/evidence.</li>
					<li><strong>go_id</strong> Gene Ontology ID.</li>
					<li><strong>go_src</strong> Gene Ontology source.</li>
					<li><strong>ec_id</strong> Enzyme Commision ID.</li>
					<li><strong>ec_src</strong> Enzyme Commision source.</li>
					<li><strong>hmm_id</strong> PFAM and TIGRFAM HMM accessions</li>
				</ul>
				</td><td valign=\"top\">
				<h5>Best Blast Hit Fields</h5>
				<BR>
					<li><strong>blast_species</strong> Blast species.</li>
					<li><strong>blast_tree</strong> NCBI taxonomy lineage for blast species.</i></li>
					<li><strong>blast_evalue_exp</strong> Negative Blast E-Value exponent.</li>
					<li><strong>blast_pid</strong> Blast percent identity.</li>
					<li><strong>blast_cov</strong> Blast coverage of shortest sequence.</li>
				</ul>
				</td><td valign=\"top\">
				<h5>Optional Fields</h5>
				<BR>
				<ul>	
					<li><strong>filter</strong> Sequence filter, e.g. Schmidt et al.</li>
					<li><strong>apis_tree</strong> NCBI taxonomy lineage for most precise Apis classification.</li>
					<li><strong>env_lib</strong> Environmental library that has been hit (only available for viral libraries).</li>
					<li><strong>scaff_id</strong> Scaffold id.</li>
					<li><strong>scaff_tree</strong> NCBI taxonomy lineage for most precise scaffold classification.</li>
					</li>
				</ul></td></tr>
				</p>
				<BR>
				</table>
				</p>";
	
	function printSearch($divId, $dataset = null, $action = 'index') {
		echo("<div id=\"$divId\" title=\"Search Help Dialog\">	
		<p><p>The search supports fielded data. When performing a search you can either specify a field, or use the default field (com_name).
		You can search any field by typing the field name followed by a colon \":\" and then the term you are looking for.</p>
		<p>
		<table>
		<tr><td valign=\"top\">
		<ul>
		<h5>Core Evidence Fields</h5>
		<BR>
			<li><strong>com_name_txt</strong> Common name (default field).</li>
			<li><strong>com_name_src</strong> Common names source/evidence.</li>
			<li><strong>go_id</strong> Gene Ontology ID.</li>
			<li><strong>go_src</strong> Gene Ontology source.</li>
			<li><strong>ec_id</strong> Enzyme Commision ID.</li>
			<li><strong>ec_src</strong> Enzyme Commision source.</li>
			<li><strong>hmm_id</strong> PFAM and TIGRFAM HMM accessions</li>
		</ul>
		</td><td valign=\"top\">
		<h5>Best Blast Hit Fields</h5>
		<BR>
			<li><strong>blast_species</strong> Blast species.</li>
			<li><strong>blast_tree</strong> NCBI taxonomy lineage for blast species.</i></li>
			<li><strong>blast_evalue_exp</strong> Negative Blast E-Value exponent.</li>
			<li><strong>blast_pid</strong> Blast percent identiy.</li>
			<li><strong>blast_cov</strong> Blast coverage of shortest sequence.</li>
		</ul>
		</td><td valign=\"top\">
		<h5>Optional Fields</h5>
		<BR>
		<ul>	
			<li><strong>filter</strong> Sequence filter, e.g. Schmidt et al.</li>
			<li><strong>apis_tree</strong> NCBI taxonomy lineage for most precise Apis classification.</li>
			<li><strong>env_lib</strong> Environmental library that has been hit (only available for viral libraries).</li>
			<li><strong>scaff_id</strong> Scaffold id.</li>
			<li><strong>scaff_tree</strong> NCBI taxonomy lineage for most precise scaffold classification.</li>
			</li>
		</ul></td></tr>
		</p>
		<BR>
		</table>
		<p>
		<h5>Examples</h5><BR>
		<pre class=\"code\">com_name_txt:phage* ". $this->Html->link('test it', array('controller'=>'search','action'=>'link',$action,'com_name_txt@phage*',$dataset), array('class'=>'_class', 'id'=>'_id'))."</pre>
		<p>or</p>
		<pre class=\"code\">ec_id:1.1* " .$this->Html->link('test it', array('controller'=>'search','action'=>'link',$action, 'ec_id@1.1*',$dataset), array('class'=>'_class', 'id'=>'_id'))."</pre>
		<p>or</p>
		<pre class=\"code\">blast_species:Chlamydia* ". $this->Html->link('test it', array('controller'=>'search','action'=>'link',$action,'blast_species@Chlamydia*',$dataset), array('class'=>'_class', 'id'=>'_id'))."</pre>
		<p>or Blast E-Value exponent >= 50</p>
		<pre class=\"code\">blast_evalue_exp:{50 TO *} ". $this->Html->link('test it', array('controller'=>'search','action'=>'link',$action, 'blast_evalue_exp@{50 TO *}',$dataset), array('class'=>'_class', 'id'=>'_id'))."</pre>
		<p>or NCBI taxonomy taxon 2 (Bacteria) </p>
		<pre class=\"code\">blast_tree:2 ". $this->Html->link('test it', array('controller'=>'search','action'=>'link',$action,'blast_tree@2',$dataset), array('class'=>'_class', 'id'=>'_id'))."</pre>
		<p>or combinations of several fields</p>
		<pre class=\"code\">blast_evalue_exp:{50 TO *} AND com_name_txt:\"structural protein\" ". $this->Html->link('test it', array('controller'=>'search','action'=>'link',$action,"blast_evalue_exp@{50 TO *} AND com_name_txt@\"structural protein\"",$dataset), array('class'=>'_class', 'id'=>'_id'))."</pre>
		More information can be found 
		<a href=\"http://lucene.apache.org/java/2_3_2/queryparsersyntax.html\" target=\"blank\">here</a>.</p>
		</div>");
	}
	function compare($divId) {
		echo("<div id=\"$divId\" title=\"Compare Datasets Help Dialog\">
				<BR>	
				<b>Select Datasets</b>
				<BR>	
				<p>Choose datsets from the left subpanel either by clicking on the + symbol or by dragging it into the left subpanel. You can narrow down the list in the rigth subpanel by entering a search term into the
				text box above the list.<p>
				<BR>	
				<b>Filter Datasets</b>{$this->searchParagraph}
				<BR>	
				<b>Compare Options</b>
				<BR>	
				<p>
				<table>
				<tr><td valign=\"top\">
				<ul>
				<h5>Summary</h5>
				<BR>
					<li><strong>absolute counts</strong> displays the sum of peptides/weights that fall into a certain category.</li>
					<li><strong>relative counts</strong> displays the sum of peptides/weights that fall into a certain category 
				devided by the dataset's overall sum of peptides/weights.</li>
					<li><strong>heatmap counts</strong> displays a colored representation
				of relative row counts. The relative row count is the row count devided by the sum of all row counts of a category across all datasets.</li>
				</ul>
				</td><td valign=\"top\">
				<h5>Statistical Tests (2 datasets)</h5>
				<BR>
					<li><strong>Equality of Proportions Test</strong> Tests if the difference in the proportion of a certain feature for two datasets is significant. This is a large sample approximation test and can only accurately be applied to categories with at least five absolute counts (Min. Count is set to 5).</li>
					<li><strong>Chi-Square Test of Independence</strong> Tests association between rows and column features. In a two by two case it mirrows the proportion test. This is a large sample approximation test and can only accurately be applied to categories with at least five absolute counts (Min. Count is set to 5).</li>
					<li><strong>Fishers Exact Test</strong> Tests association between between rows and columns features using the hypergeometric distribution. This is an exact test that can be applied to cell counts below 5. Note that this test takes much longer tahn any of the two approximate tests.</li>
				</ul>
				</td><td valign=\"top\">
				<h5>Statistical Tests (2 populations)</h5>
				<BR>
					<li><strong>Wilcoxon Rank Sum Test</strong> Non-parametric test to compare two sample populations.</i></li>
					<li><strong>METASTATS</strong> modified non-parametric t-test to compare two sample populations (White et al.).</i></li>
				</ul>
				</td><td valign=\"top\">				
				<h5>Hierarchical Clustering Plot</h5>
				<BR>
				<ul>	
					<li><strong>Complete Linkage Cluster Plot</strong> uses the maximum distance of two merged clusters for the next clustering iteration.</li>
					<li><strong>Average Linkage Cluster Plot</strong> uses average distance of two merged clusters for the next clustering iteration
(tends to find spherical clusters with equal variance).</li>
					<li><strong>Single Linkage Cluster Plot</strong> uses minimum distance of two merged clusters for the next clustering iteration
(tends to have less bias for detecting highly elongated or irregular shaped clusters).</li>
					<li><strong>Wards Minimum Variance Cluster Plot </strong>tends to find spherical clusters with approximately the same number
of observations in each cluster</li>
					<li><strong>Median Cluster Plot</strong>  uses median distance of two merged clusters for next clustering iteration..</li>
					<li><strong>McQuitty Cluster Plot</strong></li>
					<li><strong>Centroid Cluster Plot</strong></li>
					</li>
				</ul></td>
				<td valign=\"top\">
				<h5>Other Plot</h5>
				<BR>
				<ul>	
					<li><strong>Multidimensional Scaling Plot</strong> applies non-metric multidimensional scaling to project differences between samples
onto a two dimensional space where samples that are close are more similar than those that a farther apart.</li>
					<li><strong>Heatmap Plot</strong> provides quick visual impression of differences between datasets and
categories. Differences are highlighted by a color gradient and dendrograms (tree like structures) that
are added to the left and to the top axis. Click on the download button to
download both sets of euclidean distances</li>
					<li><strong>Mosaic Plot</strong> plots rectangles for each datasets category combination proportional to the absolute counts.</li>
				</ul></td>				
				</tr>
				
				</p>
				<BR>
				</table>				
				<BR>	
				<b>Tabs</b><BR>
				<p>
				Click on a tab in the tab panel to switch between different categories. For each selected tab, the level of comparison can be adjusted by selected the desired level from the drop down menu.
				</p>
				<BR>
				<b>Download</b><BR>
				<p>
				Comparison results can be downloaded by clicking on the disk with the green arrow at the left upper corner of the tab panel.".$this->Html->image("download-small.png")."
				<p>
				
		</div>");
	}
	
	function blast($divId) {
		echo("<div id=\"$divId\" title=\"Blast Datasets Help Dialog\">
				<BR>	
				<b>Select Datasets</b>
				<BR>	
				<p>Choose datsets from the left subpanel either by clicking on the + symbol or by dragging it into the left subpanel. You can narrow down the list in the rigth subpanel by entering a search term into the
				text box above the list.<p>
				<BR>	
				<b>Enter Sequence</b>
				<BR>	
				<p>Enter your sequence (one only).<p>								
				<BR>	
				<b>Filter Datasets</b>{$this->searchParagraph}
				<BR>	
				</table>				
				<BR>	
				<b>Tabs</b><BR>
				<p>
				Click on a tab in the tab panel to switch between different categories. For each selected tab, the level of comparison can be adjusted by selected the desired level from the drop down menu.
				</p>
				<BR>
				<b>Download </b>
				<p>
				Blast results can be downloaded by clicking on the disk with the green arrow at the left upper corner of the tab panel.".$this->Html->image("download-small.png")."
				<p>
				
		</div>");
	}	
	function browseTaxonomy($divId) {
		echo("<div id=\"$divId\" title=\"Browse Taxonomy Help Dialog\">
				
		<b>Filter</b>{$this->searchParagraph}
		<BR>		
		<p><B>Browse Taxonomy</B>
		Expand a taxon in the tree on the left hand side by clicking it.
		For each tree level the taxon name, its rank and the number of peptides are shown. <BR><BR>
		<B>Taxon Summaries:</B> For each selected taxon, various functional and taxonomic assignments are summarized on the right hand side.
		Counts reflect the number of peptides that have a taxonomic assignment that belongs to the lineage of the selected taxon.
		Percentages reflect the proportion of peptides in that selected taxon that fall into a certain category.</p>
		<BR>	
		<b>Download</b>	
		<p>
		Browse results can be downloaded by clicking on the disk with the green arrow at the left upper corner of the panels on the right hand side.".$this->Html->image("download-small.png")."
		</p>		
		</div>");
	}
	function browseEnzymes($divId) {
			echo("<div id=\"$divId\" title=\"Browse Enzymes Help Dialog\">
			<b>Filter</b>{$this->searchParagraph}
			<BR>				
			<p><B>Browse Enzymes</B>
			Expand an enzyme class in the tree on the left hand side by clicking it. 
			For each enzyme level the enzyme class name, its level and the number of peptides are shown. <BR><BR>
			<B>Enzyme Class Summaries:</B> For each selected enzyme class, various functional and taxonomic assignments
			are summarized on the right hand side. Counts reflect the number of peptides that have an EC number assignment
			that belongs to the selected class. Percentages reflect the proportion of peptides in that selected class that
			fall into a certain category.</p>
			<BR>	
			<b>Download</b>	
			<p>
			Browse results can be downloaded by clicking on the disk with the green arrow at the left upper corner of the panels on the right hand side.".$this->Html->image("download-small.png")."
			</p>			
			</div>");
	}
	function browseKeggPathways($divId) {
			echo("<div id=\"$divId\" title=\"Browse Kegg Pathways Help Dialog\">
			<b>Filter</b>{$this->searchParagraph}
			<BR>				
			<p><B>Browse Kegg Pathways</B>
			Expand a pathway class, pathway or an enzyme in the tree on the left hand side by clicking it. 
			For each pathway level the pathway name, its level and the number of hits are shown. <BR><BR>
			<B>Kegg Pathway Summaries:</B> For each selected pathway, or enzyme various functional and taxonomic assignments
			are summarized on the right hand side. Pathway maps highlight enzymes that have been found in the respective dastaset. A distribution 
			of individual enzymes is shown below the maps. 
			Counts reflect the number of peptides that have an EC number assignment
			that belongs to the selected pathway or pathway group. Percentages reflect the proportion of peptides in the selected class that
			fall into a certain category.</p>
			<BR>	
			<b>Download</b>	
			<p>
			Browse results can be downloaded by clicking on the disk with the green arrow at the left upper corner of the panels on the right hand side.".$this->Html->image("download-small.png")."
			</p>			
			</div>");
	}	
	function browseMetacycPathways($divId) {
			echo("<div id=\"$divId\" title=\"Browse Metacyc Pathways Help Dialog\">
			<b>Filter</b>{$this->searchParagraph}
			<BR>				
			<p><B>Browse Metacyc Pathways</B>
			Expand a pathway class, pathway or an enzyme in the tree on the left hand side by clicking it. 
			For each pathway level the pathway name, its level and the number of hits are shown. <BR><BR>
			<B>Metacyc Pathway Summaries:</B> For each selected pathway, or enzyme various functional and taxonomic assignments
			are summarized on the right hand side. Metacyc pathway maps are shown on the pathway level. A distribution 
			of individual enzymes is shown below the maps. 
			Counts reflect the number of peptides that have an EC number assignment
			that belongs to the selected pathway or pathway group. Percentages reflect the proportion of peptides in the selected class that
			fall into a certain category.</p>
			<BR>	
			<b>Download</b>	
			<p>
			Browse results can be downloaded by clicking on the disk with the green arrow at the left upper corner of the panels on the right hand side.".$this->Html->image("download-small.png")."
			</p>			
			</div>");
	}	
	function browseGeneOntology($divId) {
			echo("<div id=\"$divId\" title=\"Browse Gene Ontology Help Dialog\">
			<b>Filter</b>{$this->searchParagraph}
			<BR>				
			<p><B>Browse Gene Ontology</B>
			 Expand a GO class in the tree on the left hand side by clicking it. 
			 For each GO class, the class accession, name and the number of peptides are shown.
			 <BR><BR><B>Gene Ontology Class Summaries:</B> For each GO class, various functional
			 and taxonomic assignments are summarized on the right hand side. Counts reflect the number of peptides
			 that have a GO assignment that belongs to the selected class or is a child of it. Percentages reflect the
			 proportion of peptides in that selected class that fall into a certain category.</p>
			<BR>	
			<b>Download</b>	
			<p>
			Browse results can be downloaded by clicking on the disk with the green arrow at the left upper corner of the panels on the right hand side.".$this->Html->image("download-small.png")."
			</p>			 
			</div>");
	}	
	
}
?>



