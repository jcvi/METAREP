#! usr/local/bin/perl

##################################################################
# Description: metarep_loader.pl
#
# Reads tab delimited files and creates Solr/Lucene index files
#
# --------------
# Author: jgoll 
# Email:  jgoll@jcvi.org
# Date:   Jun 22, 2010  
##################################################################

use strict;
use DBI();
use File::Basename;
use Encode;
use utf8;
use Unicode::String;
use Getopt::Long qw(:config no_ignore_case no_auto_abbrev);
use Pod::Usage;

=head1 NAME
metarep_loader.pl generates METAREP lucene indices from METAREP tab delimited files
			
B<--project_id, -i>
	METAREP project id (MySQL table projects, field project_id)			
			
B<--project_dir, -d>
	METAREP project directory that contains one or more tab delimited annotation files

B<--tmp_dir, -r>
	directory to store temporary files (XML files gnerated before the Solr load)
	
B<--solr_url, -s>
	METAREP solr server URL incl. port [default: http://localhost:8983]
	
B<--solr_home_dir, -h>
	Solr server home directory

B<--solr_instance_dir, -w>
	Solr instance (configuration) directory [default: solr_dir/example/solr]

B<--solr_data_dir, -y>
	Solr index directory [default: solr_dir/example/solr/data/]
	
B<--solr_max_mem, -z>
	Solr maximum memory allocation [default: 1000M]
	
B<--mysql_url, -s>
	mySQL URL incl. port [default: http://localhost:3306]

B<--metarep_db, -b>
	METAREP MySQL database name [default: metarep]
	
B<--metarep_username, -u>
	METAREP MySQL username
	
B<--metarep_password, -p>
	METAREP MySQL password

B<--go_db, -g>
	Gene Ontology database name [default: gene_ontology]	

B<--go_username, -e>
	Gene Ontology MySQL username [default: metarep_username]
	
B<--go_password, -f>
	Gene Ontology MySQL password [default: metarep_password]	
	
B<--xml_only, -x>
	Useful for debugging. Generates only XML files in the specified tmp directory without pushing the data to the Solr server. 
	
=back

=head1 AUTHOR

Johannes Goll  C<< <jgoll@jcvi.org> >>

=cut

my %args = ();

#handle user arguments
GetOptions(
	\%args,                
	'version', 	
	'project_id|i=s',
	'project_dir|d=s',
	'tmp_dir|t=s',
	'solr_url|s=s',
	'solr_home_dir|h=s',
	'solr_instance_dir|w=s',
	'solr_data_dir|h=s',
	'solr_max_mem|z=s',
	'mysql_url|m=s',
	'metarep_db|b=s',
	'metarep_username|u=s',
	'metarep_password|p=s',
	'go_db|g=s',
	'go_username|e=s',
	'go_password|f=s',	
	'xml_only|x',	
	'help|man|?',
) || pod2usage(2);

#print help
if($args{help}) {	
	pod2usage(-exitval => 1, -verbose => 1);
}

#check arguments || mandatory fields
if(!defined($args{project_id})) {
	pod2usage(
		-message => "\n\nERROR: A project id needs to be defined.\n",
		-exitval => 1,
		-verbose => 1
	);
}
elsif(!defined($args{project_dir}) || !(-d $args{project_dir})) {
		pod2usage(
			-message =>
"\n\nERROR: A valid project directory needs to be defined.\n",
			-exitval => 1,
			-verbose => 1
		);
}	
elsif(!defined($args{tmp_dir}) || !(-d $args{tmp_dir})) {
	pod2usage(
			-message =>
"\n\nERROR: A valid tmp directory needs to be defined.\n",
			-exitval => 1,
			-verbose => 1
	);
}	
elsif(!defined($args{metarep_username})) {
	pod2usage(
			-message =>
"\n\nERROR: A MySQL username needs to be defined.\n",
			-exitval => 1,
			-verbose => 1
	);
}
elsif(!defined($args{metarep_password})) {
	pod2usage(
			-message =>
"\n\nERROR: A MySQL password needs to be defined.\n",
			-exitval => 1,
			-verbose => 1
	);
}
elsif(!defined($args{solr_home_dir})) {
	pod2usage(
			-message =>
"\n\nERROR: A Solr installation directory needs to be defined.\n",
			-exitval => 1,
			-verbose => 1
	);
}


#set default values
if(!defined($args{solr_url})) {
	$args{solr_url} = "http://localhost:8983";
}
if(!defined($args{mysql_url})) {
	$args{mysql_url} = "http://localhost:3306";
}
if(!defined($args{metarep_db})) {
	$args{metarep_db} = "metarep";
}
if(!defined($args{go_db})) {
	$args{go_db} = "gene_ontology";
}
if(!defined($args{go_username})) {
	$args{go_username} = $args{metarep_username};
}
if(!defined($args{go_password})) {
	$args{go_password} = $args{metarep_password};
}
if(!defined($args{solr_max_mem})) {
	$args{solr_max_mem} = '1000M';
}
if(!defined($args{solr_data_dir})) {
	$args{solr_data_dir} = "$args{solr_home_dir}/example/solr/data/";
}
if(!defined($args{solr_instance_dir})) {
	$args{solr_instance_dir} = "$args{solr_home_dir}/example/solr";
}

#connect to metarep MySQL database
print "Trying to connect to MySQL database=".$args{metarep_db}." host=".$args{mysql_url}."\n";
my $metarepDbConnection = DBI->connect("DBI:mysql:".$args{metarep_db}.";host=".$args{mysql_url}."",$args{metarep_username},$args{metarep_password}, { 'RaiseError' => 0 });

if(!$metarepDbConnection) {
		pod2usage(
			-message =>
"\n\nERROR:Could not connect to $args{metarep_db}.\n",
			-exitval => 1,
			-verbose => 1
	);
}

#connect to Gene Ontology database
print "Trying to connect to MySQL database=".$args{go_db}." host=".$args{mysql_url}."\n";
my $goDbConnection = DBI->connect("DBI:mysql:".$args{go_db}.";host=".$args{mysql_url}."",$args{go_username},$args{go_password}, { 'RaiseError' => 0 });

if(!$goDbConnection) {
		pod2usage(
			-message =>
"\n\nERROR:Could not connect to $args{go_db}.\n",
			-exitval => 1,
			-verbose => 1
	);
}

#read files
opendir(DIR, $args{project_dir});
my @files = grep(/\.tab$/,readdir(DIR));

foreach my $file(@files) {
	print $file."\n";
	&createIndex("$args{project_dir}/$file");
}

#creates a new Lucene index for a dataset
sub createIndex() {
	my $file = shift;
	
	open FILE, "$file" or die "Could not open file $file.";
	
	#parse dataset name
	my $datsetName = basename($file);
	$datsetName =~ s/.tab//;
	
	#create index file
	&openIndex($datsetName);
	
	while(<FILE>) {
		chomp $_;
		
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
           ) = split("\t",  $_);	 
           
        &addDocument($peptideId,$libraryId,$comName,$comNameSrc,$goId,$goSrc,$ecId,$ecSrc,$hmmId,$blastTaxon,$blastEvalue,$blastPid,$blastCov,$filter);         
	}
	&closeIndex();
	&pushIndex($datsetName);
}

#creates index file
sub openIndex {
	my $dataset = shift;
	#open output file
	print "creating index file $args{tmp_dir}/$dataset".".xml\n";
	my $outFile	= "$args{tmp_dir}/$dataset".".xml";
	
	open(INDEX, ">$outFile") || die("Could not create file $outFile.");

	print INDEX "<add>\n";
}

#closes index file
sub openIndex {
	print INDEX "</add>";
	close INDEX;
}

#pushes new index file to Solr server; adds MySQL dataset
sub pushIndex() {
	my $dataset = shift;

	print "Deleting dataset from METAREP MySQL database...\n";
	&deleteMetarepDataset($dataset);
	
	print "Deleting Solr Core (if exists)...\n";
	&deleteSolrCore($dataset);
	
	print "Creating Solr core...\n";
	&createSolrCore($dataset);
	
	print "Loading Solr index...\n";
	&loadSolrIndex($dataset);

	print "Optimizing Solr index...\n";
	&optimizeIndex($dataset);	
	
	print "Adding dataset to METAREP MySQL database....\n";
	&createMetarepDataset($dataset);	
}

#deletes dataset from METAREP MySQL database
sub deleteMetarepDataset() {
	my $name = shift;
	
	my $query ="delete from libraries where name = ?";

	#prepare query
	my $sth =$metarepDbConnection->prepare($query);
	
	$sth->execute($name) or die "Couldn't execute: $DBI::errstr";
}

#deletes Solr core (if exists)
sub deleteSolrCore() {
	my $core = shift;

	#delete all documents of existing index
	print "Deleting index: java -Durl=$args{solr_url}/solr/$core/update -Xms150 -Xmx$args{solr_max_mem} -jar $args{solr_home_dir}/example/exampledocs/post.jar $args{solr_instance_dir}/delete.xml...\n";
	`java -Durl=$args{solr_url}/solr/$core/update -Xms150 -Xmx$args{solr_max_mem} -jar $args{solr_home_dir}/example/exampledocs/post.jar $args{solr_instance_dir}/delete.xml `;
		
	#unload core from core registry
	print "Unloading index: curl $args{solr_url}/solr/admin/cores?action=UNLOAD&core=$core \n";
	`curl \"$args{solr_url}/solr/admin/cores?action=UNLOAD&core=$core\"`;
}

#creates Solr core
sub createSolrCore() {
	my $core = shift;
	
	#create core
	print "Creating new core: curl $args{solr_url}/solr/admin/cores?action=CREATE&name=$core&instanceDir=$args{solr_home_dir}/example/solr&dataDir=$args{solr_instance_dir}/$args{project_id}/$core...\n";
	`curl \"$args{solr_url}/solr/admin/cores?action=CREATE&name=$core&instanceDir=$args{solr_instance_dir}&dataDir=$args{solr_data_dir}/$args{project_id}/$core\"`;	
}

#creates Solr index
sub loadSolrIndex() {
	my $core = shift;
	
	my $file = "$args{tmp_dir}/$core.xml";
	
	#load index
	print "Loading Dataset Index: java -Durl=$args{solr_url}/solr/$core/update -Xms150 -Xmx$args{solr_max_memory} -jar $args{solr_home_dir}/example/exampledocs/post.jar $file ...\n";
	`java -Durl=$args{solr_url}/solr/$core/update -Xms150 -Xmx$args{solr_max_mem} -jar $args{solr_home_dir}/example/exampledocs/post.jar $file`;
}

#optimizes Solr index
sub optimizeIndex() {
	my $core = shift;
	
	#optimize index
	print "Optimize Dataset Index: java -Durl=$args{solr_url}/solr/$core/update -Xms150 -Xmx$args{solr_max_memory} -jar $args{solr_home_dir}/example/exampledocs/post.jar $args{solr_instance_dir}/optimize.xml \n";
	`java -Durl=$args{solr_url}/solr/$core/update -Xms150 -Xmx$args{solr_max_memory}  -jar $args{solr_home_dir}/example/exampledocs/post.jar $args{solr_instance_dir}/optimize.xml `;

}

#creates dataset in METAREP MySQL database
sub createMetarepDataset() {
	my $dataset = shift;
	
	my $query ="insert ignore into libraries (name,project_id,created,updated) VALUES (?,?,now(),now())";
	print $query."\n";
	
	#prepare query
	my $sth =$metarepDbConnection->prepare($query);
	
	#execute query
	$sth->execute($dataset,$args{project_id}) or die "Couldn't execute: $DBI::errstr";
}

#adds lucene document to lucene index
sub addDocument() {
	my ($peptideId,$libraryId,$comName,$comNameSrc,$goId,$goSrc,$ecId,$ecSrc,$hmmId,$blastTaxon,$blastEvalue,$blastPid,$blastCov,$filter) = @_;	 
	
	print INDEX "<doc>\n";	
	
	#write core fields
	&printSingleValue("peptide_id",$peptideId);
	&printSingleValue("library_id",$libraryId);
	&printMultiValue("com_name",$comName);
	&printMultiValue("com_name_src",$comNameSrc);
	&printMultiValue("go_id",$goId);
	&printMultiValue("go_src",$goSrc);
	&printAncestors("go_tree",$goId);
	&printMultiValue("ec_id",$ecId);
	&printMultiValue("ec_src",$ecSrc);			
	&printMultiValue("hmm_id",$hmmId);		
	&printMultiValue("filter",$filter);
	
	print INDEX "</doc>\n";		
}

#writes a single values field to the lucene index
sub printSingleValue {
	my ($field,$value) = @_;
	
	$value = &clean($value);
	
	if($value) {
		print INDEX "<field name=\"$field\">$value</field>\n";	
	}
}

#writes multi-valuse fields
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

#adds ancestors to a taxon or GO ID
sub printAncestors {
	my ($field,$value) = @_;
	
	if($value ne '') {
		
		my @ancestors = undef;
		
		if($field eq 'go_tree') {		
			@ancestors = &getGoAncestors($value);
		}
		elsif($field eq 'blast_tree') {			
			@ancestors = &getTaxonAncestors($value);
		}
		
		if(@ancestors>0) {
			foreach(@ancestors) {
				my $value =  &clean($_);
				
				if($value) {
					print INDEX "<field name=\"$field\">". &clean($_)."</field>\n";
				}
			}
		}
	}
}


#takes a species taxon id and returns an array that contains its lineage
sub getTaxonAncestors() {
	my $speciesId = shift;
	my @ancestors = ();
	
	my @speciesIds = split (/\|\|/, $speciesId);
	
	@speciesIds = &trimArray(@speciesIds);	
	
	foreach $speciesId (@speciesIds) {
		unshift(@ancestors,$speciesId);
		
		#loop through tree until root has been reached
		while(1) {
			my $parentTaxonId = &getParentTaxonId($speciesId);
			
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

#returns array of GO ancestors (integer part of the ID)
sub getGoAncestors(){
	
	my $goTerms = shift;
	my @ancestors=();
		
	my @goTerms = split (/\|\|/, $goTerms);
	#print 'go-term:'.$goTerms;
	#print 'go-array:'.@goTerms;
	
	@goTerms = &trimArray(@goTerms);
	
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
	my $sth = $goDbConnection->prepare($query);	

	$sth->bind_col(1, \$ancestor);
	
	while ($sth->fetch) {
		#remove trailing zeros
		$ancestor =~ s/^0*//;
		push(@ancestors,$ancestor);
	}		
	
	return @ancestors;
}

#returns parent taxon id (NCBI taxonomy)
sub getParentTaxonId() {
	my $speciesId = shift;
	my $parentTaxonId;
 	
	my $query ="select parent_tax_id from taxonomy where taxon_id=?" ;

	#execute query
	my $sth = $metarepDbConnection->prepare($query);
	$sth->execute($speciesId);

	$sth->bind_col(1, \$parentTaxonId);
	$sth->fetch;
	
	return $parentTaxonId;
}

#trims and escapes array values
sub cleanArray() {
	my @array = shift;
	my @cleanArray=();
	foreach(@array) {
		push(@cleanArray,&clean($_));
	}
	return @cleanArray;
}

#trims and escapes special xml characters
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

