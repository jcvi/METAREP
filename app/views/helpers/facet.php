<?php
/***********************************************************
* File: facet.php
* Description: The facet Helper class defines methods
* that help to visualize Solr facet results. It supports
* facet tables, bar charts, pie charts and other special
* layouts.
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

class FacetHelper extends AppHelper {

	var $uses = array('Go');
	

	private $pieChartColor = 'F99D31,6DB33F,00A4E4,E31B23'; ## orange, ## green
		
	//private $pieChartColor = "5C4033";
	
	function printFacet($facet,$results,$hits) {
		$html = null;
		$html .= "<b>$facet</b><BR><ol>";
		foreach($results as $class=>$count) {	
			
			$class = str_replace('_empty_','unassigned',$class);
			$perc = (float) round(($count/$hits)*100,2);
			
			$numDec = strlen(substr(strrchr($count, "."), 1));
			if($numDec == 0) {
				$count = number_format($count,0);		
			}
			else {
				$count = number_format($count,WEIGHTED_COUNT_PRECISION);
			}
			$html.= "<li><B>$class</B> ($perc%) ($count)</li>";
		}
		$html.= "</ol>";
		return $html; 
	}
		
	//returns an image for once count/slice
	function barChartSlice($percPos,$percNeg) {
		$fgColor='cccccc';
		$bgColor='f4f4f4';
		//$bgColor='ffffff';
				
		$html ='';		
		$html .= "<img src=\"http://chart.apis.google.com/chart?cht=bhs&chs=400x15&chco=$fgColor,$bgColor&chbh=20&chd=t:$percPos|$percNeg\">";
		return $html; 		
	}

	//returns an image for once count/slice
	function barChartSliceMinMax($count,$min,$max) {
		$fgColor='cccccc';
		$bgColor='f4f4f4';
		//$bgColor='ffffff';
			
		$html ='';		
		$html .= "<img src=\"http://chart.apis.google.com/chart?cht=bhs&chs=400x15&chco=$fgColor,$bgColor&chbh=20&chd=t:$count&chds=$min,$max\">";
		return $html; 		
	}	

	function twoWayConfidenceInterval($meanA,$varA,$meanB,$varB) {
		$html ='';		
		$html .= "http://chart.apis.google.com/chart?chs=175x75&cht=bhs&chd=t0:-1,5,10|-1,11,12|-1,11,12|-1,16,19|-1,12,14&chm=F,0000FF,0,1:4,5&chxr=0,1";		
	}
	
	
	function table($facet,$results,$hits) {
		$html='';
		#arsort($results,SORT_NUMERIC);
		
		$counter=1;
		$i=0;
		$html .="<div style=\"text-align:left;padding-top:2px; padding-bottom:5px;font-weight:bold; border-width:1px;border-bottom-style:solid;font-size:1.2em;background-color:#FFFFFF;\">$facet</div>";
		
		$html .='<table cellpadding="10px" cellspacing="0">	
				<tr>	
					<th>#Rank</th>
					<th>Class</th>
					<th>#Hits</th>
					<th>%Hits</th>
					<th>Bar Chart</th>
				</tr>';
			
		foreach ($results as $funClass=>$count) {	
			
			$funClass = wordwrap($funClass, 120, "<br/>");
			#$funClass = str_replace('_empty_','unassigned',$funClass);

			$perc = round($count/$hits,4)*100;		
			
			//handle rounding issues
			if($perc > 100) {
				$perc = 100;
			}
			
			$class = null;

			
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}						
			
			$html.="<tr  $class>";
			$html.="<td style=\"width:3%;text-align:right\">".$counter."</td>";
			$html.="<td><span style=\"white-space: nowrap; width:10%\">".$funClass."</span></td>";
			$html.="<td style=\"width:6%;text-align:right\">".$count."</td>";
			$html.="<td style=\"width:6%;text-align:right\">".$perc." %</td>";
			$html.="<td style=\"width:45%\"align=\"center\" width=\"0px\">".$this->barChartSlice($perc,100)."</td>";	
			$html.='</tr>';
			$counter++;
		}
		$html.= '</table><div>';
		return $html;
	}
	
	function pathwayTable($data) {
		$html='';
		
		$i=0;
		
		foreach ($data as $level2=>$pathways) {	
			$counter=1;
		$html .="<div style=\"text-align:left;padding-top:10px; padding-bottom:5px;font-weight:bold; border-width:1px;border-bottom-style:solid;font-size:1.2em;background-color:#FFFFFF;\">$level2</div>";
		
		
		$html.='<table cellpadding="20px" cellspacing="0">	
				<tr>	
					<th>Rank</th>
					<th>Class</th>
					<th>#Hits</th>
					<th>%Hits</th>
					<th>#Pathway Enzymes</th>
					<th>#Found Enzymes</th>
					<th>%Found Enzymes</th>
					
					<th>Bar Chart (% Found Enzymes)</th>
				</tr>';
			
		
		
			$count=1;
			$class = null;

			
			foreach($pathways as $pathway) {
				$class = null;				
				if ($i++ % 2 == 0) {
					$class = ' class="altrow"';
				}	
				
				$html.="<tr  $class>";
				$html.="<td style=\"width:2%;text-align:right\">$counter</td>";
				$html.="<td style=\"width:30%;text-align:left\"><a href=\"javascript: void(0)\"onclick=\"window.open('{$pathway['pathwayUrl']}')\" class=\"tooltip\" title=\"Open Kegg Pathway Map\">{$pathway['pathwayName']}</a></td>";
				$html.="<td style=\"width:6%;text-align:right\">{$pathway['numPeptidesPathway']}</td>";
				$html.="<td style=\"width:6%;text-align:right\">{$pathway['percPeptidesPathway']}  %</td>";
				$html.="<td style=\"width:6%;text-align:right\">{$pathway['numPathwayEnzymes']}</td>";
				$html.="<td style=\"width:6%;text-align:right\">{$pathway['numFoundEnzymes']}</td>";
				$html.="<td style=\"width:6%;text-align:right\">{$pathway['percFoundEnzymes']}  %</td>";
				$html.="<td style=\"width:30%\"align=\"center\" width=\"0px\">".$this->barChartSlice($pathway['percFoundEnzymes'],100)."</td>";	
				$html.='</tr>';
				$counter++;
				
			}
			$html.= '</table>';
			
		}
		
		return $html;
	}

	function summaryTable($sumary) {
		$html='';
		
		$i=0;
		
		foreach ($sumary as $summaryName=>$summaryEntry) {		
		$counter=1;
		$html .="<div style=\"text-align:left;padding-top:10px; padding-bottom:5px;font-weight:bold; 
		border-width:1px;border-bottom-style:solid;font-size:1.2em;background-color:#FFFFFF;\">$summaryName</div>";
				
		$html.='<table cellpadding="20px" cellspacing="0">	
				<tr>	
					<th>Evidence</th>				
					<th>Unassigned</th>
					<th>%Unassigned</th>					
					<th>Assigned</th>
					<th>%Assigned</th>
					<th>Bar Chart (% Assigned)</th>
				</tr>';
					
			$count=1;
			$class = null;

			
			foreach ($summaryEntry as $field=>$entry) {	
			
				$class = null;				
				if ($i++ % 2 == 0) {
					$class = ' class="altrow"';
				}	
				
				$html.="<tr  $class>";
				$html.="<td style=\"width:10%;text-align:left\">{$entry['name']}</td>";
				$html.="<td style=\"width:6%;text-align:right\">{$entry['totalUnassigned']}</td>";				
				$html.="<td style=\"width:6%;text-align:right\">{$entry['percUnassigned']}  %</td>";				
				$html.="<td style=\"width:6%;text-align:right\">{$entry['totalAssigned']}</td>";
				$html.="<td style=\"width:6%;text-align:right\">{$entry['percAssigned']}  %</td>";

				$html.="<td style=\"width:20%; text-align:left\">".$this->barChartSlice($entry['percAssigned'],100)."</td>";	
				$html.='</tr>';
				$counter++;
				
			}
			$html.= '</table>';
		}
			
		return $html;
	}	

	function oneFacetTable($facetCounts,$facetName,$numHits) {
		arsort($facetCounts);
		
		$html='';
		
		$i=0;
		$counter=1;
				
		$html .="<div style=\"text-align:left;padding-top:15px; padding-bottom:5px;font-weight:bold; border-width:1px;border-bottom-style:solid;font-size:1.2em;background-color:#FFFFFF;\">$facetName Distribution</div>";
		
		
		$html.="<table cellpadding=\"20px\" cellspacing=\"0\">	
				<tr>	
					<th>Rank</th>
					<th>$facetName</th>
					<th>#Hits</th>
					<th>%Hits</th>
					<th>Bar Chart (% Found $facetName)</th>
				</tr>";
		
		foreach ($facetCounts as $name=>$count) {	
				$class = null;				
				if ($i++ % 2 == 0) {
					$class = ' class="altrow"';
				}				
			
			$percHits = round(($count/$numHits)*100,2);
			
			$html.="<tr  $class>";
			$html.="<td>$counter</td>";
			$html.="<td>$name</td>";
			$html.="<td style=\"text-align:right\">$count</td>";
			$html.="<td style=\"width:8%;text-align:right\">$percHits %</td>";
			$html.="<td style=\"width:30%\"align=\"center\" width=\"0px\">".$this->barChartSlice($percHits ,100)."</td>";	
			$html.='</tr>';
			$counter++;
		}
			
		$html.= '</table>';
			
		return $html;
	}	
	
	function enzymeTable($enzymes,$numHits) {
		arsort($enzymes);
		
		$html='';
		
		$i=0;
		$counter=1;
				
		$html .="<div style=\"text-align:left;padding-top:15px; padding-bottom:5px;font-weight:bold; border-width:1px;border-bottom-style:solid;font-size:1.2em;background-color:#FFFFFF;\">Enzyme Distribution</div>";
		
		
		$html.='<table cellpadding="20px" cellspacing="0">	
				<tr>	
					<th>Rank</th>
					<th>Enzyme</th>
					<th>#Hits</th>
					<th>%Hits</th>
					<th>Bar Chart (% Found Enzymes)</th>
				</tr>';
		foreach ($enzymes as $enzyme=>$count) {	
				$class = null;				
				if ($i++ % 2 == 0) {
					$class = ' class="altrow"';
				}				
			
			$percHits = round(($count/$numHits)*100,2);
			
			$html.="<tr  $class>";
			$html.="<td>$counter</td>";
			$html.="<td>$enzyme</td>";
			$html.="<td style=\"text-align:right\">$count</td>";
			$html.="<td style=\"width:8%;text-align:right\">$percHits %</td>";
			$html.="<td style=\"width:30%\"align=\"center\" width=\"0px\">".$this->barChartSlice($percHits ,100)."</td>";	
			$html.='</tr>';
				$counter++;
		}
			
		$html.= '</table>';
			
		return $html;
	}
		
	function barChartRelative($facet,$results,$hits) {	
		//http://chart.apis.google.com/chart?cht=bhs&chs=200x125&chco=4d89f9,c6d9fd&chbh=20&chd=t:10,50,60,80,40|50,60,100,40,20
		$sum   = 0;
		$html = '';
		$html .= "<b>$facet</b><BR><ol>";
		$html .= "<img src=\"http://chart.apis.google.com/chart?cht=bhs&chs=100x225&chco=4d89f9,c6d9fd&chbh=15&chd=t:";
		//<img src="http://chart.apis.google.com/chart?chco=4D89F9&chs=950x250&cht=p&chd=t:&chl">
		
		foreach($results as $class=>$count) {					
			$perc = (float) round(($count/$hits)*100,0);	
			$html .= $perc.",";
			$sum +=$perc; 			
		}
		
		$html = substr_replace($html ,"",-1);
		
		$html .= "|";
		
		foreach($results as $class=>$count) {					
			$perc = (float) round(($count/$hits)*100,0);	
			$html .= (100-$perc).",";
			$sum +=$perc; 			
		}
		$html = substr_replace($html ,"",-1);

		
		$html.="\">";
		$html.= "</ol>";
		return $html;
	}

	function barChartAbsolute($facet,$results,$size="600x425") {	
		//http://chart.apis.google.com/chart?cht=bhs&chs=200x125&chco=4d89f9,c6d9fd&chbh=20&chd=t:10,50,60,80,40|50,60,100,40,20
		$sum   = 0;
		$html = '';
		$html .= "<b>$facet</b><BR><ol>";
		$html .= "<img src=\"http://chart.apis.google.com/chart?cht=bvs&chs=600x425&chco=4d89f9,c6d9fd&chbh=4&chd=t:";
		//<img src="http://chart.apis.google.com/chart?chco=4D89F9&chs=950x250&cht=p&chd=t:&chl">
		

		$maxCount=0;
		$minCount=0;
				
		foreach($results as $class=>$count) {	
			
			//die(print_r($results));
			if($class == '_empty_') {				
				continue;
			}
			else if($maxCount== 0) {
				$maxCount = $count;
				$html .= $count.",";
				//die("SZIE".count($results));
			}
			elseif($count== end($results)) {
				$minCount = $count;
				$html .= $count.",";
			}
			$html .= $count.",";
			
		}
		
		$html = substr_replace($html ,"",-1);
		$html.="&chds=$minCount,$maxCount";
		
		$html.="\">";
		$html.= "</ol>";
		return $html;
	}	
	
	function pieChart($facet,$results,$hits,$size="600x150"){
				
		$sortedResults = (array) $results;
		ksort($sortedResults);
		
		$colors = $this->getPieChartColors(count($sortedResults));
		
		$sum = 0;
		$html = null;
		$html .= "<b>$facet</b><BR><ol>";
		$html .= "<img src=\"http://chart.apis.google.com/chart?chco=$colors&chs=".$size."&cht=p&chd=t:";
		//<img src="http://chart.apis.google.com/chart?chco=4D89F9&chs=950x250&cht=p&chd=t:&chl">
		
		foreach($sortedResults as $class => $count) {					
			$perc = (float) round(($count/$hits)*100,0);	
			$html .= $perc.",";
			$sum += $perc; 			
		}
		
		//add last category (other)
		$percOther = (float) round((100-$sum));		
		
		
		if($percOther>0) {
			$html .= $percOther;
		}
		else {
			$html = substr_replace($html ,"",-1);
		}
		
		$html .= "&chl=";
		foreach($sortedResults as $class => $count) {
			
			
			$class = split('\\|',$class);
				
			$class= urlencode($class[0]);
			
			if(strlen($class)>30) {
				$class = substr($class,0,29);
				$class.="...";
			}
			
			$html .= $class."|";
		}
		//remove last '|'
		//$html = substr_replace($html ,"",-1);
		
		if($percOther>0) {
			$html .= "other|";
		}
		else {
			$html = substr_replace($html ,"",-1);
		}
		
		$html.="\">";
		$html.= "</ol>";
		return $html;
	}
	
	function topTenList($facets,$facetFields,$numHits) {
		$facetFieldCount 	= count($facetFields);
		$facetFieldCounter 	= 1;
		$style = "";
		
		$html =  "
		<div class=\"top 10 facets form\">
			<fieldset>
				<legend>Top Ten Functional Classifications</legend>
					<div class=\"top-10-list\">
						<table cellpadding=\"0\" cellspacing=\"10\" border =0 valign=\"TOP\" style=\"border-style:none !important;\">
							<tr>";
							foreach($facetFields as $facetField => $facetName) { 
							
								//last column 
								if($facetFieldCounter == $facetFieldCount) { 
									$style = "style=\"border-right:none;\"";
								}
	
								$html .= "<td valign=\"TOP\" $style>".
											$this->printFacet($facetName,$facets->facet_fields->{$facetField},$numHits).
										 "</td>";
								
								$facetFieldCounter++;
							}
							$html .="
							</tr>
						</table>
					</div>
			</fieldset>
		</div>";
		return $html;
	}	


	
	function topTenPieCharts($facets,$facetFields,$numHits,$sizeLarge="600x200",$sizeSmall="600x150") {
		$html = "<div class=\"top-10-pie-charts\">
					<fieldset>
						<legend>Top Ten Functional Pie Charts</legend>
							<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-style:none !important;\" >
								<tr><td valign=\"TOP\">";
		
							$facetFieldIds 	= array_keys($facetFields);

							for($i =0; $i < count($facetFieldIds);$i++) {
								$id 	= $facetFieldIds[$i];
								$name 	= $facetFields[$id];
							
								if($i == 0) {
									$html .= $this->pieChart($name,$facets->facet_fields->{$id},$numHits,$sizeLarge);
								}
								else if($i == 1) {
									$html .= $this->pieChart($name,$facets->facet_fields->{$id},$numHits,$sizeLarge).
									"</td><td style=\"border-right:none\">";
								}
								else if($i > 1) {
									$html .= $this->pieChart($name,$facets->facet_fields->{$id},$numHits,$sizeSmall);
								}			
							}
		
		$html .= 			"</td>
						</tr>
					</table>
				</legend>
			</fieldset>
		</div>";
		
		return $html;
		
	}	

	function topTenPieChartsWeighted($facets,$numHits,$sizeLarge="600x200",$sizeSmall="600x150") {
		return "<div class=\"top 10 facets form\">
				<fieldset>
					<legend>Top Ten Functional Pie Charts</legend>
						<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-style:none !important;\" >
							<tr>
								<td valign=\"TOP\">".$this->pieChart('Species (Blast)',$facets->facet_fields->blast_species,$numHits,$sizeLarge)
										.$this->pieChart('Kegg Ortholog',$facets->facet_fields->ko_id,$numHits,$sizeLarge)."</td>
								<td style=\"border-right:none\">"	.$this->pieChart('Ec number',$facets->facet_fields->ec_id,$numHits,$sizeSmall)
										.$this->pieChart('Gene Ontology',$facets->facet_fields->go_id,$numHits,$sizeSmall).
								"</td>
								</tr>
						</table>
						</legend>
				</fieldset>
			</div>";
	}	
	
	function topTenMetaInformationList($facets,$numHits) {
		return "
		<div class=\"top 10 facets form\">
			<fieldset>
				<legend>Top Ten Meta-Information</legend>
					<table cellpadding=\"0\" cellspacing=\"10\" valign=\"TOP\" style=\"border-style:none !important;\" >
						<tr>
							<td valign=\"TOP\">".$this->printFacet('Project',$facets['project'],$numHits)."</td>
							<td valign=\"TOP\">".$this->printFacet('Sample Habitat',$facets['habitat'],$numHits)."</td>
							<td valign=\"TOP\">".$this->printFacet('Sample Filter',$facets['filter'],$numHits)."</td>
							<td valign=\"TOP\">".$this->printFacet('Sample Depth',$facets['depth'],$numHits)."</td>
							<td valign=\"TOP\" style=\"border-right:none\">".$this->printFacet('Sample Location',$facets['location'],$numHits)."</td>
			</tr>
			</table>
			</fieldset>
		</div>";
	}		
	
	function topTenMetaInformationPieCharts($facets,$numHits,$sizeLarge="600x200",$sizeSmall="600x150") {
		return "<div class=\"top 10 facets form\">
				<fieldset>
					<legend>Top Ten Meta-Information Pie Charts</legend>
						<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-style:none !important;\">
							<tr>
								<td valign=\"TOP\">"
										.$this->pieChart('Project',$facets['project'],$numHits,$sizeLarge)
										.$this->pieChart('Sample Habitat',$facets['habitat'],$numHits,$sizeLarge)."</td>
								<td style=\"border-right:none\">"	.$this->pieChart('Sample Filter',$facets['filter'],$numHits,$sizeSmall).
										 $this->pieChart('Sample Depth',$facets['depth'],$numHits,$sizeSmall)."</td>".
								"</td>
								</tr>
						</table>
						</legend>
				</fieldset>
			</div>";
	}	
	
	private function getPieChartColors($categoryCount) {
		$colorArray = explode(',',$this->pieChartColor);
		arsort($colorArray);
		return  implode(',',array_slice($colorArray, 0,$categoryCount));
	}
}

?>



