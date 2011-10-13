#! usr/local/bin/perl

###############################################################################
# File: metarep_loader.php
# Description: Loads annotation data into METAREP.

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
# lastmodified 2011-06-02
# license http://www.opensource.org/licenses/mit-license.php The MIT License
###############################################################################

use strict;
use DBI();
use File::Basename;
use Encode;
use utf8;
use Getopt::Long qw(:config no_ignore_case no_auto_abbrev);
use Pod::Usage;

=head1 NAME
metarep_loader.pl generates METAREP lucene indices from various input files.
			
=head1 SYNOPSIS

perl scripts/perl/metarep_loader.pl --project_id 1 --input_dir data/tab --format=tab --sqlite_db db/metarep.sqlite3.db 
--solr_url http://localhost:1234 --solr_home_dir <SOLR_HOME> --solr_instance_dir <SOLR_HOME>/metarep-solr 
--mysql_host localhost --mysql_db ifx_hmp_metagenomics_reports --mysql_username metarep --mysql_password metarep
--tmp_dir /usr/local/scratch 

=head1 OPTIONS
B<--project_id, -i>
	METAREP project id (MySQL table projects, field project_id)			
			
B<--format, -o>
	specified the input mode ('tab','humann','jpmap')

B<--input_file, -f>
	input file. Needs to be specified for mode tab and humann
	
B<--project_dir, -d>
	input directory for JPMAP files. Needs to be specified for mode JPMAP

B<--sqlite_db, -q>
	METAREP SQLite database
	
B<--solr_url, -s>
	METAREP solr server URL incl. port [default: http://localhost:8983]
	
B<--solr_home_dir, -h>
	Solr server home directory (<SOLR_HOME>)

B<--solr_instance_dir, -w>
	Solr instance (configuration) directory (<SOLR_HOME>/metarep-solr)

B<--solr_data_dir, -y>
	Solr index directory [default: <solr-instance-dir>/data]
	
B<--solr_max_mem, -z>
	Solr maximum memory allocation [default: 1G]
	
B<--mysql_host, -s>
	METAREP MySQL host incl. port [default: localhost:3306]

B<--mysql_db, -b>
	METAREP MySQL database name [default: metarep]
	
B<--mysql_username, -u>
	METAREP MySQL username
	
B<--mysql_password, -p>
	METAREP MySQL password

B<--go_db, -g>
	Gene Ontology database name [default: gene_ontology]	

B<--go_username, -e>
	Gene Ontology MySQL username [default: mysql_username]
	
B<--go_password, -f>
	Gene Ontology MySQL password [default: mysql_password]	
	
B<--tmp_dir, -y>
	Directory to store temporary files (XML files gnerated before the Solr load)

B<--xml_only, -x>
	Useful for debugging. Generates only XML files in the specified tmp directory without pushing the data to the Solr server. 

B<--max_num_docs, -x>
	The maximum number of docs to split XML files into.


=head1 AUTHOR

Johannes Goll  C<< <jgoll@jcvi.org> >>

=cut

my $initialJavaHeapSize = '250M';

my %args = ();

## handle user arguments
GetOptions(
	\%args,                
	'version', 	
	'project_id|i=s',
	'format|f=s',
	'project_dir|d=s',
	'sqlite_db|q=s',
	'solr_url|s=s',
	'solr_home_dir|h=s',
	'solr_instance_dir|w=s',
	'solr_data_dir|h=s',
	'solr_max_mem|z=s',
	'mysql_host|m=s',
	'mysql_db|b=s',
	'mysql_username|u=s',
	'mysql_password|p=s',
	'tmp_dir|y=s',	
	'xml_only|x',	
	'help|man|?',
) || pod2usage(2);

#print help
if($args{help}) {
	pod2usage(-exitval => 1, -verbose => 2);
}

if(!defined($args{format})) {
	pod2usage(
		-message => "\n\nERROR: A format needs to be defined.\n",
		-exitval => 1,
		-verbose => 1
	);
}
if(!defined($args{project_dir})) {
	pod2usage(
		-message => "\n\nERROR: Please specify a project directory.\n",
		-exitval => 1,
		-verbose => 1
	);
}
if(!defined($args{project_id})) {
	pod2usage(
		-message => "\n\nERROR: A project id needs to be defined.\n",
		-exitval => 1,
		-verbose => 1
	);
}
elsif(!defined($args{project_dir}) || !-d $args{project_dir}) {
		pod2usage(
			-message =>
"\n\nERROR: A valid input directory needs to be defined.\n",
			-exitval => 1,
			-verbose => 1
		);
}	
elsif(!defined($args{tmp_dir}) || !(-d $args{tmp_dir})) {
	pod2usage(
			-message =>
"\n\nERROR: A valid xml directory needs to be defined.\n",
			-exitval => 1,
			-verbose => 1
	);
}	
elsif(!defined($args{mysql_username})) {
	pod2usage(
			-message =>
"\n\nERROR: A METAREP MySQL username needs to be defined.\n",
			-exitval => 1,
			-verbose => 1
	);
}
elsif(!defined($args{mysql_password})) {
	pod2usage(
			-message =>
"\n\nERROR: A METAREP MySQL password needs to be defined.\n",
			-exitval => 1,
			-verbose => 1
	);
}
elsif(!defined($args{solr_home_dir}) && !$args{xml_only}) {
	pod2usage(
			-message =>
"\n\nERROR: A Solr home directory needs to be defined.\n",
			-exitval => 1,
			-verbose => 1
	);
}
elsif(!defined($args{solr_instance_dir}) && !$args{xml_only}) {
	pod2usage(
			-message =>
"\n\nERROR: A Solr instance directory needs to be defined.\n",
			-exitval => 1,
			-verbose => 1
	);
}
elsif(!defined($args{sqlite_db})) {
	pod2usage(
			-message =>
"\n\nERROR: A sqlite database neeeds to be defined.\n",
			-exitval => 1,
			-verbose => 1
	);
}

if(defined($args{format})) {	
	if($args{format} ne 'tab' && $args{format} ne 'humann'  && $args{format} ne 'jpmap') {
	pod2usage(
			-message =>
"\n\nERROR: A valid input format needs to be specified [metarep or humann or jpmap].\n",
			-exitval => 1,
			-verbose => 1
	);
	}
}

## set global variables
my $koAncestorHash = undef;
my $goAncestorHash = undef;
my $taxonAncestorHash = undef;

## set default arguments
if(!defined($args{solr_url})) {
	$args{solr_url} = "http://localhost:8983";
}
if(!defined($args{mysql_host})) {
	$args{mysql_host} = "localhost:3306";
}
if(!defined($args{mysql_db})) {
	$args{mysql_db} = "metarep";
}
if(!defined($args{solr_max_mem})) {
	$args{solr_max_mem} = '1G';
}
if(!defined($args{solr_data_dir})) {
	$args{solr_data_dir} = "$args{solr_instance_dir}/data";
}
if(!defined($args{max_num_docs})) {
	$args{max_num_docs} = 300000;
}
if(!defined($args{format})) {
	$args{format} = 'metarep';
}

## connect to METAREP MySQL database
print "Trying to connect to MySQL database=".$args{mysql_db}." host=".$args{mysql_host}."\n";
my $metarepDbConnection = DBI->connect_cached("DBI:mysql:".$args{mysql_db}.";host=".$args{mysql_host}."",$args{mysql_username},$args{mysql_password}, { 'RaiseError' => 0 });

if(!$metarepDbConnection) {
		pod2usage(
			-message =>
"\n\nERROR:Could not connect to $args{mysql_db} MySQL database\n",
			-exitval => 1,
			-verbose => 1
	);
}

## connect to sqlite database
print "Trying to connect to Sqlite database=".$args{sqlite_db}."\n";
my $sqliteDbConnection = DBI->connect( "dbi:SQLite:$args{sqlite_db}",
									 "", "", {PrintError=>1,RaiseError=>1,AutoCommit=>0} );	
if(!$sqliteDbConnection) {
		pod2usage(
			-message =>	"\n\nERROR:Could not connect to SQLite database $args{sqlite_db}\n",
			-exitval => 1,
			-verbose => 1
	);
}

if(defined($args{project_dir})) {
	if($args{format} eq 'jpmap') {
		opendir(DIR, $args{project_dir});
		my @libraries = readdir(DIR);
		foreach my $library (@libraries) {
			unless (($library eq ".") || ($library eq "..") )	 {
				 	&createIndexFromJpmapFile("$args{project_dir}/$library");
			}
		}		
	}
	else {
		## read all files in project dir
		opendir(DIR, $args{project_dir});
	
		my @files = grep(/\.$args{format}$/,readdir(DIR));
			
		foreach my $file(@files) {
			print "Processing file $file \n";
				
			if($args{format} eq 'tab') {
				&createIndexFromTabFile("$args{project_dir}/$file");
			}
			elsif($args{format} eq 'humann') {
				&createIndexFromHumannFile("$args{project_dir}/$file");
			}		
		}
	}
}

########################################################
## Parses HUMANnN gene weighted annotations.
########################################################

sub createIndexFromHumannFile() {
	my $file = shift;
	
	my $gzipFlag =0;
	my $hasKo = 0;
	my $isWeighted = 1;
	
	if($file =~ /\.gz$/) {
		`gunzip $file`;
		$gzipFlag =1;
		$file =~ s/\.gz$//;
	}
		
	open FILE, "$file" or die "Could not open file $file.";
		
		## parse dataset name		
		my $datsetName = basename($file);
		$datsetName =~ s/\..*//;
		
		## create index file
		&openIndex($datsetName);
		
		## count documents and XML files
		my $xmlSplitSet  = 2;
		my $numDocuments = 1;	
		
		my ($peptideId,$geneName,$weight,$blastEvalue,$blastPid,$blastCov,$keggTaxon,$ecId,$ecSrc,
		$blastTaxon,$comName,$comNameSrc,$koId,$koSrc,$goId,$goSrc,$goTree,$koTree,$blastTree,$blastSpecies,$blastEvalueExponent);
		
		## foreach line in file create index entry
		while(<FILE>) {
			chomp;
			
			if(m/^#/) {
				next;
			}
			
			$peptideId = $geneName = $weight= $blastEvalue = $blastPid = $blastCov = $keggTaxon = $ecId = $blastTaxon = 
			$ecSrc = $comName = $comNameSrc= $koId = $goId = $goSrc = $goTree= $koTree = $blastTree = $blastSpecies = 
			$blastEvalueExponent = '';
			
			($geneName,$weight,$blastEvalue,$blastPid,$blastCov) = split("\t",$_);
			
			$peptideId = $datsetName.":".$geneName;
			
			($keggTaxon,undef)= split(':',$geneName);
				
			$blastTaxon = &getNcbiTaxonId($keggTaxon);				
			$ecId  		= &getEcId($geneName);
			
			if($ecId) {
				$ecSrc = $geneName;
			}				
				
			$comName 	= &getCommonName($geneName);
			$comNameSrc = $geneName;		
			$koId  		= &getKoId($geneName);
			
			if($koId) {
				$koSrc = $geneName;
			}

			if($koId) {
				my $goResults = &getGoIdsFromKO($koId);
				$goId = uc(&clean($goResults->{go_id_string}));

		       	if($goId) {     
		        	$goTree = join('||',&getGoAncestors($goId));
		       	}
				
				if($goId) {
					$goSrc = $goResults->{go_src_string};
				}
				$koTree = join('||',&getKoAncestors($koId));  
			}
				
	        if($blastTaxon) {
	        	
	        	## find least common ancestor if multiple ids have been provided
	        	if($blastTaxon =~ m/\|\|/) {
	        		my @taxonIds = split(/\|\|/, $blastTaxon);
	        		
	        		#override blastTaxon with least common ancestor
	        		$blastTaxon = &mergeMultiTaxonomicLabels(@taxonIds);      		
	        	}
	        		
	        	## get all parent taxa	
		        my @taxonAncestors = &getTaxonAncestors($blastTaxon);
		        $blastTree 		= join('||',@taxonAncestors);
		        
		        ## set the species if the 
		 		$blastSpecies 	= &getSpecies(\@taxonAncestors);	
	        }	
	
		    if($blastPid =~ m/^[1-9].*/) {
		    	$blastPid = $blastPid/ 100;
		    }
	           
	        ## set Blast Evalue exponent 
			if($blastEvalue == 0) {
				#set evalue to high default value
				$blastEvalueExponent = 9999;
			}	
			else {
				my @tmp = split('e-',lc($blastEvalue));	
				use POSIX qw( floor );
				$blastEvalueExponent  = floor($tmp[1]);
			}

			## add entry to index
	        &addDocument($peptideId,$datsetName,$comName,$comNameSrc,$goId,$goSrc,$goTree,$ecId,$ecSrc,
	        undef,$blastSpecies,$blastEvalue,$blastEvalueExponent,$blastPid,$blastCov,$blastTree,undef,$weight,$koId,$koSrc,$koTree);         

			if(($numDocuments % $args{max_num_docs}) == 0) {
					&nextIndex($datsetName,$xmlSplitSet);
					$xmlSplitSet++;
			} 	
			
			$numDocuments++;	

		}
		
	&closeIndex();
	
	## push index if xml only option has not been selected
	unless($args{xml_only}) {
		&pushIndex($datsetName,$xmlSplitSet,$isWeighted,$hasKo);
	}
	
	if($gzipFlag) {
		`gzip $file`;
	}
}

########################################################
## Parses JCVI Prokaryotic Annotation Files.
########################################################

sub createIndexFromJpmapFile() {
	my $projectDir  = shift;
	my $dataset	 	= basename($projectDir);
		
	my $annotationFile 	= "$projectDir/annotation_rules.combined.out"	;
	my $blastFile 		= "$projectDir/ncbi_blastp_btab.combined.out"	;
	my $hmmFile 		= "$projectDir/ldhmmpfam_full.htab.combined.out"	;
	
	## count documents and XML files
	my $xmlSplitSet  = 2;
	my $numDocuments = 1;	
	my $isGzipped	 = 1;
	my $hasKo = 0;
	my $isWeighted   = 0;
	my $goTree 		 = undef;
			
	## create index 	
	&openIndex($dataset);
			
	## fetch UniRef peptide ID to subject ID mapping
	my $peptideId2SubjectIdHashRef = &readPeptideIdToSubjectIdMapping($annotationFile);	
				
	## fetch blast results
	my $blastResultHashRef 	= &readJpmapBlastEntries($blastFile,$peptideId2SubjectIdHashRef) ;
	my %blastResultHash 	= %$blastResultHashRef;
	
	## fetch hmm results
	my $hmmResultHashRef 	= &readJpmapHmmEntries($hmmFile) ;
	my %hmmResultHash 		= %$hmmResultHashRef;
			
	if(-e "$annotationFile.gz") {	
		$isGzipped = 1;
		`gunzip $annotationFile.gz`;		
	}
	
	open FILE, "$annotationFile" or die "Could not open file $annotationFile.";
									
	while(<FILE>) {					
		my $defline = $_;
			
		my ($peptideId,undef,$comName,$comNameSrc,undef,$gene_symbol,
		$gene_symbol_evidence,undef,$goId,$goSrc,undef,$ecId,$ecSrc,
		undef,undef,undef) = split (/\t/, $defline);
				
		## adjust case of GO ID
		$goId = uc($goId);
			
		## get blast entry
		my $blastEntry = $blastResultHash{$peptideId};
			
		## get array ref with hmms
		my $hmmId = undef;		
			
		my $hmmRef = $hmmResultHash{$peptideId};
			
		if($hmmRef ) {
			my @hmms  = @$hmmRef;
				
			if(@hmms){
				$hmmId = join('||',@hmms);
			}
		}
				
       	if($goId) {     
        	$goTree = join('||',&getGoAncestors($goId));
       	}				
       					
		## set index field
	    &addDocument($peptideId,$dataset,$comName,$comNameSrc,$goId,$goSrc,$goTree,$ecId,$ecSrc,
	    $hmmId,$blastEntry->{species},$blastEntry->{evalue},$blastEntry->{evalue_exp},$blastEntry->{pid},$blastEntry->{coverage},$blastEntry->{blast_tree},undef,undef,undef,undef,undef);      
	        
		if(($numDocuments % $args{max_num_docs}) == 0) {
			&nextIndex($dataset,$xmlSplitSet);
			$xmlSplitSet++;
		} 	
		$numDocuments++;
	}
				
	## close store
	close(FILE);
	&closeIndex();
	
	if($isGzipped) {
		`gzip $annotationFile`;
	}
	
	## push index if xml only option has not been selected
	unless($args{xml_only}) {
		&pushIndex($dataset,$xmlSplitSet,$isWeighted,$hasKo);
	}
}

########################################################
## Parses tab delimited input file.
########################################################

sub createIndexFromTabFile() {
	my $file = shift;
	my $hasKo = 0;
	my $isWeighted = 0;
	my $gzipFlag =0;
	
	if($file =~ /\.gz$/) {
		`gunzip $file`;
		$gzipFlag =1;
		$file =~ s/\.gz$//;
	}
		
	open FILE, "$file" or die "Could not open file $file.";
		
		#parse dataset name		
		my $datsetName = basename($file);
		$datsetName =~ s/\.tab//;
		
		#create index file
		&openIndex($datsetName);
		
		## count documents and XML files
		my $xmlSplitSet  = 2;
		my $numDocuments = 1;	
		
		while(<FILE>) {
			chomp $_;
			
			my ($blastTree,$blastSpecies,$blastEvalueExponent,$goTree,$koTree);
			
			#read fields
	        my (
	            $peptideId, 
	            $libraryId, 
	            $comName, 
	            $comNameSrc,
	            $goId, 
	            $goSrc, 
	            $ecId,
	            $ecSrc,
	            $hmmId,
	            $blastTaxon,
	            $blastEvalue,
	            $blastPid,
	            $blastCov,
				$filter,
				$koId,
				$koSrc,
				$weight
	        ) = split("\t",$_);	 

	        ## handle KO
	        if(&clean($koId)) {
	        	$hasKo = 1;
	        }
	        
	        ## set weight
	        if(&clean($weight)) {
	        	$isWeighted = 1;
	        }
	        
	        ## set GO tree
	       	$goId = uc(&clean($goId));  
	       	    
	       	if($goId) {     
	        	$goTree = join('||',&getGoAncestors($goId));
	       	}
	       	        
	        if($koId) {
	        	 if($koId==1) {
	        	 	die($_);
	        	 }   
	        	$koTree = join('||',&getKoAncestors($koId));  
	        }    
	               
	        ## set Blast tree and Blast species based on provided taxon
	        if($blastTaxon) {
	        	
	        	## find least common ancestor if multiple ids have been provided
	        	if($blastTaxon =~ m/\|\|/) {
	        		my @taxonIds = split(/\|\|/, $blastTaxon);
	        		
	        		#override blastTaxon with least common ancestor
	        		$blastTaxon = &mergeMultiTaxonomicLabels(@taxonIds);      		
	        	}
	        		
	        	## get all parent taxa	
		        my @taxonAncestors = &getTaxonAncestors($blastTaxon);
		        $blastTree 		= join('||',@taxonAncestors);
		        
		        ## set the species if the 
		 		$blastSpecies 	= &getSpecies(\@taxonAncestors);	
	        }	
	
		    if($blastPid =~ m/^[1-9].*/) {
		    	$blastPid = $blastPid/ 100;
		    }
	           
	        #set Blast Evalue exponent 
			if($blastEvalue == 0) {
					#set evalue to high default value
				$blastEvalueExponent = 9999;
			}	
			else {
				my @tmp = split('e-',lc($blastEvalue));	
				use POSIX qw( floor );
				$blastEvalueExponent  = floor($tmp[1]);
			}
			       
	        &addDocument($peptideId,$libraryId,$comName,$comNameSrc,$goId,$goSrc,$goTree,$ecId,$ecSrc,
	        $hmmId,$blastSpecies,$blastEvalue,$blastEvalueExponent,$blastPid,$blastCov,$blastTree,$filter,$weight,$koId,$koSrc,$koTree);         

			if(($numDocuments % $args{max_num_docs}) == 0) {
					&nextIndex($datsetName,$xmlSplitSet);
					$xmlSplitSet++;
			} 	
			
			$numDocuments++;	

		}
		
	&closeIndex();
	
	## push index if xml only option has not been selected
	unless($args{xml_only}) {
		&pushIndex($datsetName,$xmlSplitSet,$isWeighted,$hasKo);
	}
	
	if($gzipFlag) {
		`gzip $file`;
	}
}

########################################################
## Clean store
########################################################

$metarepDbConnection->disconnect;
$sqliteDbConnection->disconnect();

########################################################
## Creates new index file
########################################################

sub openIndex {
	my $dataset = shift;

	my $outFile	= "$args{tmp_dir}/$dataset"."_1.xml";
	#open output file
	print "Creating index file $outFile ...\n";
	
	open(INDEX, ">$outFile") || die("Could not create file $outFile.");

	print INDEX "<add>\n";
}

########################################################
## Closed current index file and opens a new index file.
########################################################

sub nextIndex {
	my ($dataset,$xmlSplitSet) = @_;
	
	#close exiting index
	&closeIndex();
	
	#define next index file
	my $outFile	= "$args{tmp_dir}/$dataset"."_".$xmlSplitSet.".xml";
	
	#save filehandle in variable	
	open(INDEX, ">$outFile") || die("Could not create file $outFile.");	

	print INDEX "<add>\n";
}

########################################################
## Closed index file.
########################################################

sub closeIndex {
	print INDEX "</add>";
	close INDEX;
}

########################################################
## pushes new index file to Solr server; adds MySQL dataset
########################################################

sub pushIndex() {
	my ($dataset,$xmlSplitSet,$isWeighted,$hasKo) = @_;

	print "Deleting dataset from METAREP MySQL database...\n";
	&deleteMetarepDataset($dataset);
	
	print "Deleting Solr Core (if exists)...\n";
	&deleteSolrCore($dataset);
	
	print "Creating Solr core...\n";
	&createSolrCore($dataset);
	
	for(my $set = 1; $set <= $xmlSplitSet;$set++){
		my $xmlFile = "$dataset"."_".$set.".xml";
		
		print "Loading Solr index...\n";
		&loadSolrIndex($dataset,$xmlFile);
	
		print "Optimizing Solr index...\n";
		&optimizeIndex($dataset);
	}
	
	print "Adding dataset to METAREP MySQL database....\n";
	&createMetarepDataset($dataset,$isWeighted,$hasKo);	
}

########################################################
## adds lucene document to lucene index
########################################################

sub addDocument() {
	my ($peptideId,$libraryId,$comName,$comNameSrc,$goId,$goSrc,$goTree,$ecId,$ecSrc,
        $hmmId,$blastSpecies,$blastEvalue,$blastEvalueExponent,$blastPid,$blastCov,$blastTree,$filter,$weight,$koId,$koSrc,$koTree) = @_;	 
	
	print INDEX "<doc>\n";		
		
	#write core fields
	&printSingleValue("peptide_id",$peptideId);
	&printSingleValue("library_id",$libraryId);
	&printMultiValue("com_name",$comName);
	&printMultiValue("com_name_src",$comNameSrc);
	&printMultiValue("go_id",$goId);
	&printMultiValue("go_src",$goSrc);
	&printMultiValue("go_tree",$goTree);
	&printMultiValue("ec_id",$ecId);
	&printMultiValue("ec_src",$ecSrc);		
	&printMultiValue("hmm_id",$hmmId);		
	&printMultiValue("filter",$filter);
	&printMultiValue("ko_id",$koId);
	&printMultiValue("ko_src",$koSrc);
	&printMultiValue("kegg_tree",$koTree);
	&printSingleValue("weight",$weight);
	
	#write best hit Blast fields
	&printSingleValue('blast_species',$blastSpecies);
	&printSingleValue('blast_evalue',$blastEvalue);
	&printSingleValue('blast_evalue_exp',$blastEvalueExponent);
	&printSingleValue('blast_pid',$blastPid);
	&printSingleValue('blast_cov',$blastCov);	
	&printMultiValue("blast_tree",$blastTree);			
	
	print INDEX "</doc>\n";		
}

########################################################
## writes a single values field to the lucene index.
########################################################

sub printSingleValue {
	my ($field,$value) = @_;
	
	$value = &clean($value);
	
	if($value ne '') {
		
		print INDEX "<field name=\"$field\">$value</field>\n";	
	}
}

########################################################
## writes multi-valued fields.
########################################################

sub printMultiValue() {
	my ($field,$value) = @_;
	
	$value = &clean($value);
		
	my @values = split(/\|\|/, $value);
	
	if(@values>0) {
		foreach(@values){
			$value = &clean($_);
			
			if($value) {								
				print INDEX "<field name=\"$field\">". $value."</field>\n";	
			}
		}
	}
}

########################################################
## takes a species taxon id and returns an array that contains its lineage
########################################################

sub getTaxonAncestors() {
	my $taxonId = shift;
	my @ancestors = ();

	if(exists $taxonAncestorHash->{$taxonId}) { 
		@ancestors = @{$taxonAncestorHash->{$taxonId}};
	}
	else {
		## add taxon id to the front of the array
		unshift(@ancestors,$taxonId);
			
		## loop through tree until root has been reached
		while(1) {
			my $parentTaxonId = &getParentTaxonId($taxonId);
			
			## add parent to the front of the array if is non-empty
			if($parentTaxonId ne '') {			
				unshift(@ancestors,$parentTaxonId);			
			}	
			
			## stop if root has been reached or empty taxon ID has been returned	
			if($parentTaxonId == 1 || $parentTaxonId eq ''){
				last;
			}	
					
			$taxonId = $parentTaxonId;
		} 
		
		$taxonAncestorHash->{$taxonId} = \@ancestors;		
	}
	return @ancestors;
}

########################################################
## Returns array of KO ancestors (integer part of the ID).
########################################################
sub getKoAncestors(){
	
	my $koTerms = shift;
	
	my @ancestors=();
	
	if(exists $koAncestorHash->{$koTerms}) { 
		@ancestors = @{$koAncestorHash->{$koTerms}};
	}
	else {
			
		my $id = undef;	
		
		my @koTerms = split (/\|\|/, $koTerms);
		
		@koTerms = &cleanArray(@koTerms);
		
		my $koTermSelection = join ',',map{qq/'$_'/} @koTerms;
		
		$koTermSelection = "($koTermSelection)";	

		my $query = "select distinct parent_pathway_id from pathway_ko where pathway_id in (select parent_pathway_id from pathway_ko where pathway_id in(select parent_pathway_id from pathway_ko where pathway_id in (select parent_pathway_id from pathway_ko where ko_id in $koTermSelection))) union
					select distinct parent_pathway_id from pathway_ko where pathway_id in(select parent_pathway_id from pathway_ko where pathway_id in (select parent_pathway_id from pathway_ko where ko_id in $koTermSelection)) union
					select distinct parent_pathway_id from pathway_ko where pathway_id in (select parent_pathway_id from pathway_ko where ko_id in $koTermSelection) union 
					select distinct parent_pathway_id from pathway_ko where ko_id in $koTermSelection";
							
		my $sth = $sqliteDbConnection->prepare($query);	
		$sth->bind_col(1, \$id);
		$sth->execute();	
		
		while ($sth->fetch) {		
			push(@ancestors,$id);
		}
		$koAncestorHash->{$koTerms} = \@ancestors;
	}
		
	return @ancestors;
}

########################################################
#Returns array of GO ancestors (integer part of the ID).
########################################################

sub getGoAncestors(){

	my $goTerms = shift;
	my @ancestors=();
	
	if(exists $goAncestorHash->{$goTerms}) { 
		@ancestors = @{$goAncestorHash->{$goTerms}};
	}
	else {
		
		my @goTerms = split (/\|\|/, $goTerms);

		@goTerms = &cleanArray(@goTerms);
		
		my $goTermSelection = join 'or term.acc=',map{qq/'$_'/} @goTerms;
		
		$goTermSelection = "(go_term.acc=$goTermSelection)";
		
		my $ancestor;

		## SQLITE query
		my $query = "select DISTINCT substr(ancestor.acc,4,length(ancestor.acc)) 
		FROM go_term INNER JOIN go_graph_path ON (go_term.go_term_id=go_graph_path.go_term2_id) INNER JOIN go_term
		 AS ancestor ON (ancestor.go_term_id=go_graph_path.go_term1_id) WHERE $goTermSelection and
		  ancestor.acc!='all' order by distance desc;";

		my $sth = $sqliteDbConnection->prepare($query);	
		$sth->execute();
	
		$sth->bind_col(1, \$ancestor);
		
		while ($sth->fetch) {
				
			##check if numeric
			if ($ancestor =~ /^[0-9]+$/ ) {
				
				#remove trailing zeros
				$ancestor =~ s/^0*//;
			
				#print $ancestor ."\n";	
				push(@ancestors,$ancestor);
			}
		}	
		$goAncestorHash->{$goTerms} = \@ancestors;
	}	
	
	return @ancestors;
}

########################################################
## Returns parent taxon id (NCBI taxonomy).
########################################################

sub getParentTaxonId() {
	my $speciesId = shift;
	my $parentTaxonId;
 	
	my $query ="select parent_ncbi_taxon_id from ncbi_taxon where ncbi_taxon_id=?" ;

	#execute query
	my $sth = $sqliteDbConnection->prepare($query);
	$sth->execute($speciesId);

	$sth->bind_col(1, \$parentTaxonId);
	$sth->fetch;
	
	return $parentTaxonId;
}

########################################################
## returns species level of taxon; returns 'unresolved' string if taxon is higher than species.
########################################################

sub getSpecies() {
	my $ancestors = shift;
	my $species = 'unresolved';
	my $query = '';
		
	my @ancestors = reverse(@$ancestors);
	
	if(@ancestors == 1) {
		$query ="SELECT name FROM ncbi_taxon WHERE rank = 'species' AND ncbi_taxon_id = $ancestors[0]" ;
	}
	elsif(@ancestors > 1) {
	 	$query ="SELECT name FROM ncbi_taxon WHERE rank = 'species' AND ncbi_taxon_id IN(".join(',',@ancestors).")" ;
	}
	else{
		return $species;	
	}
	 	
 	my $sth = $sqliteDbConnection->prepare($query);
 	$sth->execute();	
	$sth->bind_col(1, \$species);
	$sth->fetch;

	return $species;
}

########################################################
## Takes array of NCBI taxon ids and returns least common ancestor.
########################################################

sub mergeMultiTaxonomicLabels() {
	my @taxonIds = shift;
	
	my $leastCommonAncestor = $taxonIds[0];
	
	for(my $i = 0; $i < ((scalar @taxonIds) -1) ; $i++) {		
		my $tmpLeastCommonAncestor = &getLeastCommonAncestor($leastCommonAncestor,$taxonIds[$i+1]);
		
		if($tmpLeastCommonAncestor ne '') {
			$leastCommonAncestor = $tmpLeastCommonAncestor;
		}
	}
	return $leastCommonAncestor;	
}

########################################################
## Get least common ancestor.
########################################################

sub getLeastCommonAncestor() {
	my ($taxonIdA,$taxonIdB) = @_;
	
	## if both are at the same taxon level
	if($taxonIdA == $taxonIdB) {
		return $taxonIdA;
	}
	else {
		## get lineage for both taxa sorted by lowest (species) to highest taxa (root)
		my @lineageA = reverse(&getTaxonAncestors($taxonIdA));		
		my @lineageB = reverse(&getTaxonAncestors($taxonIdB));

		foreach my $taxonA(@lineageA) {
			
			foreach my $taxonB(@lineageB) {
				if($taxonA == $taxonB) {
					return $taxonA;
				}
			}
		}
	}
}

########################################################
## Deletes dataset from METAREP MySQL database.
########################################################

sub deleteMetarepDataset() {
	my $name = shift;
	
	my $query ="delete from libraries where name = ?";

	## reconnect to avoid mysql time-out
	$metarepDbConnection = DBI->connect_cached("DBI:mysql:".$args{mysql_db}.";host=".$args{mysql_host}."",$args{mysql_username},$args{mysql_password}, { 'RaiseError' => 0 });
	
	## prepare query
	my $sth =$metarepDbConnection->prepare($query);
	
	$sth->execute($name) or die "Couldn't execute: $DBI::errstr";
}

########################################################
## Deletes Solr core (if exists)
########################################################

sub deleteSolrCore() {
	my $core = shift;

	## delete all documents of existing index
	print "Deleting index: java -Durl=$args{solr_url}/solr/$core/update -Xms$initialJavaHeapSize -Xmx$args{solr_max_mem} -jar $args{solr_home_dir}/example/exampledocs/post.jar $args{solr_home_dir}/delete.xml...\n";
	`java -Durl=$args{solr_url}/solr/$core/update -Xms$initialJavaHeapSize -Xmx$args{solr_max_mem} -jar $args{solr_home_dir}/example/exampledocs/post.jar $args{solr_home_dir}/delete.xml `;
		
	## unload core from core registry
	print "Unloading index: curl $args{solr_url}/solr/admin/cores?action=UNLOAD&core=$core \n";
	`curl \"$args{solr_url}/solr/admin/cores?action=UNLOAD&core=$core\"`;
}

########################################################
## Creates Solr core
########################################################

sub createSolrCore() {
	my $core = shift;
	
	## create core
	print "Creating new core: curl $args{solr_url}/solr/admin/cores?action=CREATE&name=$core&instanceDir=$args{solr_home_dir}/example/solr&dataDir=$args{solr_instance_dir}/$core...\n";
	`curl \"$args{solr_url}/solr/admin/cores?action=CREATE&name=$core&instanceDir=$args{solr_instance_dir}&dataDir=$args{solr_data_dir}/$core\"`;	
}

########################################################
## Creates Solr index.
########################################################

sub loadSolrIndex() {
	my ($core,$xmlFile) = @_;
	
	my $file = "$args{tmp_dir}/$xmlFile";
	
	#load index
	print "Loading Dataset Index: java -Durl=$args{solr_url}/solr/$core/update -Xms$initialJavaHeapSize -Xmx$args{solr_max_mem} -jar $args{solr_home_dir}/example/exampledocs/post.jar $file ...\n";
	`java -Durl=$args{solr_url}/solr/$core/update -Xms$initialJavaHeapSize -Xmx$args{solr_max_mem} -jar $args{solr_home_dir}/example/exampledocs/post.jar $file`;
}

########################################################
## Optimizes Solr index.
########################################################

sub optimizeIndex() {
	my $core = shift;
	
	#optimize index
	print "Optimize Dataset Index: java -Durl=$args{solr_url}/solr/$core/update -Xms$initialJavaHeapSize -Xmx$args{solr_max_mem} -jar $args{solr_home_dir}/example/exampledocs/post.jar $args{solr_home_dir}/optimize.xml \n";
	`java -Durl=$args{solr_url}/solr/$core/update -Xms$initialJavaHeapSize -Xmx$args{solr_max_mem}  -jar $args{solr_home_dir}/example/exampledocs/post.jar $args{solr_home_dir}/optimize.xml `;

}

########################################################
## Creates dataset in METAREP MySQL database
########################################################

sub createMetarepDataset() {
	my ($dataset,$isWeighted,$hasKo) = @_;
	my $srsId = $dataset;
	
	$srsId =~ s/-pga$//;
	
	my $projectId = $args{project_id};
	
	my $pipeline = undef;
	
	if($args{format} eq 'humann') {
		$pipeline = 'HUMANN';
	}
	elsif($args{format} eq 'jpmap') {
		$pipeline = 'JCVI_META_PROK';
	}
	elsif($args{format} eq 'tab') {
		$pipeline = 'DEFAULT';
	}
	
	my $query ="insert ignore into libraries (name,project_id,created,updated,pipeline,is_weighted,has_ko) VALUES (?,?,curdate(),curdate(),'$pipeline',$isWeighted,$hasKo)";
	print $query."\n";

	## reconnect to avoid time-out
	$metarepDbConnection = DBI->connect_cached("DBI:mysql:".$args{mysql_db}.";host=".$args{mysql_host}."",$args{mysql_username},$args{mysql_password}, { 'RaiseError' => 0 });
		
	## prepare query
	my $sth =$metarepDbConnection->prepare($query);
	
	## execute query
	$sth->execute($dataset,$projectId) or die "Couldn't execute: $DBI::errstr";
}

########################################################
## Trims and escapes array values.
########################################################

sub cleanArray() {
	my @array = shift;
	my @cleanArray=();
	foreach(@array) {
		push(@cleanArray,&clean($_));
	}
	return @cleanArray;
}

########################################################
## Trims and escapes special xml characters.
########################################################

sub clean {
	my $tmp = shift;
	
	## escape special xml characters
	$tmp =~ s/&/&amp;/g;
	$tmp =~ s/</&lt;/g;
	$tmp =~ s/>/&gt;/g;
	$tmp =~ s/\"/&quot;/g;
	$tmp =~ s/'/&apos;/g;
	
	## remove white spaces
	$tmp =~ s/^\s+//g;
	$tmp =~ s/\s+$//g;
		
	## remove other invalid characters
	$tmp =~ s/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]//;	
		
	return $tmp;
}
########################################################
## Selects NCBI taxon ID for KEGG taxon.
########################################################
sub getNcbiTaxonId() {
	my $keggTaxonId = shift;
	my @ecIdAccessions= ();
	
	my $sth = $sqliteDbConnection->prepare("Select ncbi_ncbi_taxon_id from taxon where kegg_ncbi_taxon_id='$keggTaxonId'");
	$sth->execute();
	
	my $ecId = undef;
	
	my $ncbiTaxonId;
	$sth->bind_col(1, \$ncbiTaxonId);
	$sth->fetch;
	
	return $ncbiTaxonId;
}

########################################################
## Get KEGG enzyme ID.
########################################################

sub getEcId() {
	my $keggGeneId = shift;
	my @ecIdAccessions= ();
	
	my $sth = $sqliteDbConnection->prepare("Select ec_id from gene2ec where gene_id='$keggGeneId'");
	$sth->execute();
	
	my $ecId = undef;
	
	$sth->bind_col(1, \$ecId);
	
	while ($sth->fetch) {
		$ecId =~ s/ec://;
		push(@ecIdAccessions,$ecId);
	}
	return join('||',@ecIdAccessions);
}

########################################################
## Get KEGG common name.
########################################################

sub getCommonName() {
	my $keggGeneId = shift;
	my $commonNameString = '';
	
	my $sth = $sqliteDbConnection->prepare("Select defline from gene2desc where gene_id='$keggGeneId'");
	$sth->execute();
	
	$sth->bind_col(1, \$commonNameString);	
	$sth->fetch;
	
	$commonNameString =~ s/; /||/g;
	
	return $commonNameString;
}

########################################################
## Get KEGG ortholog.
########################################################

sub getKoId() {
	my $keggGeneId = shift;
	my @koIdAccessions= ();
	
	my $sth = $sqliteDbConnection->prepare("Select ko_id from gene2ko where gene_id='$keggGeneId'");
	$sth->execute();
	
	my $ecId = undef;
	
	$sth->bind_col(1, \$ecId);
	
	while ($sth->fetch) {
		$ecId =~ s/ec://;
		push(@koIdAccessions,$ecId);
	}
	return join('||',@koIdAccessions);
}

########################################################
## Get KEGG Ortholog KO IDs.
########################################################

sub getGoIdsFromKO() {
	my $koIdString = shift;
	my $goIdString = '';
	my $goSrcString = '';
	
	my %results = ();
	my @goIds;
	my @goSrc;
	
	my @koIds = split('\|\|',$koIdString);
	
	foreach my $koId (@koIds) {	
		
		my $sth = $sqliteDbConnection->prepare("Select go_id from ko2go where ko_id ='$koId'");
		$sth->execute();		
		$sth->bind_col(1, \$goIdString);	
		while ($sth->fetch) {		
			push(@goIds,$goIdString);
			push(@goSrc,$koId);
		}							
	}
		
	$results{'go_id_string'} = join('||',@goIds);
	$results{'go_src_string'}=  join('||',@goSrc);

	return \%results;
}

########################################################
## Get KEGG enzyme ID.
########################################################

sub readPeptideIdToSubjectIdMapping() {
	my $annotationFile = shift;

	my %peptideId2SubjectIdHash=();
	my $zip =0;
		
	if(!open FILE, $annotationFile) {
		
		if(-e "$annotationFile.gz") {
			$zip =1;
			`gunzip $annotationFile.gz`;
			if(!open FILE, $annotationFile) {
				die("Could not find $annotationFile");
			}
		}
	}
	
	print "Reading Annotation File $annotationFile...\n";
	
	while(<FILE>) {	
		chomp;			
		my $defline = $_;
		
		#parse data
		my @fields = split (/\t/, $defline);
		my $peptideId = $fields[0];
		my $subjectId   = $fields[18];
		$peptideId2SubjectIdHash{$peptideId} = $subjectId;	
	}
	close FILE;
	return \%peptideId2SubjectIdHash;
}

########################################################
# Reads btab formatted files produced by
# the JCVI Prokarytoic Metagneomics Annotation pipeline
########################################################

sub readJpmapBlastEntries {
	my ($blastFile,$peptideId2SubjectIdHashRef) = @_;

	my $zip =0;
	
	if(!open FILE, $blastFile) {
		
		if(-e "$blastFile.gz") {
			$zip =1;
			`gunzip $blastFile.gz`;
			if(!open FILE, $blastFile) {
				die("Could not find $blastFile");
			}
		}
	}
	
	my %blastHash;
	
	## read file
	while (<FILE>) {
		my $defline = $_;
		
		## htab fields		
        my (
            $peptide_id, 
            $analysis_date, 
            $query_length, 
            $search_method,
            $database_name, 
            $subject_id, 
            $query_start,
            $query_end,
            $subject_start,
            $subject_end,
            $percent_identity,
            $percent_similarity,
            $score,
            $file_offset1,
            $file_offset2,
            $description,
            $frame,
            $query_strand,
            $subject_length,
            $evalue,
            $pvalue,
           ) = split("\t",  $defline);
           
           my $coverage = undef;

           my $qpctlen = int( 1000. * ($query_end - $query_start +1) / $query_length ) / 10.;
          
           my $spctlen = 0;#int( 1000. * ($subject_end - $subject_start +1) /  $subject_length) / 10.;

			
           if ( $qpctlen >= $spctlen ) {
              $coverage = $qpctlen;
           } else {
              $coverage = $spctlen;
           } 

		## parse evalue exponent 		
		my $evalueExponent = undef;
		
		## set to high exponent for 0 evalue
		if($evalue == 0) {			
			$evalueExponent=9999;
		}
		else {
			my @tmp = split('e-',lc($evalue));	
			use POSIX qw( floor );
			$evalueExponent  = floor($tmp[1]);
		}
						
		## get annotation subject ID
		my $annotationSubjectId = $peptideId2SubjectIdHashRef->{$peptide_id};
			
		## if annotation subject ID and blast subject ID matches; add result
		if($annotationSubjectId eq $subject_id) {
			my $resultHash = undef;
				
			$resultHash = &parseUniRefDefline($description);  	
				
			$resultHash->{'evalue'} 	= $evalue;				
			$resultHash->{'evalue_exp'} = &clean($evalueExponent);
				
			## adjust percent identity; range [0-1]	
			$resultHash->{'pid'} 		= $percent_identity/100;
			$resultHash->{'coverage'} 	= $coverage/100;
				
			$blastHash{$peptide_id} = $resultHash;		
		}
	}
		
	close FILE;

	if($zip) {
		`gzip $blastFile`;
	}		
	
	return \%blastHash;
}

########################################################
# Reads htab formatted files produced by
# the JCVI Prokarytoic Metagneomics Annotation pipeline
########################################################

sub readJpmapHmmEntries {
	my $hmmFile = shift;
	
	my $zip =0;
	
	if(-e "$hmmFile.gz") {	
		$zip = 1;
		`gunzip $hmmFile.gz`;		
	}
	
	open FILE, "$hmmFile" or die "Could not open file $hmmFile.";
	
	my %hmmHash;
	
	## read file
	while (<FILE>) {
		chomp;
		my $defline = $_;

		my (@fields) =split(/\t/, $defline);
		
		my $hmm 	= &clean($fields[0]);
		
		unless($hmm eq 'No hits above thresholds') {
			my $domainScore 		= &clean($fields[11]);
			my $trustedCutoff 	= &clean($fields[17]);
			
			## only add hmm hits that have a hit above the trusted cutoff
			if($domainScore >= $trustedCutoff) {
				my $peptide_id 		= &clean($fields[5]);
				## if no hit has been added, assign hit				
				if (! exists $hmmHash{$peptide_id}) {				
					my @hmms = ();
					push(@hmms,$hmm);
					
					$hmmHash{$peptide_id} = \@hmms;
				}	
				else {
					my $hmmArrayRef = $hmmHash{$peptide_id};
					my @hmms = @$hmmArrayRef;
					
					## add hmm if hmm not yet part of array
					if(! grep (/$hmm/i, @hmms)) {
						push(@hmms,$hmm);
					}
					
					$hmmHash{$peptide_id} = \@hmms;
				}								
			}
		}		
	}	
	
	close FILE;
	
	if($zip) {
		`gzip $hmmFile`;
	}
	

	return \%hmmHash;
}

########################################################
# Parses UniRef100 deflines.
########################################################

sub parseUniRefDefline() {
	my $defline = shift;
	
	## create hash to return results 
	my %resultHash 	= ();	

	## set default resolution 
    $resultHash{'species'} 	  = 'unresolved';
    $resultHash{'species_id'} = -1;   
	
	## parse UniRef header
	$defline =~  /^.*\sn=\d*\sTax=(.*)\sRepID=.*/; 
    
    my $taxonName = &clean($1);
      			      			      	
    ## get NBI taxon ID for the taxon name
    my $taxonId = &getTaxonIdByName($taxonName);	
    
    if($taxonId) {    	  	
    	$resultHash{'species_id'} = $taxonId;    	
    	 
		my @taxonAncestors = &getTaxonAncestors($taxonId);	      	
			      	
	    ## assign species if the taxon could be resolved to the species level
	    $resultHash{'species'} = &getSpecies(\@taxonAncestors);				
		$resultHash{'blast_tree'} = join('||',@taxonAncestors);
	
	}
	return \%resultHash;	   
}

########################################################
# Returns NCBI taxon ID by name.
########################################################

sub getTaxonIdByName() {
	my $name = shift;
	
	my $taxonId = '';
	
	my $query ="select ncbi_taxon_id from ncbi_taxon where name=? ";
		
	## execute query
	my $sth = $sqliteDbConnection->prepare($query);
	$sth->execute($name);

	$sth->bind_col(1, \$taxonId);
	$sth->fetch;
	
	return $taxonId;
}