<?php
/***********************************************************
* File: pivot_controller.php
* Description: Allows to relate two annotation data types to 
* one another.
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

class ItolController extends AppController {
	var $name 		= 'Itol';	
	var $helpers 	= array('Dialog','Ajax');
	var $uses 		= array();
	#var $uses 		= array('Project','Library','Population');
	var $components = array('Solr','RequestHandler','Session','Matrix','Format');	

//	var $treeOfLife = array(
//		'Escherichia coli EDL933,
//		'Escherichia coli O157:H7',
//		'Escherichia coli O6,
//		'Escherichia coli K12,
//		'Shigella flexneri 2a 2457T,
//		'Shigella flexneri 2a 301,
//		'Salmonella enterica,
//		'Salmonella typhi,
//		'Salmonella typhimurium,
//		'Yersinia pestis Medievalis,
//		'Yersinia pestis KIM,
//		'Yersinia pestis CO92,
//		'Photorhabdus luminescens,
//		14 Blochmannia floridanus,
//		15 Wigglesworthia brevipalpis,
//		16 Buchnera aphidicola Bp,
//		17 Buchnera aphidicola APS,
//		18 Buchnera aphidicola Sg,
//		19 Pasteurella multocida,
//		20 Haemophilus influenzae,
//		21 Haemophilus ducreyi,
//		22 Vibrio vulnificus YJ016,
//		23 Vibrio vulnificus CMCP6,
//		24 Vibrio parahaemolyticus,
//		25 Vibrio cholerae,
//		26 Photobacterium profundum,
//		27 Shewanella oneidensis,
//		28 Pseudomonas putida,
//		29 Pseudomonas syringae,
//		30 Pseudomonas aeruginosa,
//		31 Xylella fastidiosa 700964,
//		32 Xylella fastidiosa 9a5c,
//		33 Xanthomonas axonopodis,
//		34 Xanthomonas campestris,
//		35 Coxiella burnetii,
//		36 Neisseria meningitidis A,
//		37 Neisseria meningitidis B,
//		38 Chromobacterium violaceum,
//		39 Bordetella pertussis,
//		40 Bordetella parapertussis,
//		41 Bordetella bronchiseptica,
//		42 Ralstonia solanacearum,
//		43 Nitrosomonas europaea,
//		44 Agrobacterium tumefaciens Cereon,
//		45 Agrobacterium tumefaciens WashU,
//		46 Rhizobium meliloti,
//		47 Brucella suis,
//		48 Brucella melitensis,
//		49 Rhizobium loti,
//		50 Rhodopseudomonas palustris,
//		51 Bradyrhizobium japonicum,
//		52 Caulobacter crescentus,
//		53 Wolbachia sp. wMel,
//		54 Rickettsia prowazekii,
//		55 Rickettsia conorii,
//		56 Helicobacter pylori J99,
//		57 Helicobacter pylori 26695,
//		58 Helicobacter hepaticus,
//		59 Wolinella succinogenes,
//		60 Campylobacter jejuni,
//		61 Desulfovibrio vulgaris,
//		62 Geobacter sulfurreducens,
//		63 Bdellovibrio bacteriovorus,
//		64 Acidobacterium capsulatum,
//		65 Solibacter usitatus,
//		66 Fusobacterium nucleatum,
//		67 Aquifex aeolicus,
//		68 Thermotoga maritima,
//		69 Thermus thermophilus,
//		70 Deinococcus radiodurans,
//		71 Dehalococcoides ethenogenes,
//		72 Nostoc sp. PCC 7120,
//		73 Synechocystis sp. PCC6803,
//		74 Synechococcus elongatus,
//		75 Synechococcus sp. WH8102,
//		76 Prochlorococcus marinus MIT9313,
//		77 Prochlorococcus marinus SS120,
//		78 Prochlorococcus marinus CCMP1378,
//		79 Gloeobacter violaceus,
//		80 Gemmata obscuriglobus,
//		81 Rhodopirellula baltica,
//		82 Leptospira interrogans L1-130,
//		83 Leptospira interrogans 56601,
//		84 Treponema pallidum,
//		85 Treponema denticola,
//		86 Borrelia burgdorferi,
//		87 Tropheryma whipplei TW08/27,
//		88 Tropheryma whipplei Twist,
//		89 Bifidobacterium longum,
//		90 Corynebacterium glutamicum 13032,
//		91 Corynebacterium glutamicum,
//		92 Corynebacterium efficiens,
//		93 Corynebacterium diphtheriae,
//		94 Mycobacterium bovis,
//		95 Mycobacterium tuberculosis CDC1551,
//		96 Mycobacterium tuberculosis H37Rv,
//		97 Mycobacterium leprae,
//		98 Mycobacterium paratuberculosis,
//		99 Streptomyces avermitilis,
//		100 Streptomyces coelicolor,
//		101 Fibrobacter succinogenes,
//		102 Chlorobium tepidum,
//		103 Porphyromonas gingivalis,
//		104 Bacteroides thetaiotaomicron,
//		105 Chlamydophila pneumoniae TW183,
//		106 Chlamydia pneumoniae J138,
//		107 Chlamydia pneumoniae CWL029,
//		108 Chlamydia pneumoniae AR39,
//		109 Chlamydophila caviae,
//		110 Chlamydia muridarum,
//		111 Chlamydia trachomatis,
//		112 Thermoanaerobacter tengcongensis,
//		113 Clostridium tetani,
//		114 Clostridium perfringens,
//		115 Clostridium acetobutylicum,
//		116 Mycoplasma mobile,
//		117 Mycoplasma pulmonis,
//		118 Mycoplasma pneumoniae,
//		119 Mycoplasma genitalium,
//		120 Mycoplasma gallisepticum,
//		121 Mycoplasma penetrans,
//		122 Ureaplasma parvum,
//		123 Mycoplasma mycoides,
//		124 Phytoplasma Onion yellows,
//		125 Listeria monocytogenes F2365,
//		126 Listeria monocytogenes EGD,
//		127 Listeria innocua,
//		128 Oceanobacillus iheyensis,
//		129 Bacillus halodurans,
//		130 Bacillus cereus ATCC 14579,
//		131 Bacillus cereus ATCC 10987,
//		132 Bacillus anthracis,
//		133 Bacillus subtilis,
//		134 Staphylococcus aureus MW2,
//		135 Staphylococcus aureus N315,
//		136 Staphylococcus aureus Mu50,
//		137 Staphylococcus epidermidis,
//		138 Streptococcus agalactiae III,
//		139 Streptococcus agalactiae V,
//		140 Streptococcus pyogenes M1,
//		141 Streptococcus pyogenes MGAS8232,
//		142 Streptococcus pyogenes MGAS315,
//		143 Streptococcus pyogenes SSI-1,
//		144 Streptococcus mutans,
//		145 Streptococcus pneumoniae R6,
//		146 Streptococcus pneumoniae TIGR4,
//		147 Lactococcus lactis,
//		148 Enterococcus faecalis,
//		149 Lactobacillus johnsonii,
//		150 Lactobacillus plantarum,
//		151 Thalassiosira pseudonana,
//		152 Cryptosporidium hominis,
//		153 Plasmodium falciparum,
//		154 Oryza sativa,
//		155 Arabidopsis thaliana,
//		156 Cyanidioschyzon merolae,
//		157 Dictyostelium discoideum,
//		158 Eremothecium gossypii,
//		159 Saccharomyces cerevisiae,
//		160 Schizosaccharomyces pombe,
//		161 Anopheles gambiae,
//		162 Drosophila melanogaster,
//		163 Takifugu rubripes,
//		164 Danio rerio,
//		165 Rattus norvegicus,
//		166 Mus musculus,
//		167 Homo sapiens,
//		168 Pan troglodytes,
//		169 Gallus gallus,
//		170 Caenorhabditis elegans,
//		171 Caenorhabditis briggsae,
//		172 Leishmania major,
//		173 Giardia lamblia,
//		174 Nanoarchaeum equitans,
//		175 Sulfolobus tokodaii,
//		176 Sulfolobus solfataricus,
//		177 Aeropyrum pernix,
//		178 Pyrobaculum aerophilum,
//		179 Thermoplasma volcanium,
//		180 Thermoplasma acidophilum,
//		181 Methanobacterium thermautotrophicum,
//		182 Methanopyrus kandleri,
//		183 Methanococcus maripaludis,
//		184 Methanococcus jannaschii,
//		185 Pyrococcus horikoshii,
//		186 Pyrococcus abyssi,
//		187 Pyrococcus furiosus,
//		188 Archaeoglobus fulgidus,
//		189 Halobacterium sp. NRC-1,
//		190 Methanosarcina acetivorans,
//		191 Methanosarcina mazei
	
	var $taxonomyLevels = array(
		'root' 		=> 'root',
		'kingdom' 	=> 'kingdom',
		'class' 	=> 'class',
		'phylum' 	=> 'phylum',
		'order' 	=> 'order',
		'family' 	=> 'family',
		'genus' 	=> 'genus',
	);	
	
//	function index($dataset,$filter='*:*',$facetField='blast_tree',$rank='order') {
//		
//		$this->loadModel('Taxonomy');
//		
//		$taxonResults = $this->Taxonomy->find('all', array('conditions' => array('Taxonomy.rank' => $rank,'Taxonomy.is_shown'=>1),'fields' => array('taxon_id','name')));
//		
//		$facetQueries = array();
//		$counts = array();
//		$taxIds = array();
//		
//		foreach($taxonResults as $taxonResult) {
//			$id = $taxonResult['Taxonomy']['taxon_id'];
//			$name = $taxonResult['Taxonomy']['name'];
//			$counts[$id]['name'] 	= $name;	
//			$counts[$id]['sum'] 	= 0;	
//			array_push($facetQueries,"$facetField:$id");	
//			array_push($taxIds,$id);	
//		}		
//		
//		#debug($counts);
//			
//		$facetQueryChunks = array_chunk($facetQueries,6700);
//		
//		foreach($facetQueryChunks as $facetQueryChunk) {
//				
//			//specify facet behaviour (fetch all facets)
//			$solrArguments = array(	"facet" => "true",
//			'facet.mincount' => 1,
//			'facet.query' => $facetQueryChunk,
//			"facet.limit" => -1);	
//			try	{
//				$result 	  = $this->Solr->search($dataset,$filter,0,0,$solrArguments);		
//				debug($result )		;	
//			}
//			catch(Exception $e){
//				die('exception');
//				$this->set('exception',SOLR_CONNECT_EXCEPTION);
//				$this->render('/compare/result_panel','ajax');
//			}
//			
////			if($this->Project->isWeighted($dataset)) {  
////				$facets = $result;
////			}
////			else {
//			$facets = $result->facet_counts->facet_queries;
//			//}
//			
//			foreach($facets as $facetQuery =>$count) {
//				$tmp 	= explode(":", $facetQuery);
//				$id 	= $tmp[1];	
//				if($count > 0) {
//					$counts[$id][$dataset] = $count;	
//					$counts[$id]['sum'] += $count;
//				}
//			}
//		}
//		
//		debug($counts);
//		
//		$this->writeTaxonIds($taxIds);			
//	}
	
	private function writeTaxonIds($taxonIds) {
		$result = ' ';
		$id 	= time();
		
		$outFile   = "jcvi_metagenomics_report_itol_tax_ids_".$id;	
		$fh = fopen(METAREP_TMP_DIR."/$outFile", 'w');
		
		foreach($taxonIds as $taxon) {
			
			if($taxon == 32568) {
				$taxon = 40677;
			}
			if($taxon == 41327) {
				next();
			}
			if($taxon == 55640) {
				next();
			}
			fwrite($fh,$taxon."\n");
		}
		fclose($fh);	

		debug(PERL_PATH." ".METAREP_WEB_ROOT."/scripts/perl/iTOL/iTOL_uploader.pl --ncbiFile ".METAREP_TMP_DIR."/$outFile");
		exec(PERL_PATH." ".METAREP_WEB_ROOT."/scripts/perl/iTOL/iTOL_uploader.pl --ncbiFile ".METAREP_TMP_DIR."/$outFile");
		
	}
}
?>