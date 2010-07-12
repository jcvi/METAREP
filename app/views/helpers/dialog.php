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
* @version METAREP v 1.0.1
* @author Johannes Goll
* @lastmodified 2010-07-09
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
**/

class DialogHelper extends AppHelper {


	var $helpers = array('Html');	
	
	function printSearch($divId, $dataset='GOMVIR') {
		echo("<div id=\"$divId\" title=\"METAREP Lucene Query Syntax\">	
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
		<pre class=\"code\">com_name_txt:phage* ". $this->Html->link('test it', array('controller'=>'search',$dataset,'com_name_txt@phage*',1), array('class'=>'_class', 'id'=>'_id'))."</pre>
		<p>or</p>
		<pre class=\"code\">ec_id:1.1*" .$this->Html->link('test it', array('controller'=>'search', $dataset, 'ec_id@1.1*',1), array('class'=>'_class', 'id'=>'_id'))."</pre>
		<p>or</p>
		<pre class=\"code\">blast_species:Chlamydia*". $this->Html->link('test it', array('controller'=>'search',$dataset,'blast_species@Chlamydia*',1), array('class'=>'_class', 'id'=>'_id'))."</pre>
		<p>or Blast E-Value exponent >= 50</p>
		<pre class=\"code\">blast_evalue_exp:{50 TO *}". $this->Html->link('test it', array('controller'=>'search',$dataset, 'blast_evalue_exp@{50 TO *}',1), array('class'=>'_class', 'id'=>'_id'))."</pre>
		<p>or NCBI taxonomy taxon 2 (Bacteria) </p>
		<pre class=\"code\">blast_tree:2". $this->Html->link('test it', array('controller'=>'search',$dataset,'blast_tree@2',1), array('class'=>'_class', 'id'=>'_id'))."</pre>
		<p>or combinations of several fields</p>
		<pre class=\"code\">blast_evalue_exp:{50 TO *} AND com_name_txt:\"structural protein\" ". $this->Html->link('test it', array('controller'=>'search',$dataset,"blast_evalue_exp@{50 TO *} AND com_name_txt@\"structural protein\"",1), array('class'=>'_class', 'id'=>'_id'))."</pre>
		More information can be found 
		<a href=\"http://lucene.apache.org/java/2_3_2/queryparsersyntax.html\" target=\"blank\">here</a>.</p>
		</div>");
	}
	function compare($divId) {
		echo("<div id=\"$divId\" title=\"METAREP Multi Dataset Comparisons\">
				<BR>	
				<b>Select Datasets</b>
				<BR>	
				<p>Choose datsets from the left subpanel either by clicking on the + symbol or by dragging it into the left subpanel. You can narrow down the list in the rigth subpanel by entering a search term into the
				text box above the list.<p>
				<BR>	
				<b>Filter Datasets</b>
				<p>The 'Filter Datasets Panel' allows you to filter all data sets using fielded data. When defining a filter you can either specify a field, or use the default field (com_name).
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
				</p>
				<BR>	
				<b>Options</b>
				<BR>	
				<p>
				<table>
				<tr><td valign=\"top\">
				<ul>
				<h5>Summary</h5>
				<BR>
					<li><strong>absolute counts</strong> displays the absolute number of peptides that fall into a certain category.</li>
					<li><strong>relative count</strong> displays the absolute number of peptides that fall into a certain category 
				devided by the dataset's overall number of peptides.</li>
					<li><strong>heatmap</strong> displays a colored representation
				of relative row peptide counts. The relative row peptide count is the relative peptide count devided by the sum of all relative
				peptide counts of a category in a row.</li>
				</ul>
				</td><td valign=\"top\">
				<h5>Statistical Test</h5>
				<BR>
					<li><strong>Chi-Square test of independence</strong> Blast species.</li>
					<li><strong>METASTATS</strong> NCBI taxonomy lineage for blast species.</i></li>
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
				
				
				&#160;&#160;&#160;<strong>Absolute Counts</strong>
				&#160;&#160;&#160;displays the absolute number of peptides that fall into a certain category. 
				&#160;<strong>Relative Counts</strong> displays the absolute number of peptides that fall into a certain category 
				devided by the dataset's overall number of peptides. The <strong>Heatmap</strong> option  The minumum absolute count (<strong>Min. Abs. Count</strong>) allows to filter for categories with equal or more peptides than specified (accross all datasets).
				</p>
				<BR>	
				<b>Tabs</b><BR>
				<p>
				Click on a tab in the tab panel to switch between different categories. For each selected tab, the level of comparison can be adjusted by selected the desired level from the drop down menu.
				</p>
				<BR>
				<b>Download </b>
				<p>
				Comparison results can be downloaded by clicking on the disk with the green arrow at the left upper corner of the tab panel.".$this->Html->image("download-small.png")."
				<p>
				
		</div>");
	}
	
	function browseTaxonomy($divId) {
		echo("<div id=\"$divId\" title=\"METAREP Taxonomy Browser\">
		<p><B>NCBI Taxonomy Tree</B>
		Expand a taxon in the tree on the left hand side by clicking it.
		For each tree level the taxon name, its rank and the number of peptides are shown. <BR><BR>
		<B>Taxon Summaries:</B> For each selected taxon, various functional and taxonomic assignments are summarized on the right hand side.
		Counts reflect the number of peptides that have a taxonomic assignment that belongs to the lineage of the selected taxon.
		Percentages reflect the proportion of peptides in that selected taxon that fall into a certain category.</p>
		</div>");
	}
	function browseEnzymes($divId) {
			echo("<div id=\"$divId\" title=\"METAREP Enzyme Browser\">
			<p><B>Enzymes Classes:</B>
			Expand an enzyme class in the tree on the left hand side by clicking it. 
			For each enzyme level the enzyme class name, its level and the number of peptides are shown. <BR><BR>
			<B>Enzyme Class Summaries:</B> For each selected enzyme class, various functional and taxonomic assignments
			are summarized on the right hand side. Counts reflect the number of peptides that have an EC number assignment
			that belongs to the selected class. Percentages reflect the proportion of peptides in that selected class that
			fall into a certain category.</p>
			</div>");
	}
	function browsePathways($divId) {
			echo("<div id=\"$divId\" title=\"METAREP Pathway Browser\">
			<p><B>Pathway Classes:</B>
			Expand a pathway class, pathway or an enzyme in the tree on the left hand side by clicking it. 
			For each pathway level the pathway name, its level and the number of hits are shown. <BR><BR>
			<B>Pathway Summaries:</B> For each selected pathway, or enzyme various functional and taxonomic assignments
			are summarized on the right hand side. Pathway maps highlight enzymes that have been found in the respective dastaset. A distribution 
			of individual enzymes is shown below the maps. 
			Counts reflect the number of peptides that have an EC number assignment
			that belongs to the selected pathway or pathway group. Percentages reflect the proportion of peptides in the selected class that
			fall into a certain category.</p>
			</div>");
	}	
	function browseGeneOntology($divId) {
			echo("<div id=\"$divId\" title=\"METAREP Gene Ontology Browser\">
			<p><B>Gene Ontology Tree</B>
			 Expand a GO class in the tree on the left hand side by clicking it. 
			 For each GO class, the class accession, name and the number of peptides are shown.
			 <BR><BR><B>Gene Ontology Class Summaries:</B> For each GO class, various functional
			 and taxonomic assignments are summarized on the right hand side. Counts reflect the number of peptides
			 that have a GO assignment that belongs to the selected class or is a child of it. Percentages reflect the
			 proportion of peptides in that selected class that fall into a certain category.</p>
			</div>");
	}	
	
}
?>



