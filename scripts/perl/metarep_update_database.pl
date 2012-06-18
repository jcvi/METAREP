#!/usr/local/bin/perl

###############################################################################
# File: metarep_update_database.pl
# Description: utility script to update METAREP SQLite3 and MySQL databases 
# using information provided by NCBI (Taxonomy), KEGG, and Gene Ontology.
#
# METAREP : High-Performance Comparative Metagenomics Framework (http://www.jcvi.org/metarep)
# Copyright(c)  J. Craig Venter Institute (http://www.jcvi.org)
#
# Licensed under The MIT License
# Redistributions of files must retain the above copyright notice.
#
# link http://www.jcvi.org/metarep METAREP Project
# package metarep
# version METAREP v 1.3.1
# author Johannes Goll
# lastmodified 2012-06-18
# license http://www.opensource.org/licenses/mit-license.php The MIT License
###############################################################################

use strict;
use warnings;
use DBI;
use File::Basename;
use Cwd ;
use File::Listing qw(parse_dir);
use POSIX qw(strftime);
use Log::Log4perl qw(:easy);
use LWP::Simple;
use Net::FTP;
use Getopt::Long qw(:config no_ignore_case no_auto_abbrev);
use Pod::Usage;

=head1 NAME
metarep_update_database.pl utility script to update METAREP SQLite3 and MySQL databases.
Uses information provided by NCBI (Taxonomy), KEGG, and Gene Ontology.
			
=head1 SYNOPSIS

perl metarep_update_database.pl 
	--kegg_dir=/tmp/kegg_current 
	--output_dir=/tmp/kegg-out 
	--sqlite3_bin=/usr/local/bin/sqlite3 
	--update_db=23 
	
=head1 OPTIONS

B<--update_db, -u, default=123 >
	databases to update 
	1 KEGG
	2 NCBI_TAXONOMY
	3 GO
	
	to update all use 123

B<--kegg_dir, -k>
	root directory of the local KEGG FTP installation
	
	it accesses the following files/folders
	
	genes/fasta/genes.pep
	genes/organisms
	genes/ko/ko
	ligand/enzyme/enzyme [enzyme information, lowest level]
	genes/genome/genome
	brite/ko/ko01000.keg [enzyme hierarchy]
	brite/ko/ko00001.keg [ko pathways]
	pathway/ec/ec.list  [ec pathways]

B<--output_dir, -d>
	directory to store the tab delimited files and databases
	
B<--sqlite3_bin, -s>
	path to the sqlite3 executable

=head1 AUTHOR

Johannes Goll  C<< <jgoll@jcvi.org> >>

=cut

my %args = ();

## handle user arguments
GetOptions(
	\%args,                
	'version', 	
	'update_db|u=s',
	'kegg_dir|k=s',
	'output_dir|o=s',
	'sqlite3_bin|s=s',
	'help|man|?',
) || pod2usage(2);

if($args{help}) {
	pod2usage(-exitval => 1, -verbose => 2);
}

## command line argument validation
if(!defined($args{output_dir})) {
	pod2usage(
		-message => "\n\nVALIDATION EXCEPTION: A valid output directory needs to be defined.\n",
		-exitval => 1,
		-verbose => 1
	);
}
if(defined($args{sqlite3}) && !-e $args{sqlite3_path}) {
	pod2usage(
		-message => "\n\nVALIDATION EXCEPTION: A valid sqlite3 path needs to be defined.\n",
		-exitval => 1,
		-verbose => 1
	);
}


if(!defined($args{update_db})) {
	$args{update_db} = '123';
}

## if KEGG is part of the update_db list, check if kegg files exist
if($args{update_db}=~ m/1/) {
	if(!defined($args{kegg_dir}) || !-d $args{kegg_dir} ) {
		pod2usage(
			-message => "\n\nVALIDATION EXCEPTION: A valid KEGG directory needs to be defined.\n",
			-exitval => 1,
			-verbose => 1
		);		
	} 
	else {
		if(! -e "$args{kegg_dir}/genes/fasta/genes.pep") {
			pod2usage(
				-message => "\n\nMISSING FILE EXCEPTION: missing $args{kegg_dir}/genes/fasta/genes.pep.\n",
				-exitval => 1,
				-verbose => 1
			);				
		}
		if(! -d "$args{kegg_dir}/genes/organisms") {
			pod2usage(
				-message => "\n\nMISSING FOLDER EXCEPTION: missing $args{kegg_dir}/genes/organisms.\n",
				-exitval => 1,
				-verbose => 1
			);				
		}		
		if(! -e "$args{kegg_dir}/genes/ko/ko") {
			pod2usage(
				-message => "\n\nMISSING FILE EXCEPTION: missing $args{kegg_dir}/genes/ko/ko.\n",
				-exitval => 1,
				-verbose => 1
			);				
		}	
		if(! -e "$args{kegg_dir}/ligand/enzyme/enzyme") {
			pod2usage(
				-message => "\n\nMISSING FILE EXCEPTION: missing $args{kegg_dir}/ligand/enzyme/enzyme.\n",
				-exitval => 1,
				-verbose => 1
			);				
		}
		if(! -e "$args{kegg_dir}/genes/genome/genome") {
			pod2usage(
				-message => "\n\nMISSING FILE EXCEPTION: missing $args{kegg_dir}/genes/genome/genome.\n",
				-exitval => 1,
				-verbose => 1
			);				
		}
		if(! -e "$args{kegg_dir}/brite/ko/ko01000.keg") {
			pod2usage(
				-message => "\n\nMISSING FILE EXCEPTION: missing $args{kegg_dir}/brite/ko/ko01000.keg.\n",
				-exitval => 1,
				-verbose => 1
			);				
		}	
		if(! -e "$args{kegg_dir}/brite/ko/ko00001.keg") {
			pod2usage(
				-message => "\n\nMISSING FILE EXCEPTION: missing $args{kegg_dir}/brite/ko/ko00001.keg.\n",
				-exitval => 1,
				-verbose => 1
			);				
		}
		if(! -e "$args{kegg_dir}/pathway/ec/ec.list") {
			pod2usage(
				-message => "\n\nMISSING FILE EXCEPTION: missing $args{kegg_dir}/pathway/ec/ec.list.\n",
				-exitval => 1,
				-verbose => 1
			);				
		}		
	}
}

if(!defined($args{sqlite_binary})) {
	$args{sqlite_binary} = '/usr/local/bin/sqlite3';
}

## Gene Ontology File URL
my $geneOntologyFileUrl = 'http://archive.geneontology.org/latest-termdb/go_daily-termdb-tables.tar.gz';

## NCBI Taxonomy FTP URL
my $ncbiTaxonomyFtpUrl  = 'ftp.ncbi.nih.gov';

my $ftp =undef;
my $inputDir  = &trim($args{kegg_dir});
my $outputDir = &trim($args{output_dir});
my ($nodes,$pathwayExtIds,$pathwayIntIds) = undef;
my $logger = &getLogger($outputDir);	
my $counter = 1;
my $cwd = getcwd();

chdir($outputDir);

## execute parsing steps

if($args{update_db} =~ m/2/) {
	&ncbiTaxonomy();
} 

if($args{update_db} =~ m/3/) {
	&geneOntology();
}

if($args{update_db} =~ m/1/) {
	&gene2desc();
	&gene2ec();
	&gene2ko();
	&ko2go();
	&taxon2ncbi(); 
	&ko2desc();
	&ko2go(); 
	&briteKo(); 
	&briteEc(); 
	&enzymes(); 
}

### create sqlite database and load data
&createSQliteDatabase();

## create table statistics
&executeSql();

## create scrip to update the mysql production database
&createMySqlImportScript();

## switch back to orginal directory
chdir($cwd);

###############################################
# parses gene descriptions
###############################################

sub gene2desc() {
	if(! -e "$outputDir/gene2desc.tab") {		
		$logger->info("generating gene => description mappings ...");	
		## parse defline and generate tab delimited format
		open FILE,"$inputDir/genes/fasta/genes.pep";
		open OUTFILE,">$outputDir/gene2desc.tab";
		
		while(<FILE>) {
			chomp;
			if(m/^>/){
				s/^>//;
				my ($keggGeneId,$defline) = split(" ",$_,2);
				$keggGeneId = &trim($keggGeneId);
				$defline = &trim($defline); 
				print OUTFILE "$keggGeneId\t$defline\n";
			}
		}
		close FILE;
		close OUTFILE;
	}
	else {
		$logger->info("skipping gene => description mappings: file $outputDir/genes2desc.tab already exists.");
	}
}

###############################################
# downloads Gene Ontology tables
###############################################

sub geneOntology() {
	if(! -e "$outputDir/term.tab") {
		$logger->info("downloading GO files...");
		my $file = "$outputDir/go_daily-termdb-tables.tar.gz";
		getstore($geneOntologyFileUrl, $file);
		`tar -xvf $file`;
		`mv go_daily-termdb-tables/term.txt $outputDir/go_term.tab`;
		`mv go_daily-termdb-tables/graph_path.txt $outputDir/go_graph_path.tab`;
		`rm -rf $outputDir/go_daily-termdb-tables`;
		`rm $outputDir/go_daily-termdb-tables.tar.gz`;
	}
	else {
		$logger->info("skipping GO files...");
	}
}

###############################################
# parses gene => KO mappings
###############################################

sub gene2ko() {	
	if(!-e "$outputDir/gene2ko.tab") {
		$logger->info("generating gene => KO mappings ...");
		my @organismDirs = split(/\n/,`find $inputDir/genes/organisms -type d`);			
		foreach my $organismDir(@organismDirs) {
			if($organismDir ne 'organisms') {
				my $organism = basename($organismDir);
				my $enzymeFile = $organism."_ko.list";
				$logger->info("Concatinating ko file $enzymeFile ...");
				$logger->info("sed 's/\tec\:/\t/' >> gene2ko.tab");
				`sed 's/\tko\:/\t/' $organismDir/$enzymeFile >> gene2ko.tab`;
			}			
		}	
	}
	else {
		$logger->info("skipping gene => KO mappings ...");
	}
}

###############################################
# parses gene => ec mappings
###############################################

sub gene2ec() {	
	if(!-e "$outputDir/gene2ec.tab") {
		$logger->info("generating gene => ec mappings ...");
		my @organismDirs = split(/\n/,`find $inputDir/genes/organisms -type d`);
		foreach my $organismDir(@organismDirs) {
			if($organismDir ne 'organisms') {
				my $organism = basename($organismDir);
				my $enzymeFile = $organism."_enzyme.list";
				$logger->info("Concatinating enzyme file $enzymeFile ...");
				$logger->info("sed 's/\tec\:/\t/' >> gene2ec.tab");
				`sed 's/\tec\:/\t/' $organismDir/$enzymeFile >> gene2ec.tab`;		
			}	
		}	
	}
	else {
		$logger->info("skipping gene => ec mappings ...");
	}
}

###############################################
# parsed KO => go mappings
###############################################

sub ko2go() {
	if(!-e "$outputDir/ko2go.tab") {
		$logger->info("generating KO => GO mappings ...");
		open(FILE,"$inputDir/genes/ko/ko") || die "ko file does not exist";		
		open (OUT, ">$outputDir/ko2go.tab");				
		my ($ko,$go) = undef;
		my @goIds = undef;	
		while(<FILE>) {
			if(/^ENTRY/) {
				s/^ENTRY//;
				s/KO$//;				
				$ko 	= &trim($_);
				@goIds 	= ();
			}
			if(/.* GO: .*/) {
				s/^DBLINKS//;
				s/GO://;
				my @goSplitIds = split(" ",&trim($_));				
				for my $goId (@goSplitIds) {
					push(@goIds,$goId);					
				}					
			}
			## end of entry
			if(/\/\/\//) {
				for my $goId (@goIds) {
					print OUT "$ko\tGO:$goId\n";
				}
			}
		}		
		close(FILE);
		close(OUT);
	}
	else {
		$logger->info("skipping KO => GO mappings ...");
	}			
}

###############################################
# fetch KO descriptions.
###############################################

sub ko2desc() {
	if(! -e "$outputDir/ko2desc.tab") {
		$logger->info("generating KO descriptions ...\n");		
		## open file
		open(FILE,"$inputDir/genes/ko/ko") || die "ko file does not exist";	
		open (OUT, ">$outputDir/ko2desc.tab");			
		my ($ko,$name,$def,$gene) = undef;		
		while(<FILE>) {
			if(/^ENTRY/) {
				s/^ENTRY//;
				s/KO$//;
				$ko = &trim($_);		
			}
			if(/^NAME/) {
				s/^NAME//;				
				$name = &trim($_);		
			}	
			if(/^DEFINITION/) {
					s/^DEFINITION//;					
				$def = &trim($_);							
			}
			if(/\/\//) {
				if(defined $def ) {
					print OUT "$ko\t$def\n";
				}
				else {
					print OUT "$ko\t$name\n";
				}
				$ko = undef;	
				$name = undef;
				$def = undef;
				$gene = undef;		
			}
		}	
		close(FILE);
		close(OUT);		
	}
	else {
		$logger->info("skipping KO descriptions ...\n");	
	}	
}

###############################################
# parses enzyme information
###############################################

sub enzymes() {		
	$logger->info("generating enzyme descriptions ...\n");	
	## open file
	open(FILE,"$inputDir/ligand/enzyme/enzyme") || die "enzyme file does not exist";				
	open (OUT, ">$outputDir/enzyme.tab") || die "Can not open file";		
	
	my ($ecId,$name) = undef;
	my @names= undef;
	my $isClass = 0;
	my $classSectionStarts = 0;	
	
	while(<FILE>) {							
		## parse enzyme ID		
		if(/^ENTRY/) {
			my $line = $_;
					
			my $match = m/^ENTRY       EC ([-\.0-9]++)/;
								
			$ecId = $1;
					
			if(!$1) {
				$match = m/^ENTRY       EC ([-\.0-9]++).*Enzyme/;
				$ecId = $1;			
			}
			if($ecId =~ m/-/) {
				$isClass=1;
			}
			else {
				$isClass=0;
			}
			@names 	= ();	
			$classSectionStarts =0;			
		}
		if(/^REACTION/ && $isClass) {
			$classSectionStarts = 0;
		}			
		if(/^NAME/ && !$isClass) {
			s/^NAME//;
			push(@names,&trim($_));	
		}
		if(/^CLASS/ && $isClass) {
			$classSectionStarts = 1;
			s/^CLASS//;
			push(@names,&trim($_));	
		}			
		if($classSectionStarts && $isClass) {
			push(@names,&trim($_));	
		}
	
		## end of entry
		if(/\/\/\//) {
			my $lastName = pop(@names);
	
			my $level='';
			my $parent ='';
							
			my ($l0,$l1,$l2,$l3) =split("\\.",$ecId);
								
			$l0 = &trim($l0);
			$l1 = &trim($l1);
			$l2 = &trim($l2);
			$l3 = &trim($l3);
				
			if($l0 ne '-') {
				$level = 'level 4';
				$parent = "$l0.$l1.$l2.-";
			}
			if( $l3 eq '-'){
				$level = 'level 3';
				$parent = "$l0.$l1.-.-";
			}
			if( $l2 eq '-'){
				$level = 'level 2';
				$parent = "$l0.-.-.-";
			}	
			if( $l1 eq '-'){
				$level  = 'level 1';
				$parent = 1;
			}			
			$lastName = &trim($lastName);
			$lastName =~ s/;$/./;	
			
			if($level eq 'level 4')	 {
				print OUT "$l0.$l1.$l2.$l3\t$parent\t$level\t$lastName\n";		
			}		
		}
	}	
	close(FILE);
	open(FILE,"$inputDir/brite/ko/ko01000.keg") || die "File 'ko01000.keg' does not exist.";
			
 	my $level = '';
 	my $parent = ''; 
	my $enzymeHash = ();
	
	while(<FILE>) {
		my ($ecId,$name) = undef;
		
		if(/^[ABC]/)	{
			
			if(/^A/)	{					
				($ecId,$name) = split(/\s++/,$_,2);					
				$ecId =~ s/A<b>//;
				$name =~ s/<\/b>//;
			}			
			else {										
				(undef,$ecId,$name) = split(/\s++/,$_,3);
			}
		
			my ($l0,$l1,$l2,$l3) =split("\\.",$ecId);
					unless($l1) {
				$l1 = '-';
			}	
			unless($l2) {
				$l2 = '-';
			}	
			unless($l3) {
				$l3 = '-';
			}
			
			$l0 = &trim($l0);
			$l1 = &trim($l1);
			$l2 = &trim($l2);
			$l3 = &trim($l3);
																
			if($l0 ne '-') {
				$level = 'level 4';
				$parent = "$l0.$l1.$l2.-";
			}
			if( $l3 eq '-'){
				$level = 'level 3';
				$parent = "$l0.$l1.-.-";
			}
			if( $l2 eq '-'){
				$level = 'level 2';
				$parent = "$l0.-.-.-";
			}	
			if( $l1 eq '-'){
				$level  = 'level 1';
				$parent = 1;
			}			
			$name = &trim($name);
			$name =~ s/;$/./;	
			
			## handle duplicates
			if(! exists $enzymeHash->{"$l0.$l1.$l2.$l3"}) {
				print OUT "$l0.$l1.$l2.$l3\t$parent\t$level\t$name\n";			
			 	$enzymeHash->{"$l0.$l1.$l2.$l3"} = undef;
			}								
		}						
	}					
	close(FILE);		
	print OUT "1\t0\troot\troot\n";
	close(OUT);									
}

###############################################
# parses KEGG taxon => NCBI taxon mappings
###############################################

sub taxon2ncbi() {	
	$logger->info("generating KEGG taxon => NCBI taxon mappings...");
	my $keggTaxon = undef;
	my $ncbiTaxon = undef;
	my $short	  = undef;
	
	## open file
	open(FILE,"$inputDir/genes/genome/genome") || die "File 'genome' does not exist.";		
	open (OUT, ">$outputDir/kegg_taxon.tab");				
	
	while(<FILE>) {
		chomp;			
		if(/^NAME/) {
			my $line = $_;
			$line =~ s/^NAME//g;
			$line =~ s/ //g;
			$line = &trim($line);
			
			($keggTaxon,undef,$short,$ncbiTaxon) = split(',',$line); 	
			
			if(!$ncbiTaxon) {
				$ncbiTaxon = $short;
			}
		} 
		if(/\/\/\//) {
			print OUT "$keggTaxon\t$ncbiTaxon\n";
		}
	}	
	close(FILE);
	close(OUT);			
}

###############################################
# parses NCBI taxonomy
###############################################

sub ncbiTaxonomy() {
	
	$logger->info("generating ncbi taxonomy...");
	&ftpConnect($ncbiTaxonomyFtpUrl,'anonymous','');
		
	my $ftpTimestamp = undef;
	my $nodeFile = "$outputDir/nodes.dmp";
	my $nameFile = "$outputDir/names.dmp";
	my $outFile  = "$outputDir/ncbi_taxon.tab";
	
	$ftp->cwd('/pub/taxonomy'); 
	
	my $ls = $ftp->dir();
	foreach my $entry (parse_dir($ls)) {
	    my ($name, $type, $size, $mtime, $mode) = @$entry;
	    next unless $type eq 'f';
	       
	    if($name eq 'taxdump.tar.gz') {
	    	$ftpTimestamp = strftime "%F %T", gmtime($mtime);
	    	$logger->info("File $name has an mtime of $ftpTimestamp");
	    }
	}
	
	## set mode to binary	    
	$ftp->get('taxdump.tar.gz');
	
	##  extract tar file
	$logger->info("tar -xvf  taxdump.tar.gz");
	`tar -xvf  taxdump.tar.gz`;	
	
	open(NODES,$nodeFile);
	
	my $nodes = undef;
	
	while(<NODES>) {
		chomp;
		my @fields = split("\\|") ;
		my $entry = undef;
		
		my $divisionId = &trim($fields[4]);
				
		my $taxonId 		= &trim($fields[0]);	
		$entry->{parent_id} = &trim($fields[1]);
		$entry->{rank} 		= &trim($fields[2]);
		$entry->{is_shown} 	= &trim($fields[10]);	
		$nodes->{$taxonId} = $entry;
	}
	close(NODES);
	
	open (OUT, ">$outFile");	
	
	open(NAMES,$nameFile);	
	
	while(<NAMES>) {
		chomp;
		
		my @fields = split("\\|") ;
			
		my $taxonId 	= &trim($fields[0]);
		my $name		= &trim($fields[1]);
		my $class 		= &trim($fields[3]);
		
		if($class eq 'scientific name') {
			my $nodeEntry = $nodes->{$taxonId};
			
			my $parentId = $nodeEntry->{parent_id};
			my $isShown  = $nodeEntry->{is_shown};
			my $rank 	 = $nodeEntry->{rank};
			
			if($parentId) {
				print OUT "$taxonId\t$parentId\t$rank\t$name\t$isShown\n";
			}
		} 
	}
	close(NAMES);	
	close(OUT);	
	
	## clean up
	`rm $outputDir/*.dmp`;
	`rm $outputDir/taxdump.tar.gz`;		
	`rm $outputDir/gc.prt`;
	`rm $outputDir/readme.txt`;
		
}

###############################################
# parses brite hierarchy for KO
###############################################

sub briteKo() {
	$logger->info("generating KO brite mapping...");
	my ($id,$extId,$name,$aLevel,$bLevel,$cLevel,$dLevel,$eLevel) = undef;	
		
	open(FILE,"$inputDir/brite/ko/ko00001.keg") || die "File 'ko00001.keg' does not exist.";
		
	while(<FILE>) {
		if(/^[A-E]{1}.*/) {			
			## level 1
			if(/^A.*/) {						
				($id,$extId,$name,$bLevel,$cLevel,$dLevel,$eLevel) = undef;
				
				$aLevel = $_;
				$aLevel =~ s/^A//;
				$aLevel =~ s/<b>//;
				$aLevel =~ s/<\/b>//;					
				$name= &trim($aLevel);
				
				$aLevel= $counter;	
							
				## create new node
				$nodes->{$aLevel}->{id} 		= $counter;	
				$nodes->{$aLevel}->{parent_id}  = 1;
				$nodes->{$aLevel}->{ext_id} 	= '';
				$nodes->{$aLevel}->{name}   	= $name;
				$nodes->{$aLevel}->{level}  	= 'level 1';
				$nodes->{$aLevel}->{kegg_id}  	= '';
				$nodes->{$aLevel}->{count}  	= 0;
				$counter++;
				
			}
			## super-pathway level
			elsif(/^B.*/ && defined($aLevel)) {			
				$bLevel = $_;
				$bLevel =~ s/^B//;
				$bLevel =~ s/<b>//;
				$bLevel =~ s/<\/b>//;
				
				$name = &trim($bLevel);
				$bLevel = $counter;
						
			
				## create new node
				$nodes->{$bLevel}->{id} 		= $counter;
				$nodes->{$bLevel}->{parent_id}  = $aLevel;
				$nodes->{$bLevel}->{ext_id} 	= '';
				$nodes->{$bLevel}->{name}   	= $name;
				$nodes->{$bLevel}->{level}  	= 'super-pathway';
				$nodes->{$bLevel}->{kegg_id}  	= '';
				$nodes->{$bLevel}->{count}  	= 0;
				$counter++;						
			}
			## pathway level
			elsif(/^C.*\[PATH.*/ && defined($bLevel)) {		
										
				$cLevel = $_;
				$cLevel =~ s/^C//;
				$cLevel =~ /.*([0-9]{5}) (.*) \[PATH.*/;
				
				my $extId =  &trim($1);	
				$name= &trim($2);	
				$cLevel = $counter;
					## create new node
				$nodes->{$cLevel}->{id} 		= $counter;
				$nodes->{$cLevel}->{parent_id}  = $bLevel;
				$nodes->{$cLevel}->{ext_id} 	= $extId;
				$nodes->{$cLevel}->{name}   	= $name;
				$nodes->{$cLevel}->{level}  	= 'pathway';
				$nodes->{$cLevel}->{kegg_id}  	= '';
				$nodes->{$cLevel}->{count}  	= 0;	
						
				$pathwayExtIds->{$extId}   = $counter;
				$pathwayIntIds->{$cLevel} = undef;	
				$counter++;				
			}	
			## kegg-ortholog level		
			elsif(/^D.*/ && defined($cLevel)) {	
				$dLevel = $_;
					
				$dLevel =~ s/^D//;
				
				$dLevel =~ /.*(K[0-9]{5})\s++(.*)/;
				
				my $koId= &trim($1);	
				$name	= &trim($2);
									
				## create new node
				$nodes->{$counter}->{id} 		= $counter;
				$nodes->{$counter}->{parent_id} = $cLevel;
				$nodes->{$counter}->{ext_id} 	= '';
				$nodes->{$counter}->{name}   	= $name;
				$nodes->{$counter}->{level}  	= 'kegg-ortholog';
				$nodes->{$counter}->{kegg_id}  	= $koId;
				$nodes->{$counter}->{count}  	= 1;
				
				## increase parent node count				
				$nodes->{$cLevel}->{count} = $nodes->{$cLevel}->{count}+1;
				$counter++;
			}							
		}
	}	
		
	&printBriteCounts('ko');
	close(FILE);
	close(OUT);	
}

###############################################
# parses brite hierarchy for EC
###############################################

sub briteEc() {	
		$logger->info("generating EC brite mapping...");		
		my ($id,$extId,$name,$aLevel,$bLevel,$cLevel,$dLevel,$eLevel) = undef;	
		
		open(FILE,"$inputDir/pathway/ec/ec.list") || die "File 'ec.list' does not exist.";
			
		while(<FILE>) {
			chomp ;			
			if(/^path:ec.*ec:/) {					
				s/path:ec//;
				s/ec://;	
				my ($pathwayId,$ecId) = split("\t");				
				$pathwayId = &trim($pathwayId);
				$ecId = &trim($ecId);
	
				if(exists $pathwayExtIds->{$pathwayId}) {	
						
					my $parentId = $pathwayExtIds->{$pathwayId};
					
					## create new node
					$nodes->{$counter}->{id} 		= $counter;		
					$nodes->{$counter}->{parent_id} = $parentId;
					$nodes->{$counter}->{ext_id} 	= '';
					$nodes->{$counter}->{name}   	= '';
					$nodes->{$counter}->{level}  	= 'enzyme';
					$nodes->{$counter}->{kegg_id}  	= $ecId;	
					$nodes->{$counter}->{count}  	= 1;				
					
					$nodes->{$parentId}->{count}++;
				}	
				$counter++;
			}
		}
		&printBriteCounts('ec');
		close(FILE);
		close(OUT);		
}

###############################################
# 	Trims white spaces at the end and beginning
#   of a string.
###############################################

sub trim() {
	my $tmp = shift;
	## remove white spaces
	$tmp =~ s/^\s+//g;
	$tmp =~ s/\s+$//g;
	return $tmp;
}

###############################################
# 	Writes KEGG Brite hierarchy counts.
###############################################

sub printBriteCounts() {
	my $mode = shift;
	my $superPathwayIntIds = undef;
	my $levelOnePathwayIntIds = undef;
	
	open (OUT, ">$outputDir/pathway_$mode.tab");	
	
	## print KO/EC
	while ( my ($id, $node) = each(%$nodes) ) {
		if($mode eq 'ko') {		
			if($node->{level} eq 'kegg-ortholog') {
				my $node = 	$nodes->{$id};		
				print OUT "$node->{id}\t$node->{name}\t$node->{parent_id}\t$node->{level}\t$node->{ext_id}\t$node->{kegg_id}\t$node->{count}\n";
			}
		}
		elsif($mode eq 'ec') {		
			if($node->{level} eq 'enzyme') {
				my $node = 	$nodes->{$id};		
				print OUT "$node->{id}\t$node->{name}\t$node->{parent_id}\t$node->{level}\t$node->{ext_id}\t$node->{kegg_id}\t$node->{count}\n";
			}		
		}
	}		
	
	## print pathways	
	while ( my ($id, undef) = each(%$pathwayIntIds) ) {
		if($nodes->{$id}->{count} > 0) {			
			my $node = 	$nodes->{$id};		
			print OUT "$node->{id}\t$node->{name}\t$node->{parent_id}\t$node->{level}\t$node->{ext_id}\t$node->{kegg_id}\t$node->{count}\n";
			my $parentId = $nodes->{$id}->{parent_id};
			
			## increase count of parent and track parents with counts > 0
			$nodes->{$parentId}->{count} = $nodes->{$parentId}->{count}+1;
			$superPathwayIntIds->{$parentId} = undef;
		}
	}

	## print super-pathways	
	while ( my ($id, undef) = each(%$superPathwayIntIds) ) {
		if($nodes->{$id}->{count} > 0) {			
			my $node = 	$nodes->{$id};		
			print OUT "$node->{id}\t$node->{name}\t$node->{parent_id}\t$node->{level}\t$node->{ext_id}\t$node->{kegg_id}\t$node->{count}\n";
			my $parentId = $nodes->{$id}->{parent_id};
			
			## increase count of parent and track parents with counts > 0
			$nodes->{$parentId}->{count} = $nodes->{$parentId}->{count}+1;
			$levelOnePathwayIntIds->{$parentId} = undef;
		}
	}	

	## print level1-pathways	
	while ( my ($id, undef) = each(%$levelOnePathwayIntIds) ) {		
		if($nodes->{$id}->{count} > 0) {
			my $node = 	$nodes->{$id};		
			print OUT "$node->{id}\t$node->{name}\t$node->{parent_id}\t$node->{level}\t$node->{ext_id}\t$node->{kegg_id}\t$node->{count}\n";
		}
	}
	print OUT "1\troot\t0\troot\t\t\t0\n";
}

###############################################
# 	Creates Sqlite database
###############################################

sub createSQliteDatabase() {
		
	$logger->info("creating SQLite database ...");
	my $keggSqlite3CreateSql  = 
	"
	CREATE TABLE pathway_ko(pathway_id numeric,name text,parent_pathway_id numeric,level text,external_id text,ko_id text,child_count numeric);
	CREATE INDEX pathway_ko_pathway_id_index ON pathway_ko(pathway_id);
	CREATE INDEX pathway_ko_parent_pathway_id_index ON pathway_ko(parent_pathway_id);
	CREATE INDEX pathway_ko_ko_id_index ON pathway_ko(ko_id);
	
	CREATE TABLE pathway_ec(pathway_id numeric,name text,parent_pathway_id numeric,level text,external_id text,ec_id text,child_count numeric);
	CREATE INDEX pathway_ec_pathway_id_index ON pathway_ec(pathway_id);
	CREATE INDEX pathway_ec_parent_pathway_id_index ON pathway_ec(parent_pathway_id);
	CREATE INDEX pathway_ec_ec_id_index ON pathway_ec(ec_id);
	
	CREATE TABLE gene2desc (gene_id text collate nocase, defline text);
	CREATE INDEX gene2desc_gene_id_index_index on gene2desc(gene_id collate nocase);
	
	CREATE TABLE gene2ec (gene_id text collate nocase,ec_id text);
	CREATE INDEX gene2ec_gene_id_index on gene2ec(gene_id collate nocase);
	
	CREATE TABLE gene2ko (gene_id text collate nocase,ko_id text);
	CREATE INDEX gene2ko_gene_id_index on gene2ko(gene_id collate nocase);
	CREATE INDEX gene2ko_ko_id_index on gene2ko(ko_id collate nocase);
	
	CREATE TABLE kegg_taxon(kegg_taxon_id text,ncbi_taxon_id numeric);
	CREATE INDEX kegg_taxon_taxon_id_index on kegg_taxon(kegg_taxon_id);
	
	CREATE TABLE ko2go (ko_id text, go_id numeric);
	CREATE INDEX ko2go_ko_id_index on ko2go(ko_id collate nocase);
	
	CREATE TABLE enzyme (enzyme_id text,parent_enzyme_id text,level text,name text);
	CREATE INDEX enzyme_enzyme_id_index on enzyme(enzyme_id,parent_enzyme_id);
	CREATE VIEW gene2go as select gene_id,go_id from gene2ko as g inner join ko2go as k on(g.ko_id=k.ko_id);";
	
	my $keggSqlite3ImportSql = ".import kegg_taxon.tab kegg_taxon\n.import gene2desc.tab gene2desc\n.import gene2ec.tab gene2ec\n.import gene2ko.tab gene2ko\n.import ko2go.tab ko2go\n.import pathway_ko.tab pathway_ko\n.import pathway_ec.tab pathway_ec\n.import enzyme.tab enzyme\n";
		
	my $ncbiTaxSqlite3CreateSql = "
	CREATE TABLE ncbi_taxon (ncbi_taxon_id numeric, parent_ncbi_taxon_id numeric,rank text,name text,is_shown numeric);
	CREATE INDEX ncbi_taxon_id_index on ncbi_taxon(ncbi_taxon_id);
	CREATE INDEX ncbi_taxon_parent_ncbi_taxon_id_index on ncbi_taxon(parent_ncbi_taxon_id);
	CREATE INDEX ncbi_taxon_name on ncbi_taxon(name collate nocase);";
	
	my $ncbiTaxSqlite3ImportSql = ".import ncbi_taxon.tab ncbi_taxon\n";
	
	my $goSqlite3CreateSql = "
	CREATE TABLE go_term(go_term_id numeric,name text,term_type text, acc text,is_obsolete numeric, is_root numeric, is_relation numeric);
	CREATE INDEX go_term_go_term_id_index on go_term(go_term_id);
	CREATE INDEX go_term_acc_index on go_term(acc);
	
	CREATE TABLE go_graph_path(go_graph_path_id numeric,go_term1_id numeric,go_term2_id numeric,relationship_type_id numeric,distance numeric,relation_distance numeric);
	CREATE INDEX go_graph_path_go_term_id_1 on go_graph_path(go_term1_id);
	CREATE INDEX go_graph_path_go_term_id_2 on go_graph_path(go_term2_id);
	CREATE INDEX go_graph_path_distance_index on go_graph_path(distance);";
	
	my $goSqlite3ImportSql = ".import go_term.tab go_term\n.import go_graph_path.tab go_graph_path\n";
	
	my $sqlite3CreateSql = '';
	my $sqlite3ImportSql = ".mode tabs\n";
	
	if($args{update_db} =~ m/1/) {
		$sqlite3CreateSql .= $keggSqlite3CreateSql;
		$sqlite3ImportSql .= $keggSqlite3ImportSql;
	}
	
	if($args{update_db} =~ m/2/) {
		$sqlite3CreateSql .= $ncbiTaxSqlite3CreateSql;
		$sqlite3ImportSql .= $ncbiTaxSqlite3ImportSql;
	}
	
	if($args{update_db} =~ m/3/) {
		$sqlite3CreateSql .= $goSqlite3CreateSql;
		$sqlite3ImportSql .= $goSqlite3ImportSql;
	}
	
	open (OUT, ">sql");	
	print OUT "$sqlite3CreateSql\n$sqlite3ImportSql";
	close(OUT);
	
	$logger->info("Creating Sqlite3 database \n: $sqlite3CreateSql\n$sqlite3ImportSql");	
	$logger->info("$args{sqlite_binary} kegg.db < sql");	
	
	my $sqliteBinary = $args{sqlite_binary};
	## execute sqlite3 
	`$sqliteBinary metarep.sqlite3.db < sql`;
	
	`rm sql`;
}


###############################################
#  Create SQL script to update MySQL database.
###############################################

sub createMySqlImportScript() {
	open (OUT, ">metarep.mysql.db.update.sql");	

	if($args{update_db} =~ m/1/) {
		print OUT "
			DELETE FROM enzymes;
			LOAD DATA LOCAL INFILE 'enzyme.tab' INTO TABLE enzymes;
			DELETE FROM kegg_orthologs;
			ALTER TABLE kegg_orthologs auto_increment =1 ;
			LOAD DATA LOCAL INFILE 'ko2desc.tab' INTO TABLE kegg_orthologs;				
			DELETE FROM kegg_pathways_ec;
			ALTER TABLE kegg_pathways_ec auto_increment =1 ;
			LOAD DATA LOCAL INFILE 'pathway_ec.tab' INTO TABLE kegg_pathways_ec;
			DELETE FROM kegg_pathways_ko;
			ALTER TABLE kegg_pathways_ko auto_increment =1 ;
			LOAD DATA LOCAL INFILE 'pathway_ko.tab' INTO TABLE kegg_pathways_ko;";			
	}	
	
	if($args{update_db} =~ m/2/) {
		print OUT "
			DELETE FROM taxonomy;
			ALTER TABLE taxonomy auto_increment =1;
			LOAD DATA LOCAL INFILE 'ncbi_taxon.tab' INTO TABLE taxonomy;";
	}
	
	if($args{update_db} =~ m/3/) {
		print OUT "
			DELETE FROM go_graph_path;
			LOAD DATA LOCAL INFILE 'go_graph_path.tab' INTO TABLE go_graph_path;
			DELETE FROM go_term;
			LOAD DATA LOCAL INFILE 'go_term.tab' INTO TABLE go_term";			
	}	
	close(OUT);		
}

sub executeSql() {
	my $db = DBI->connect( "dbi:SQLite:metarep.sqlite3.db",
									 "", "", {PrintError=>1,RaiseError=>1,AutoCommit=>0} );	
	
	if($args{update_db} =~ m/1/) {
		&updatePathwayEcCounts($db);
	}
				
	open (OUT, ">README");	
	print OUT "METAREP Database Update\n";	
	print OUT "-----------------------\n";
	print OUT "Table\tEntries\n";				
				
	my $table =undef;
	my $query ="select name from sqlite_master where type='table'" ;	
	my $sth = $db->prepare($query);
	$sth->execute();
	$sth->bind_col(1, \$table);	
	while($sth->fetch) {		
		my $count = &getCount($db,"select count(*) from $table");
		print OUT "$table\t$count\n";
		$logger->info("$table\tcount");
	}	
	
	print OUT "\nTo update your production METAREP MySQL database, \nexecute the following commands:\n\ncd $args{output_dir}\n";
	print OUT "mysql --local-infile -u <username> -p<password> --host=<host> <database> < metarep.mysql.db.update.sql\n";
	close(OUT);						
}

sub updatePathwayEcCounts() {
	my $db = shift;
	$logger->info("updating pathway_ec table...");		
	
	my ($sth,$sth2,$pathwayId) = undef;
	
	$db->do("update pathway_ec set child_count=0 where level != 'enzyme'");
	$db->commit();

	$sth = $db->prepare("select pathway_id from pathway_ec where level ='pathway'");
	$sth->execute();
	$sth->bind_col(1, \$pathwayId);	
	while($sth->fetch) {
		my	$childCount=	&getCount($db,"select count(*) from pathway_ec where parent_pathway_id=$pathwayId");		
		$db->do("update pathway_ec set child_count=$childCount where pathway_id=$pathwayId");	
		$db->commit();	
	}	
	$db->do("delete from pathway_ec where child_count=0 and level ='pathway'");	
	$db->commit();
	
	$sth = $db->prepare("select pathway_id from pathway_ec where level ='super-pathway'");
	$sth->execute();
	$sth->bind_col(1, \$pathwayId);	
	while($sth->fetch) {
		my	$childCount=	&getCount($db,"select count(*) from pathway_ec where parent_pathway_id=$pathwayId");		
		$db->do("update pathway_ec set child_count=$childCount where pathway_id=$pathwayId");	
		$db->commit();	
	}	
	$db->do("delete from pathway_ec where child_count=0 and level ='super-pathway'");	
	$db->commit();

	$sth = $db->prepare("select pathway_id from pathway_ec where level ='level 1'");
	$sth->execute();
	$sth->bind_col(1, \$pathwayId);	
	while($sth->fetch) {
		my	$childCount=	&getCount($db,"select count(*) from pathway_ec where parent_pathway_id=$pathwayId");		
		$db->do("update pathway_ec set child_count=$childCount where pathway_id=$pathwayId");	
		$db->commit();	
	}	
	$db->do("DELETE FROM pathway_ec where child_count=0 and level ='level 1'");	
	$db->commit();	
	
	$db->do("CREATE TABLE tmp (pathway_id numeric,name text,parent_pathway_id numeric,level text,external_id text,ec_id text,child_count numeric)");	
	$db->commit();	
	
	## update enzyme names using the enzyme table
	$db->do("INSERT INTO tmp SELECT pathway_id,CASE WHEN p.level='enzyme' THEN e.name ELSE p.name END AS name,parent_pathway_id,p.level AS level, external_id,ec_id,child_count FROM pathway_ec AS p LEFT JOIN enzyme AS e ON(enzyme_id=ec_id);");	
	$db->commit();	
	
	$db->do("DELETE FROM  pathway_ec");
	$db->commit();		

	$db->do("INSERT INTO pathway_ec SELECT * FROM tmp");
	$db->commit();		
	
	$db->do("DROP TABLE tmp");
	$db->commit();	
		
	$logger->info("Overrideing old pathway_ec tab ...");
	`$args{sqlite_binary} metarep.sqlite3.db 'select * from pathway_ec' | sed 's/|/\t/g' > pathway_ec.tab`;
}

sub getCount() {
	my ($db,$sql) = @_;
	my $count =undef;
	my $sth = $db->prepare($sql);
	$sth->execute();
	$sth->bind_col(1, \$count);
	$sth->fetch;		
	return $count;
}


###############################################
# 	Connect to FTP site (used for NCBI Taxonomy).
###############################################

sub ftpConnect() {
	my ($host,$login,$password) = @_;
	$logger->info("Connecting to $host site...");
	
	## connect to KEGG FTP site
	$ftp = Net::FTP->new($host, Debug => 1,Passive => 0)
	      or die "Cannot connect to some.host.name: $@";
		      
	#	$ftp->login('anonymous','')
	#	      or $logger->die("Cannot login $ftp->message");
	$ftp->login($login,$password) or $logger->info("Cannot login $ftp->message");
	      	      
	$logger->warn("Failed to set binary mode") unless $ftp->binary();      
}

###############################################
# 	Returns log4perl logger instance
###############################################

sub getLogger() {
	my ($logDir) = @_;	
	Log::Log4perl->easy_init(
		{ level => $DEBUG, file => ">>$logDir/metarep.sqlite3.db.update.log" });
	return get_logger();
}
