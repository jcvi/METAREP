#!/usr/local/bin/perl

########################################################
# Contains methods that support Solr index generation
########################################################

package SolrIndexGenerator;
use strict;
#to get filename from full path
use File::Basename;
#use warnings;
use FileHandle;
use DBI();

#constructor
sub new {
	my ($class,$projectId,$isViral,$apisDatabase,$populationName,$cleanNames,$xmlOnly) = @_;
	my $self = bless({}, $class);	
	
	$self->{project_id} = $projectId;

	#configure solr parameters
	$self->{solr_master_host}	="http://172.20.13.24:8989";
	$self->{solr_slave_host}	="http://172.20.13.25:8989";
	$self->{solr_data_dir}		="/solr-index";
	$self->{solr_instance_dir}	="/opt/software/apache-solr/solr";
	$self->{solr_local_dir}		="/usr/local/annotation/METAGENOMIC/METAREP";
	$self->{solr_post_jar}		="/usr/local/annotation/METAGENOMIC/METAREP/solr/example/exampledocs/post.jar";
	$self->{solr_min_memory}	='156m';
	$self->{solr_max_memory}	='2640m';
	#$self->{tmp_dir}			="/usr/local/annotation/METAGENOMIC/METAREP/metarep$projectId$populationName";
	$self->{tmp_dir}			="/usr/local/scratch/metarep$projectId";
	$self->{is_viral}			= $isViral;
	$self->{apis_db}			= $apisDatabase;		
			
	#configure Protein Namming Utility	if clean names option has been provided
	if(defined($cleanNames)) {
		my %uniqueNames = ();
		my $renamer = Renamer->new();
	 	$self->{full_matches} = $renamer->getFullMatches(1);
	 	$self->{unique_names} =	 \%uniqueNames;
	}
	
	if(defined($xmlOnly)){
		$self->{xml_only} = $xmlOnly;
	}
			
	#open db connections
	$self->{METAREP} = DBI->connect("DBI:mysql:ifx_metagenomics_reports;host=mysql51-dmz-pro.jcvi.org",
			"ifx_mg_reports", "mgano", { 'RaiseError' => 1 });
			
	$self->{GO} = DBI->connect("DBI:mysql:gene_ontology;host=mysql51-dmz-pro.jcvi.org",
			"access", "access", { 'RaiseError' => 1 });		
		
	if(defined($apisDatabase)) {		
		$self->{APIS} = DBI->connect("DBI:mysql:$apisDatabase;host=mysql51-lan-pro.jcvi.org",
				"access", "access", { 'RaiseError' => 1 });			
	}
	else {
		$self->{APIS}=undef;
	}
	
	#create tmp directory if not exists
	if(!(-d $self->{tmp_dir})) {
		print "Creating tmp dir: $self->{tmp_dir}";
		`mkdir $self->{tmp_dir}`;
	}
	
	unless(defined($self->{xml_only})) {
		if(defined($populationName)) {
			$self->{population_name} = $populationName;
			$self->createPopulationIndex($populationName);
		}
	}
	
	return $self;
}

sub createViralIndex {
	my ($self,$library,$files) = @_;
	
	my %files = %$files;
	
	my $annotationFile 	= $files{annotation};
	my $evidenceFile 	= $files{evidence};
	my $goFile 			= $files{com2go};
	my $filterFile 		= $files{filter};
	my $clusterFile 	= $files{cluster};
	my $virulenceFile 	= $files{virulence};
		
	$self->openIndex($library);

	#open annotation file
	print "Reading Annotation File...\n";
	
	my $zip =0;
	
	my $fh = FileHandle->new;
	
	if(!$fh->open($annotationFile,"r")) {
		
		if(-e "$annotationFile.gz") {
			$zip =1;
			`gunzip $annotationFile.gz`;
			
			if(!$fh->open($annotationFile,"r")) {
				die("Could not find $annotationFile");
			}
		}
	}
	
	print "Reading Viral Evidence File $evidenceFile...\n";
	
	#get information stored in the evidence file
	my $evidenceArrayRef = $self->readViralEvidenceFile($evidenceFile) ;
	my @evidenceArray 	 = @$evidenceArrayRef;
	
	#get BLAST results
	my $blastResultHashRef 	= $evidenceArray[0];
	my %blastResultHash 	= %$blastResultHashRef;
	
	#get ACLAME HMM results
	my $aclameHmmResultHashRef 	= $evidenceArray[1];
	my %aclameHmmResultHash 	= %$aclameHmmResultHashRef;

	#read go data if defined
	my %goHash=undef;
	
	if(defined($goFile)) {		
		print "Reading Go File...\n";
		my $goHashRef	= $self->readGoEntries($goFile) ;
		%goHash			= %$goHashRef;
	}	
	
	#read filter data if defined
	my %filterHash=undef;
	
	if(defined($filterFile)) {		
		print "Reading Filter File...\n";
		my $filterHashRef	= $self->readFilterEntries($filterFile) ;
		%filterHash		= %$filterHashRef;
	}	

	#read filter data if defined
	my %virulenceHash=undef;
	
	if(defined($virulenceFile)) {		
		print "Reading Virulence File...\n";
		my $virulenceHashRef	= $self->readVirluenceFile($virulenceFile) ;
		%virulenceHash			= %$virulenceHashRef;
	}	

	
	#read cluster data if defined
	my %clusterHash=undef;
	
	if(defined($clusterFile)) {		
		print "Reading Cluster File...\n";
		my $clusterHashRef	= $self->readClusterEntries($clusterFile) ;
		%clusterHash		= %$clusterHashRef;
	}
			
	my $isFirstLine=1;
	my $numDocuments=1;
	
	
	while(<$fh>) {					
		my $defline = $_;
		
		#remove data from data
		if($isFirstLine) {
			$isFirstLine=0;
			next;
		}
		
		#remove viral 'No Evidence'
		$defline =~ s/No Evidence//;
		
		#parse data
		my ($peptide_id,undef,$com_name,$com_name_src,
			undef,$go_id,$go_src,undef,$ec_id,
			$ec_src,undef,$env_lib,$env_evalue,
			$env_freq,undef,$hmm_id,undef,$signalp,
			$signalp_evidence,undef,$tmhmm,undef,
			$pepstats_mw,$pepstats_ie) = split (/\t/, $defline);
					
		#clean go ids (remove phi ontology)
		if($self->clean(uc($go_id))) {
			my @validGoIds=();
			my @validGoSrcs=();
			
			my @goIds = split (/\|\|/, $go_id);
			my @goSrcs = split (/\|\|/, $go_src);
			
			for(my $i=0;$i<	@goIds; $i++) {
				
				my $goId = $self->clean(uc($goIds[$i]));
				my $goSrc = $self->clean(uc($goSrcs[$i]));
				
				if($goId =~ m/^GO:/) {
					unshift(@validGoIds,$goId);
					unshift(@validGoSrcs,$goSrc);
				}
			}
			
			$go_id  = join('||',@validGoIds);
			$go_src = join('||',@validGoSrcs);
		}
		
		
		$hmm_id = $self->clean(uc($hmm_id));
		
		#add ACLAME HMMs to TIGR/PFAMS
		if($aclameHmmResultHash{$peptide_id}) {
			my $hmmRef = $aclameHmmResultHash{$peptide_id};
			my @hmms = @$hmmRef;
			
			if($hmm_id eq '') {		
				$hmm_id = join('||',@hmms);
			}
			else {
				$hmm_id = $hmm_id."||".join('||',@hmms);
			}
		}
			
		#add COM2GO assignments
		$go_id = $self->clean(uc($go_id));
			
		#add com2go go terms if empty
		if(defined($goFile)) {		
			if($go_id eq '') {
				
				$go_id = $goHash{$peptide_id};
				
				my $count = ($go_id =~ tr/://);
						
				#print $peptide_id.":".$go_id.":".$count."\n";		
						
				if($count == 1 && $go_id ne '') {
					$go_src = 'com2go';
				}
				elsif($count>1) {
					$go_src = 'com2go';
					
					for(my $i = 0; $i< ($count-1); $i++) {	
						$go_src .= '||com2go';
					}
					#$go_src =~ s/\|\|$//;
				}
			}		
		}
				
		#get blast entry
		my $blastEntry = $blastResultHash{$peptide_id};
			
		my $filter = undef;
		#get filter values
		if(defined(%filterHash)) {		
 			$filter = $filterHash{$peptide_id};		 
		}		
		
		#add clusters
		my $cluster = undef;
		if(defined(%clusterHash)) {				
 			$cluster = $clusterHash{$peptide_id};		 
		}		
		
		#add virulence factors to environmental entries
		my $virulenceFactor = undef;
		if(defined(%virulenceHash)) {				
			$filter = $virulenceHash{$peptide_id};		
		}				
		
		#set index field
		$self->addDocument($peptide_id, $library, $com_name, $com_name_src, $go_id, $go_src, $ec_id, $ec_src, $hmm_id, $env_lib,$blastEntry,$filter,$cluster);
		
		if($numDocuments % 500000 == 0) {
			my $index = $self->{index};
			print $index "</add>\n<add>\n";	
		} 
		
		$numDocuments++;
	}
	
	#close store
	$fh->close();	
	$self->closeIndex;	

	if($zip) {
		`gzip $annotationFile`;
	}
	
	unless($self->{xml_only} ) {
		print "Starting indexing process for $library ...\n";
		$self->pushIndex($library,$files);	
	}
}

sub createProkIndex {
	my ($self,$library,$files) = @_;
	
	#if clean names option has been selected, reset unqiue names array
	if($self->{full_matches}) {
		my %uniqueNames = ();
	 	$self->{unique_names} =	 \%uniqueNames;
	}
	
	my %files = %$files;
	my $annotationFile 	= $files{annotation};
	my $blastFile 		= $files{blast};
	my $hmmFile 		= $files{hmm};
	my $filterFile 		= $files{filter};
	my $clusterFile 	= $files{cluster};
	my $root	 		= $files{library_root};
	
	#if xml does not exist create it, if xml exists regenerate the index
	unless(-e "$self->{tmp_dir}/$library.xml") {
		$self->openIndex($library);

#		print "Reading Env file...\n";	
#		my $envHashRef 	= $self->readEnvLibEntries($root);
#		my %envHash 	= %$envHashRef;
	
		#read blast data
		print "Reading Blast file...\n";	
		my $blastResultHashRef 	= $self->readProkBlastEntries($blastFile) ;
		my %blastResultHash 	= %$blastResultHashRef;
		
		#read hmm data
		print "Reading HMM file...\n";				
		my $hmmResultHashRef 	= $self->readProkHmmEntries($hmmFile) ;
		my %hmmResultHash 		= %$hmmResultHashRef;
			
		#read filter data if defined
		my %filterHash=undef;
		if(defined($filterFile)) {		
			print "Reading Filter file...\n";
			my $filterHashRef	= $self->readFilterEntries($filterFile) ;
			%filterHash			= %$filterHashRef;
		}
		
		#read cluster data if defined
		my %clusterHash=undef;		
		if(defined($clusterFile)) {		
			print "Reading Cluster File...\n";
			my $clusterHashRef	= $self->readClusterEntries($clusterFile) ;
			%clusterHash		= %$clusterHashRef;
		}						
		
		my $zip =0;
		
		my $fh = FileHandle->new;
		
		if(!$fh->open($annotationFile,"r")) {
			if(-e "$annotationFile.gz") {
				$zip =1;
				`gunzip $annotationFile.gz`;
				
				if(!$fh->open($annotationFile,"r")) {
					die("Could not find $annotationFile");
				}
			}
		}
				
		my $numDocuments =1;	
				
		while(<$fh>) {					
			my $defline = $_;
			
			my ($peptide_id,undef,$com_name,$com_name_src,undef,$gene_symbol,
			$gene_symbol_evidence,undef,$go_id,$go_src,undef,$ec_id,$ec_src,
			undef,$tigr_role,$tigr_role_evidence) = split (/\t/, $defline);
				
			#adjust fields
			#$peptide_id =~ s/metagenomic.orf.//;
			$go_id 			= uc($go_id);
			#my $library_id	= uc($library);
			
			#get blast entry
			my $blastEntry = $blastResultHash{$peptide_id};
			
			#get array ref with hmms
			my $hmm_id = undef;		
			
			my $hmmRef = $hmmResultHash{$peptide_id};
			
			if($hmmRef ) {
				my @hmms  = @$hmmRef;
				
				if(@hmms){
					$hmm_id = join('||',@hmms);
				}
			}
			
			my $filter = undef;
			#get filter values
			if(defined(%filterHash)) {
	 			$filter = $filterHash{$peptide_id};
	 			
	 			#FIXME temporary fix for air load
	 			$filter =~ 's/ \|\| schmidt_reference//';
	 			$filter =~ 's/schmidt_reference//';
			}		
			
			#get env values
#			my $envLib = undef;
#			if(defined(%envHash)) {
#	 			$envLib = $envHash{$peptide_id};
#			}				
			
			my $cluster = undef;
			if(defined(%clusterHash)) {		
	 			$cluster = $clusterHash{$peptide_id};	
			}				
			
			#set index field
			$self->addDocument($peptide_id, $library, $com_name, $com_name_src, $go_id, $go_src, $ec_id, $ec_src, $hmm_id, undef,$blastEntry,$filter,$cluster);
			#}
			if($numDocuments % 500000 == 0) {
				my $index = $self->{index};
				print $index "</add>\n<add>\n";	
			} 	
			$numDocuments++;
		}
		
		#close store
		$fh->close();
		$self->closeIndex;
		
		if($zip) {
			`gzip $annotationFile`;
		}
			
		print "Finished Annotation file...\n";
	}
	else {
		print "XML already exits $self->{tmp_dir}/$library.xml exists. Skipping xml writing for $library\n";
	}
	unless($self->{xml_only} ) {
		print "Starting indexing process for $library ...\n";
		$self->pushIndex($library,$files);
	}
}

sub createTabIndex {
	my ($self,$library,$files) = @_;
		
	my %files = %$files;
	
	my $tabFile	= $files{tab};
				
		
	#if xml does not exist create it, if xml exists regenerate the index
	unless(-e "$self->{tmp_dir}/$library.xml") {
		
		$self->openIndex($library);
	
		my $zip =0;
		
		my $fh = FileHandle->new;	
		
		if(!$fh->open($tabFile,"r")) {
			if(-e "$tabFile.gz") {
				$zip =1;
				`gunzip $tabFile.gz`;
				
				if(!$fh->open($tabFile,"r")) {
					die("Could not find $tabFile");
				}
			}
		}
				
		while(<$fh>) {					
			
			my (@fields) = split (/\t/, $_);
			
			for(my $i = 5 ; $i< 7; $i++) {
				print "$i\t$fields[$i]\n";
			}
			
#			my ($peptide_id,$com_name,$com_name_src,undef,$gene_symbol,
#			$gene_symbol_evidence,undef,$go_id,$go_src,undef,$ec_id,$ec_src,
#			undef,$tigr_role,$tigr_role_evidence) = split (/\t/, $defline);			
		}
	

		#$self->addDocument($peptide_id, $library, $com_name, $com_name_src, $go_id, $go_src, $ec_id, $ec_src, $hmm_id, undef,$blastEntry,$filter,$cluster);
	
		
		
		#close store
		$fh->close();
		$self->closeIndex;
		
		if($zip) {
			`gzip $tabFile`;
		}
			
		print "Finished Tab file...\n";
	}
	else {
		print "XML already exits $self->{tmp_dir}/$library.xml exists. Skipping xml writing for $library\n";
	}
	unless($self->{xml_only} ) {
		#print "Starting indexing process for $library ...\n";
		#$self->pushIndex($library,$files);
	}
}


sub close() {
	my $self = shift;
	
	#close DB handles
	print "Closing database handles ...\n";
	$self->{METAREP}->disconnect;
	$self->{GO}->disconnect;
	
	if(defined($self->{APIS})){
		$self->{APIS}->disconnect;
	}
	
#	print "Cleaning tmp aerea ...\n";
#	`rm $self->{tmp_dir}/*.xml`;
}

sub createPopulationIndex() {
	my ($self,$populationName) = @_;

	print "Delete population from MySQl...\n";
	$self->deleteLibrary($populationName);
	
	print "Delete population core if exists...\n";
	$self->deleteCore($populationName);
	
	print "Create population core...\n";
	$self->createCore($populationName);
}

sub pushIndex() {
	my ($self,$library,$files) = @_;

	print "Delete library from MySQl...\n";
	$self->deleteLibrary($library);
	
	print "Delete core if exists...\n";
	$self->deleteCore($library);
	
	print "Create core...\n";
	$self->createCore($library);
	
	#print "Create index...\n";
	$self->createIndex($library);

	#print "Optimize index...\n";
	$self->optimizeIndex($library);	
	
	print "Create mysql library...\n";
	$self->createLibrary($library,$files);	
	
	#print "Remove XML file...\n";
	#`rm $self->{tmp_dir}/$library.xml`;
	#$self->copyXml($library);		
}




sub addDocument() {
	my ($self, $peptide_id, $library_id, $com_name, $com_name_src, $go_id, $go_src, $ec_id, $ec_src, $hmm_id, $env_lib,$blastEntry,$filter,$cluster) = @_;	 
	
	my $index = $self->{index};
	
	print $index "<doc>\n";	
	
	#if clean names option is provides, use protein naming utility to clean up names
	if(defined($self->{full_matches})) {		
		my $fullMatchesHashRef = $self->{full_matches} ;	
	
		my @cleanEntries = @{$self->cleanNames($com_name,$com_name_src)};
		$com_name 		= $cleanEntries[0];
		$com_name_src 	= $cleanEntries[1];		
	}	
	
	#core fields
	$self->printSingleValue("peptide_id",$peptide_id);
	$self->printSingleValue("library_id",$library_id);
	$self->printMultiValue("com_name",$com_name);
	$self->printMultiValue("com_name_src",$com_name_src);
	$self->printMultiValue("go_id",$go_id);
	$self->printMultiValue("go_src",$go_src);
	$self->printAncestors("go_tree",$go_id);
	$self->printMultiValue("ec_id",$ec_id);
	$self->printMultiValue("ec_src",$ec_src);			
	$self->printMultiValue("hmm_id",$hmm_id);	
		
	$self->printBlastValues($blastEntry);
	
	#optional fields
	if($self->{APIS}) {
		$self->printAncestors("apis_tree",$peptide_id);
	}
	if(defined($env_lib)){
		$self->printMultiValue("env_lib",$env_lib);
	}
	if(defined($filter)){
		$self->printMultiValue("filter",$filter);
	}
	if(defined($cluster)){
		$self->printMultiValue("cluster_id",$cluster);
	}	
	
	print $index "</doc>\n";		
}


sub printAncestors {
	my ($self,$field,$value) = @_;
	
	my $index = $self->{index};
	
	if($value ne '') {
		
		my @ancestors=undef;
		
		if($field eq 'go_tree') {		
			@ancestors = $self->getGoAncestors($value);
		}
		elsif($field eq 'blast_tree') {			
			@ancestors = $self->getSpeciesAncestors($value);
		}
		elsif($field eq 'apis_tree' ){
			@ancestors = $self->getApisAncestors($value);
		}
		
		if(@ancestors>0) {
			foreach(@ancestors) {
				my $value =  $self->clean($_);
				
				if($value) {
					print $index "<field name=\"$field\">". $self->clean($_)."</field>\n";
				}
			}
		}
	}
}

########################################################
# Takes a blast entry hash and print its values. 
########################################################

sub printBlastValues {
	my ($self,$blastEntry) = @_;
	my $index = $self->{index};
	
	if($blastEntry) {	
		$self->printMultiValue('blast_species',$blastEntry->{species});
		$self->printSingleValue('blast_evalue',$blastEntry->{evalue});
		$self->printSingleValue('blast_evalue_exp',$blastEntry->{evalue_exp});
		$self->printSingleValue('blast_pid',$blastEntry->{pid});
		$self->printSingleValue('blast_cov',$blastEntry->{coverage});
		$self->printAncestors('blast_tree',$blastEntry->{species_id});
	}
}

sub printSingleValue {
	my ($self,$field,$value) = @_;
	my $index = $self->{index};
	
	$value = $self->clean($value);
	
	if($value) {
		print $index "<field name=\"$field\">$value</field>\n";	
	}
}

sub openIndex {
	my ($self,$library) = @_;
	#open output file
	my $outFile	= "$self->{tmp_dir}/$library".".xml";
	
	#save filehandle in variable	
	$self->{index} = FileHandle->new($outFile,'>');	
	
	my $index = $self->{index};

	print $index "<add>\n";
}

sub closeIndex {
	my ($self) = @_;
	
	my $index = $self->{index};

	print $index "</add>\n";
	
	#optimize index (merge segements)
	#print $index "<optimize/>\n";
	
	#prepare index for searching
	#print $index "<commit/>\n";
	
	$index->close();
}


############################
# Trim trailing/leading white
# spaces
############################

sub clean {
	my ($self,$tmp) = @_;
	
	$tmp =~ s/&/&amp;/g;
	$tmp =~ s/</&lt;/g;
	$tmp =~ s/>/&gt;/g;
	$tmp =~ s/\"/&quot;/g;
	$tmp =~ s/'/&apos;/g;
	
	#remove white spaced
	$tmp =~ s/^\s+//g;
	$tmp =~ s/\s+$//g;
		
	return $tmp;
}

########################################################
# Creates a library in the ifx_metagenomics_reports
# database.  
########################################################

sub createLibrary() {
	my ($self,$name,$files) = @_;
	
	my %files = %$files;
	
	my $originalLibraryName = '';
	my $description = '';
	
	#parse irginal library names from files - this helps if symlinks 
	#are used for library names and we want to preserve the orginal file names
	if($self->{is_viral}) {
     	my $command  			= "readlink -f $files{annotation}*";
		my $originalPath 		= `$command`;			
		$originalLibraryName  = basename(dirname($originalPath));		
	}
	else {
     	my $command  			= "readlink -f $files{annotation}*";
		my $originalPath 		= `$command`;		
		$originalPath 			= dirname($originalPath);
		$originalPath 			=~ s/annotation$//;			
		$originalLibraryName  	= basename($originalPath);	
	}
	
	#if orginal library name differs from the original field name, add it to the description
	if($originalLibraryName  ne $name) {
		unless($originalLibraryName ne '.') {
			$description = $originalLibraryName  ;
		}
	}
	
	#derefernce files
	while (my($key, $file) = each(%files)){
			print "readlink -f $files{$key}*\n";
     		my $command  = "readlink -f $files{$key}*";
			my $fullPath = `$command`;	
			$files{$key} = $fullPath;
			print "$key $file $fullPath";
	}
	
	#query
	my $query ="insert ignore into libraries (name,description,project_id,annotation_file,evidence_file,blast_file,hmm_file,com2go_file,filter_file,cluster_file,is_viral,apis_database,created,updated) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,now(),now())";
	print $query."\n";
	
	#prepare query
	my $sth =$self->{METAREP}->prepare($query);
	
	#execute query
	$sth->execute($name,$description,$self->{project_id},$files{annotation},$files{evidence},$files{blast},$files{hmm},$files{com2go},$files{filter},$files{cluster},$self->{is_viral},$self->{apis_db}) or die "Couldn't execute: $DBI::errstr";
}

#sub derefenceFiles() {
#	my ($self,$files) = @_;
#	my %files = %$files;
#	
#	while (my($key, $file) = each(%files)){
#     		my $command  = "readlink -f $files{$key}*";
#			my $fullPath = `$command`;
#			$files{$key} = $fullPath;
#	}
#	
#	return \%files;	
#}


sub deleteLibrary() {
	my ($self,$name) = @_;
	
	my $query ="delete from libraries where name = ?";

	#prepare query
	my $sth =$self->{METAREP}->prepare($query);
	
	$sth->execute($name) or die "Couldn't execute: $DBI::errstr";
}

########################################################
# Takes an array of assignments and prints multiple
# xml entries. Useful for multi-valued fields that are
# separated by /\|\|/.  
########################################################

sub printMultiValue() {
	my ($self,$field,$value) = @_;
	
	$value = $self->clean($value);
	
	
	my $index = $self->{index};
		
	my @values = split (/\|\|/, $value);
	
	if(@values>0) {
		foreach(@values){
			$value = $self->clean($_);
			
			if($value) {								
				#add go filer | the viral pipeline adds other information in the go field
				if($field eq 'go_id') {
					if($value =~ m/^GO:/){
						print $index "<field name=\"$field\">". $value."</field>\n";
					}
				}
				elsif($field eq 'filter') {
					if($value eq 'schmidt_reference') {
						next;
					}
					else {
						print $index "<field name=\"$field\">". $value."</field>\n";
					}
				}
				else {
					print $index "<field name=\"$field\">". $value."</field>\n";
				}
			}
		}
	}

	
#	else {
#		print $index "<field name=\"$field\">". $self->clean($value)."</field>\n";
#	}
}

sub getApisAncestors() {
	my ($self,$peptideId) = @_;
	my $apisClassification = undef;
	
	my ($apisSpecies, $apisGenus, $apisFamily, $apisOrder, $apisPhylum, $apisClass, $apisKingdom);
	
#	my $query ="SELECT (CASE WHEN species !='Mixed' THEN species WHEN genus !='Mixed' THEN genus WHEN family !='Mixed' THEN family
#	 WHEN classification.ord !='Mixed' THEN classification.ord WHEN class !='Mixed' THEN class WHEN phylum !='Mixed' THEN phylum
#	  WHEN kingdom !='Mixed' THEN kingdom ELSE 'Mixed' END) as classification,species,family,ord,phylum,class,kingdom FROM classification
#	   where seq_name=? having classification !='Mixed'";
	   
	my $query ="SELECT species, genus, family, ord, phylum,class,kingdom FROM classification inner join dataset using(dataset)  where seq_name=? order by date_added desc limit 1;";	   
	
	#execute query
	my $sth = $self->{APIS}->prepare($query);
	
	$sth->execute($peptideId);

	$sth->bind_col(1, \$apisSpecies);
	$sth->bind_col(2, \$apisGenus);
	$sth->bind_col(3, \$apisFamily);
	$sth->bind_col(4, \$apisOrder);
	$sth->bind_col(5, \$apisPhylum);
	$sth->bind_col(6, \$apisClass);
	$sth->bind_col(7, \$apisKingdom);
	
	#my ($count) = $sth->fetchrow_array;

	if($sth->fetch) {	
		my $taxonId = undef;
			
		$taxonId = $self->getTaxonIdByName($self->clean($apisSpecies ));
	
		if($taxonId ne '') {
			return $self->getSpeciesAncestors($taxonId);		
		}
		else {
			$taxonId = $self->getTaxonIdByName($self->clean($apisGenus ));
			
			if($taxonId ne '') {
				return $self->getSpeciesAncestors($taxonId);		
			}
			else {
				$taxonId = $self->getTaxonIdByName($self->clean($apisFamily ));
				
				if($taxonId ne '') {
					return $self->getSpeciesAncestors($taxonId);		
				}
				else {
					$taxonId = $self->getTaxonIdByName($self->clean($apisOrder ));
									
					if($taxonId ne '') {
						return $self->getSpeciesAncestors($taxonId);		
					}
					else {
						$taxonId = $self->getTaxonIdByName($self->clean($apisPhylum));
										
						if($taxonId ne '') {
							return $self->getSpeciesAncestors($taxonId);		
						}
						else {
							$taxonId = $self->getTaxonIdByName($self->clean($apisClass ));
											
							if($taxonId ne '') {
								return $self->getSpeciesAncestors($taxonId);		
							}
							else {
								$taxonId = $self->getTaxonIdByName($self->clean($apisKingdom ));
												
								if($taxonId ne '') {
									return $self->getSpeciesAncestors($taxonId);		
								}	
								else{
									#value to indicate that the apis classification could not be mapped to ncbi taxonomy
									return ("-1");
								}						
							}												
						}											
					}				
				}			
			}
		}
	}
}


########################################################
# Takes a species taxon id and returns a concatenated leneage 
# string (seperated by white spaces).
########################################################

sub getSpeciesAncestors() {
	my ($self,$speciesId) = @_;
	my @ancestors = ();
	
	my @speciesIds = split (/\|\|/, $speciesId);
	
	@speciesIds = $self->trimArray(@speciesIds);	
	
	foreach $speciesId (@speciesIds) {
		unshift(@ancestors,$speciesId);
		
		#loop through tree until root has been reached
		while(1) {
			my $parentTaxonId = $self->getParentTaxonId($speciesId);
			
			#add parent to the front of the array 
			unshift(@ancestors,$parentTaxonId);
			
			#stop if root has been reached	
			if($parentTaxonId == 1 || $parentTaxonId eq ''){
				last;
			}	
			
			$speciesId = $parentTaxonId;
		} 
	}
	
	#remove unique entries
	if(@speciesIds>1 ){ 
		my %hash =();
		
		@ancestors = grep {!$hash{$_}++} @ancestors;
	}
	
	return @ancestors;
}

########################################################
# Takes a species taxon id and returns its direct parent.
########################################################

sub getParentTaxonId() {
	my ($self,$speciesId) = @_;
	my $parentTaxonId;
 	
	my $query ="select parent_tax_id from taxonomy where taxon_id=?" ;

	#execute query
	my $sth = $self->{METAREP}->prepare($query);
	$sth->execute($speciesId);

	$sth->bind_col(1, \$parentTaxonId);
	$sth->fetch;
	
	return $parentTaxonId;
}

########################################################
# Takes an apis classification and rank and returns NCBI 
# taxonomy ids.
########################################################

sub getTaxonIdByName() {
	my ($self,$name,$rank) = @_;
	
	my $taxonId;
	
	if($name ne 'Mixed') {		
		my $query ="select taxon_id from taxonomy where name=? ";
		
		#execute query
		my $sth = $self->{METAREP}->prepare($query);
		$sth->execute($name);
	
		$sth->bind_col(1, \$taxonId);
		$sth->fetch;
		
		return $taxonId;
	}
	else {
		return '';
	}
}

########################################################
# Takes a taxon id and rank and returns the name
########################################################


sub getTaxonNameById() {
	my ($self,$taxonId,$rank) = @_;
	
	my $name;
	
	my $query ="select name from taxonomy where taxon_id=? and rank=?";
		
	#execute query
	my $sth = $self->{METAREP}->prepare($query);
	$sth->execute($taxonId,$rank);

	$sth->bind_col(1, \$name);
	$sth->fetch;
	
	return $name;
}


########################################################
# Gets a list of distinct go ancestors for a list of go
# assignments.
########################################################

sub getGoAncestors(){
	
	my ($self,$goTerms) = @_;
	my @ancestors=();
		
	my @goTerms = split (/\|\|/, $goTerms);
	#print 'go-term:'.$goTerms;
	#print 'go-array:'.@goTerms;
	
	@goTerms = $self->trimArray(@goTerms);
	
	my $goTermSelection = join 'or term.acc=',map{qq/'$_'/} @goTerms;
	
	$goTermSelection = "(term.acc=$goTermSelection)";
	
	my $ancestor;
	
	my $query =" SELECT DISTINCT
        substring_index(ancestor.acc,':',-1) 
	 FROM 
	  term
	  INNER JOIN graph_path ON (term.id=graph_path.term2_id)
	  INNER JOIN term AS ancestor ON (ancestor.id=graph_path.term1_id)
	 WHERE $goTermSelection and ancestor.acc!='all' order by distance desc;";

	#execute query
	my $sth = $self->{GO}->prepare($query);
	$sth->execute();

	$sth->bind_col(1, \$ancestor);
	
	while ($sth->fetch) {
		#remove trailing zeros
		$ancestor =~ s/^0*//;
		#print $ancestor ."\n";	
		push(@ancestors,$ancestor);
	}		
	
	return @ancestors;
}

########################################################
# Loop through array and trim white spaces.
########################################################

sub trimArray() {
	my ($self,@array) = @_;
	my @cleanArray=();
	foreach(@array) {
		push(@cleanArray,$self->clean($_));
	}
	return @cleanArray;
}




########################################################
# Delete Solr Core
########################################################

sub deleteCore() {
	my ($self,$core) = @_;

	#delete index files using delete.xml
	print "Deleting index: java -Durl=$self->{solr_master_host}/solr/$core/update -Xms$self->{solr_min_memory} -Xmx$self->{solr_max_memory} -jar $self->{solr_post_jar} $self->{solr_local_dir}/delete.xml\n";
	`java -Durl=$self->{solr_master_host}/solr/$core/update -Xms$self->{solr_min_memory} -Xmx$self->{solr_max_memory} -jar $self->{solr_post_jar} $self->{solr_local_dir}/delete.xml `;
		
	print "Waiting...\n";	
	sleep 120;	
		
	#delete entries in solr.xml on master
	print "Unloading index: curl $self->{solr_master_host}/solr/admin/cores?action=UNLOAD&core=$core \n";
	`curl \"$self->{solr_master_host}/solr/admin/cores?action=UNLOAD&core=$core\"`;
	
	#delete entries in solr.xml on slave
	print "Unloading index: curl $self->{solr_slave_host}/solr/admin/cores?action=UNLOAD&core=$core \n";
	`curl \"$self->{solr_slave_host}/solr/admin/cores?action=UNLOAD&core=$core\"`;	
	
	#delete xml files
	print "Delete xml files: rm $self->{solr_local_dir}/xml/$self->{project_id}/$core.xml \n";
	`rm $self->{solr_local_dir}/xml/$self->{project_id}/$core.xml`;
}

########################################################
# Create Solr Core
########################################################

sub createCore() {
	my ($self,$core) = @_;
	
	#create master core
	print "Create new core: curl $self->{solr_master_host}/solr/admin/cores?action=CREATE&name=$core&instanceDir=$self->{solr_instance_dir}&dataDir=$self->{solr_data_dir}/$self->{project_id}/$core \n";
	`curl \"$self->{solr_master_host}/solr/admin/cores?action=CREATE&name=$core&instanceDir=$self->{solr_instance_dir}&dataDir=$self->{solr_data_dir}/$self->{project_id}/$core\"`;	
	
	#create slave core
	print "Create new core: curl $self->{solr_slave_host}/solr/admin/cores?action=CREATE&name=$core&instanceDir=$self->{solr_instance_dir}&dataDir=$self->{solr_data_dir}/$self->{project_id}/$core \n";
	`curl \"$self->{solr_slave_host}/solr/admin/cores?action=CREATE&name=$core&instanceDir=$self->{solr_instance_dir}&dataDir=$self->{solr_data_dir}/$self->{project_id}/$core\"`;
}

########################################################
# Create lucene index.
########################################################

sub createIndex() {
	my ($self,$core) = @_;
	
	my $file = "$self->{tmp_dir}/$core.xml";
	
	#load single index
	print "Loading Dataset Index: java -Durl=$self->{solr_master_host}/solr/$core/update -Xms$self->{solr_min_memory} -Xmx$self->{solr_max_memory} -jar $self->{solr_post_jar} $file \n";
	`java -Durl=$self->{solr_master_host}/solr/$core/update -Xms$self->{solr_min_memory} -Xmx$self->{solr_max_memory} -jar $self->{solr_post_jar} $file`;
	
	#add index to population
	if(defined($self->{population_name})) {
		print "Loading Population Index: java -Durl=$self->{solr_master_host}/solr/$self->{population_name}/update -Xms$self->{solr_min_memory} -Xmx$self->{solr_max_memory} -jar $self->{solr_post_jar} $file \n";
		`java -Durl=$self->{solr_master_host}/solr/$self->{population_name}/update -Xms$self->{solr_min_memory} -Xmx$self->{solr_max_memory} -jar $self->{solr_post_jar} $file`;
		
	}
}


########################################################
# optimize index.
########################################################

sub optimizeIndex() {
	my ($self,$core) = @_;
	
	print "Optimize Dataset Index: java -Durl=$self->{solr_master_host}/solr/$core/update -Xms$self->{solr_min_memory} -Xmx$self->{solr_max_memory} -jar $self->{solr_post_jar} $self->{solr_local_dir}/optimize.xml \n";
	`java -Durl=$self->{solr_master_host}/solr/$core/update -Xms$self->{solr_min_memory} -Xmx$self->{solr_max_memory} -jar $self->{solr_post_jar} $self->{solr_local_dir}/optimize.xml `;

	#optimize population index
	if(defined($self->{population_name})) {
		print "Optimize Population Index: java -Durl=$self->{solr_master_host}/solr/$self->{population_name}/update -Xms$self->{solr_min_memory} -Xmx$self->{solr_max_memory} -jar $self->{solr_post_jar} $self->{solr_local_dir}/optimize.xml \n";
		`java -Durl=$self->{solr_master_host}/solr/$self->{population_name}/update -Xms$self->{solr_min_memory} -Xmx$self->{solr_max_memory} -jar $self->{solr_post_jar} $self->{solr_local_dir}/optimize.xml `;
	}
}




########################################################
# Moves the created xml in the tmp directory to the 
# index directory.
########################################################

#sub copyXml() {
#	my ($self,$core) = @_;
#	
#	
#	
#	unless(-e "$self->{tmp_dir}/$core.xml") {
#		
#		#compress file
#		print "Zipping xml file: gzip $self->{tmp_dir}/$core.xml \n";
#		`gzip $self->{tmp_dir}/$core.xml`;
#		
#		#create output folder
#		print "Creating xml directory: mkdir -p $self->{solr_local_dir}/xml/$self->{project_id} \n";
#		`mkdir -p $self->{solr_local_dir}/xml/$self->{project_id}`;
#		
#		#move file
#		print "Copying xml file to local dir: cp $self->{tmp_dir}/$core.xml.gz $self->{solr_local_dir}/xml/$self->{project_id} \n";
#		`cp $self->{tmp_dir}/$core.xml.gz $self->{solr_local_dir}/xml/$self->{project_id}`;
#		
#		#uncompress file
#		#print "Unzipping xml file: gunzip $self->{tmp_dir}/$core.xml.gz \n";
#		#`gunzip $self->{tmp_dir}/$core.xml.gz`;
#	}
#}


########################################################
# Parses PANDA AllGroup blast data from viral evidence
# files.
########################################################

sub readViralEvidenceFile {
	my ($self,$evidenceFile) = @_;
		my $zip =0;
	
	if(!open FILE, $evidenceFile) {
		if(-e "$evidenceFile.gz") {
			$zip =1;
			`gunzip $evidenceFile.gz`;
			if(!open FILE, $evidenceFile) {
				die("Could not find $evidenceFile");
			}
		}
	}
	
	my %blastHash;
	#aclame hmm hash
	my %hmmHash;
	my $assigendHit = undef;
	
	while (<FILE>) {

		my $defline = $_;
		my @fields = split(/\t/, $defline);

		my $commonName = $fields[1];

		#ALLGROUP_PEP|query_id|subject_id|subject_definition|query_length|subject_length|coverage|pct_identity|evalue
		my ($peptide_id, $source, $undef, $description,undef,$aclame_hmm_evalue,$coverage,$percent_identity,$evalue) = split(/\t/, $defline);

		#if line describes panda blast hit
		if($source eq 'ALLGROUP_PEP') {
				
			#if a hit for a peptide does not exist yet, add it to hash
			#else skip it (best hit) for viral best coverage, identity, evalue
			if (!exists $blastHash{$peptide_id}) {					
				my %resultHash = ();
				my @tmp        = split('}', $description);
				my @tmp2       = split('{', $tmp[0]);
					
				my (undef,$speciesId)  = split(' taxon:', $tmp2[0]);
				$tmp2[1] =~ s/;//;
					
				#parse evalue exponent 
					
				my $evalueExponent = undef;		
								
				if($evalue == 0) {
					#set evalue to high default value
					$evalueExponent=9999;
				}
				else {
					my @tmp = split('e-',lc($evalue));	
					use POSIX qw( floor );
					$evalueExponent  = floor($tmp[1]);
				}
					
				#handle panda headers	
				my @entries 	= split(/\^\|\^/,$description);
				
				my %uniqueEntry 	= ();
				
				#loop through all panda headers
				foreach my $entry (@entries) {
					
					my @tmp        = split('}', $entry);
					my @tmp2       = split('{', @tmp[0]);	
					my (undef,$speciesId)  = split(' taxon:', @tmp2[0]);
					
					#define species id
					$speciesId = $self->clean($speciesId);
					
					#define species
					$tmp2[1] =~ s/;//;
					my $species = $self->clean($tmp2[1]);				
					
					#if no entries have been found, get names from the database
					if(!$speciesId && $species) {
						$speciesId = $self->getTaxonIdByName($species,'species');
					}
					if($speciesId && !$species) {
						$species = $self->getTaxonNameById($species,'species');
					}						
	
					#check for duplicated species id / species combinations	
					if (!exists $uniqueEntry{"$speciesId$species"}) {
						
						#concatinate multiple species and species ids using ||
						
						#species
						if($resultHash{'species'}) {
							$resultHash{'species'}  = $resultHash{'species'}.'||'. $species;
						}
						else {
							$resultHash{'species'}=   $species;
						}
						
						#species ids
						if($resultHash{'species_id'}) {
							$resultHash{'species_id'}  = $resultHash{'species_id'}.'||'.$speciesId;
						}
						else {
							$resultHash{'species_id'}=  $speciesId;
						}		
						
						#add hash entry to check for uniqueness
						$uniqueEntry{"$speciesId$species"} = "";	
					}							
				}
				#adjust percent identity to reflect value between 0 and 1	
				$percent_identity = $percent_identity/100;	
			
				
				$resultHash{'evalue'} 		= $self->clean($evalue);
				$resultHash{'evalue_exp'} 	= $self->clean($evalueExponent);
				$resultHash{'pid'} 			= $percent_identity;
				$resultHash{'coverage'} 	= $coverage;	
						
				$blastHash{$peptide_id} 	= \%resultHash;		
				$assigendHit = 1;
			}

		}
		elsif($source eq 'ACLAME_HMM') {
			if($aclame_hmm_evalue <= 1e-5) {
				my $hmm = 'ACLAME_'.$coverage;
				
				if (! exists $hmmHash{$peptide_id}) {				
					my @hmms = ();
					push(@hmms,$hmm);
					
					$hmmHash{$peptide_id} = \@hmms;
				}	
				else {
					my $hmmArrayRef = $hmmHash{$peptide_id};
					my @hmms = @$hmmArrayRef;
					
					#add hmm if hmm not yet part of array
					if(! grep (/$hmm/i, @hmms)) {
						push(@hmms,$hmm);
					}
					#print(join(',',$peptide_id,@hmms)."\n");
					$hmmHash{$peptide_id} = \@hmms;
				}
			}
		}		
	}
	if($zip) {
		`gzip $evidenceFile`;
	}	
	
	CORE::close FILE;
	my @results = (\%blastHash, \%hmmHash);
	return \@results;
}

#depricated
sub readProkHmmEntriesFromRawAnnotationFile {
	my ($self, $hmmFile) = @_;
	
	my $zip =0;
	
	if(!open FILE, $hmmFile) {
		if(-e "$hmmFile.gz") {
			$zip =1;
			`gunzip $hmmFile.gz`;
		}
		else {
			die("Could not find $hmmFile");
		}
	}
	
	my %hmmHash;
	
	
	#read file
	while (<FILE>) {
		my $defline = $_;

		my (@fields) =split(/\t/, $defline);
		
		my $peptide_id 	= $self->clean($fields[0]);
		my $evidence 	= $fields[1];
		
		if($evidence =~ /TIGRFAM|PFAM/) {			
			my $hmm 	= $fields[2];
			
			#if no hit has been added, assign hit
			if (! exists $hmmHash{$peptide_id}) {				
				my @hmms = ();
				push(@hmms,$hmm);
				
				$hmmHash{$peptide_id} = \@hmms;
			}	
			else {
				my $hmmArrayRef = $hmmHash{$peptide_id};
				my @hmms = @$hmmArrayRef;
				
				#add hmm if hmm not yet part of array
				if(! grep (/$hmm/i, @hmms)) {
					push(@hmms,$hmm);
				}
				#print(join(',',$peptide_id,@hmms)."\n");
				$hmmHash{$peptide_id} = \@hmms;
			}			
		}
	}
	
	close FILE;
	
	if($zip) {
		`gzip $hmmFile`;
	}
	
	return \%hmmHash;
}

sub readProkHmmEntries {
	my ($self, $hmmFile) = @_;
	
	my $zip =0;
	
	if(!open FILE, $hmmFile) {
		if(-e "$hmmFile.gz") {
			$zip =1;
			`gunzip $hmmFile.gz`;
			if(!open FILE, $hmmFile) {
				die("Could not find $hmmFile");
			}
		}
	}
	
	my %hmmHash;
		
	#read file
	while (<FILE>) {
		my $defline = $_;

		my (@fields) =split(/\t/, $defline);
		
		my $hmm 	= $self->clean($fields[0]);
		
		unless($hmm eq 'No hits above thresholds') {
			my $totalScore 		= $self->clean($fields[12]);
			my $trustedCutoff 	= $self->clean($fields[17]);
			
			#only add hmm hits that have a hit above the trusted cutoff
			if($totalScore >= $trustedCutoff) {
				my $peptide_id 		= $self->clean($fields[5]);
				#if no hit has been added, assign hit
				
				if (! exists $hmmHash{$peptide_id}) {				
					my @hmms = ();
					push(@hmms,$hmm);
					
					$hmmHash{$peptide_id} = \@hmms;
				}	
				else {
					my $hmmArrayRef = $hmmHash{$peptide_id};
					my @hmms = @$hmmArrayRef;
					
					#add hmm if hmm not yet part of array
					if(! grep (/$hmm/i, @hmms)) {
						push(@hmms,$hmm);
					}
					#print(join(',',$peptide_id,@hmms)."\n");
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

sub readVirluenceFile {
	my ($self,$blastFile) = @_;
	
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
	
	my %resultHash;
	my $assigendHit = undef;
	
	#read file
	while (<FILE>) {
		my $defline = $_;
		
		#htab fields
		
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
			
			$description =~ s/\s+/ /g;
			$description =~ s/int \-//g; 
			$description =~ s/\(gi\:[0-9]*\)//g;
			$description =~ s/\(VF[0-9]*\)//g;
			
			if($subject_id) {
				my $entry = undef;
				
				if( $description=~ m/phage/) {
					$entry =   "Virulence_Factor_Phage_".$subject_id." $description\n";
				}
				else {
					$entry =   "Virulence_Factor_".$subject_id." $description\n";
					
				}
				
				#species ids
				if($resultHash{$peptide_id}) {
					$resultHash{$peptide_id}  = $resultHash{$peptide_id}.'||'.$entry;
				}
				else {
					$resultHash{$peptide_id}=  $entry;
				}				
			}	
	}
	
	close FILE;

	if($zip) {
		`gzip $blastFile`;
	}		
	
	return \%resultHash;	
}


sub readProkBlastEntries {
	my ($self,$blastFile) = @_;
	
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
	my $assigendHit = undef;
	
	#read file
	while (<FILE>) {
		my $defline = $_;
		
		#htab fields
		
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
           my $spctlen = int( 1000. * ($subject_end - $subject_start +1) /  $subject_length) / 10.;
           
           if ( $qpctlen >= $spctlen ) {
              $coverage = $qpctlen;
           } else {
              $coverage = $spctlen;
           } 

		#parse evalue exponent 
		
		my $evalueExponent = undef;
		
		if($evalue == 0) {
			#set evalue to high default value
			$evalueExponent=9999;
		}
		else {
			my @tmp = split('e-',lc($evalue));	
			use POSIX qw( floor );
			$evalueExponent  = floor($tmp[1]);
		}
					
		#if no hit has been added, assign hit
		if (! exists $blastHash{$peptide_id}) {				
			my %resultHash = ();
			
			my @entries 	= split(/\^\|\^/,$description);
			
			my %uniqueEntry 	= ();
			
			#loop through all panda headers
			foreach my $entry (@entries) {
				
				my @tmp        = split('}', $entry);
				my @tmp2       = split('{', @tmp[0]);	
				my (undef,$speciesId)  = split(' taxon:', @tmp2[0]);
				
				#define species id
				$speciesId = $self->clean($speciesId);
				
				#define species
				$tmp2[1] =~ s/;//;
				my $species = $self->clean($tmp2[1]);				
				
				#if no entries have been found, get names from the database
				if(!$speciesId && $species) {
					$speciesId = $self->getTaxonIdByName($species,'species');
				}
				if($speciesId && !$species) {
					$species = $self->getTaxonNameById($species,'species');
				}						

				#check for duplicated species id / species combinations	
				if (!exists $uniqueEntry{"$speciesId$species"}) {
					
					#concatinate multiple species and species ids using ||
					
					#species
					if($resultHash{'species'}) {
						$resultHash{'species'}  = $resultHash{'species'}.'||'. $species;
					}
					else {
						$resultHash{'species'}=   $species;
					}
					
					#species ids
					if($resultHash{'species_id'}) {
						$resultHash{'species_id'}  = $resultHash{'species_id'}.'||'.$speciesId;
					}
					else {
						$resultHash{'species_id'}=  $speciesId;
					}		
					
					#add hash entry to check for uniqueness
					$uniqueEntry{"$speciesId$species"} = "";	
				}							
			}
			
			$resultHash{'evalue'} 			= $evalue;				
			$resultHash{'evalue_exp'} 		= $self->clean($evalueExponent);
			
			#adjust percent identity to reflect value between 0 and 1	
			$resultHash{'pid'} 				= $percent_identity/100;
			$resultHash{'coverage'} 		= $coverage/100;
			
			$blastHash{$peptide_id} = \%resultHash;				
		}	
	}
	
	close FILE;

	if($zip) {
		`gzip $blastFile`;
	}		
	
	return \%blastHash;
}

#######################################
# Reads fgo2com file and returns hash
#######################################

sub readGoEntries() {
	my ($self,$goFile) = @_;
	my %goHash;

	my $zip =0;
	
	if(!open FILE, $goFile) {
		if(-e "$goFile.gz") {
			$zip =1;
			`gunzip $goFile.gz`;
			if(!open FILE, $goFile) {
				die("Could not find $goFile");
			}	
		}
	}
	
	while(<FILE>) {
		chomp $_;
		my ($pepId, $goId) = split("\t",$_);
		$pepId = $self->clean($pepId);
		$goId = uc($self->clean($goId));
		$goHash{$pepId}=$goId;
	}
	close FILE;
	
	if($zip) {
		`gzip $goFile`;
	}	
		
	return \%goHash;
}

########################################################
# Uses Protein Naming Utilit to clean up com names
########################################################

sub cleanNames {
	my ($self,$comNames,$sources) = @_; 
	
	my @result = ();
	my %names = ();
	
	my $newComNames = '';
	my $newSources 	= '';
	
	#contains bad names has keys and good names as values	
	my %fullMatchHash 	   = %{$self->{full_matches}};	
	my %uniqueNames 	   = %{$self->{unique_names}};	
	
	#split input names and sources
	my @splitNames   = split(/\|\|/,$comNames);
	my @splitSources = split(/\|\|/,$sources);
			
	#loop through names
	for(my $i=0;$i<@splitNames;$i++) {
		my $name = $self->clean($splitNames[$i]);
		
		#lower case name to search full matches
		my $lcName = lc($name);
		
		#if name is a bad name, replace it with good name
		if(exists $fullMatchHash{$lcName}) {
			$name = $fullMatchHash{$lcName};
			$lcName = lc($name);
		}
		
		#if name has been seen previously with different
		#case, normalize it to the first occurence of the 
		#variation			
		if(exists $uniqueNames{$lcName}) {
			$name = $uniqueNames{$lcName};
		}
		else {
			$uniqueNames{$lcName} = $name;
		}
							
		#to prevent multiple names that are the same (or differ by case),
		#store only the first nane source for the first common name variation
		if(!exists $names{$name}) {			
			$names{$name} = $splitSources[$i];
		} 
	}
	
	#loop through unique names
	while ( my ($name, $source) = each(%names) ) {
		if($newComNames){
			$newComNames .= "||$name";
			$newSources .= "||$source";
		}
		else {
			$newComNames = $name;
			$newSources = $source;
		}
	}
	
	$result[0] = $newComNames;
	$result[1] = $newSources;
	
	#print "$newComNames\t$newSources\n";
	return (\@result);
}

#######################################
# Reads filter file and returns hash
#######################################

sub readFilterEntries() {
	my ($self,$filterFile) = @_;
	my %filterHash;

	my $zip =0;
	
	if(!open FILE, $filterFile) {
		if(-e "$filterFile.gz") {
			$zip =1;
			`gunzip $filterFile.gz`;
			if(!open FILE, $filterFile) {
				die("Could not find $filterFile");
			}	
		}
	}
	
	while(<FILE>) {
		chomp $_;
		my ($readId, $filter) = split("\t",$_);
		$readId = $self->clean($readId);
		$readId = "JCVI_PEP_metagenomic.orf.$readId";
		
		
		$filter = lc($self->clean($filter));
		$filterHash{$readId}=$filter;
	}
	close FILE;
	
	if($zip) {
		`gzip $filterFile`;
	}	
		
	return \%filterHash;
}

#######################################
# Reads env file and returns hash
#######################################

sub readEnvLibEntries() {
	my ($self,$path) = @_;
	my %envHash;

	my $envFile 	= "$path/env_hits/env_hits.tab";
	my $crispFile 	= "$path/crispr_hits/crispr_hits.tab";
	my $estFile 	= "$path/est_hits/est_hits.tab";

	my $counter=0;
	my @envFiles= ($envFile,$crispFile,$estFile);

	foreach(@envFiles) {
		my $zip =0;
		my $envFile = $_;
			
		#handle env 
		if(!open FILE, $envFile) {
			if(-e "$envFile.gz") {
				$zip =1;
				`gunzip $envFile.gz`;
				if(!open FILE, $envFile) {
					die("Could not find $envFile");
				}	
			}
		}
		
		while(<FILE>) {
			chomp $_;
			my ($pepId, $env) = split("\t",$_);
			$pepId = $self->clean($pepId);
			$pepId = "JCVI_PEP_metagenomic.orf.$pepId";
			
			$env = lc($self->clean($env));
			$env =~ s/: //; 
			$env =~ s/\s+/_/g;		
			
			if($counter==0) {
				$env = "env_".lc($env);
			}
			elsif($counter==1) {
				$env = "crispr_".lc($env);
			}
			elsif($counter==2) {
				$env = "est_".lc($env);
			}				
					
			if(exists $envHash{$pepId}) {
				$envHash{$pepId} .= "||".$env;
			}
			else {
				$envHash{$pepId} = $env;
			}
		}
		close FILE;
		
		if($zip) {
			`gzip $envFile`;
		}
		$counter++;	
	}
	
    for my $key ( keys %envHash ) {
        my $value = $envHash{$key};
        print "$key => $value\n";
    }
    
	return \%envHash;
}


#######################################
# Reads filter file and returns hash
#######################################

sub readClusterEntries() {
	my ($self,$clusterFile) = @_;
	my %clusterHash;

	my $zip =0;
	
	if(!open FILE, $clusterFile) {
		if(-e "$clusterFile.gz") {
			$zip =1;
			`gunzip $clusterFile.gz`;
			if(!open FILE, $clusterFile) {
				die("Could not find $clusterFile");
			}	
		}
	}
	
	while(<FILE>) {
		chomp $_;
		my ($readId, $clCluster,$crCluster) = split("\t",$_);
		$readId = $self->clean($readId);
		$clCluster = $self->clean($clCluster);
		$crCluster = $self->clean($crCluster);
		$clusterHash{$readId}= "$clCluster||$crCluster";
	}
	close FILE;
	
	if($zip) {
		`gzip $clusterFile`;
	}	
		
	return \%clusterHash;
}
1;
