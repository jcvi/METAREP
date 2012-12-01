#! usr/local/bin/perl

###############################################################################
# File: metarep_loader.php
# Description: Data Access Object for programmatic access to data stored in
# METAREP (MySQL and Lucene index files).

# METAREP : High-Performance Comparative Metagenomics Framework (http://www.jcvi.org/metarep)
# Copyright(c)  J. Craig Venter Institute (http://www.jcvi.org)
#
# Licensed under The MIT License
# Redistributions of files must retain the above copyright notice.
#
# link http://www.jcvi.org/metarep METAREP Project
# package metarep
# version METAREP v 1.4.0
# author Johannes Goll
# lastmodified 2011-06-03
# license http://www.opensource.org/licenses/mit-license.php The MIT License
###############################################################################

package METAREP::MetarepDao;

use WebService::Solr;
use strict;
use warnings;
use File::Temp qw/ tempfile tempdir /;
use DBI();
use Switch;

use constant SCRATCH => '/usr/local/scratch/METAGENOMICS/metarep';
use constant SOLR_SERVICES => qw(172.20.13.25:8989/solr);
use constant FASTACMD_PATH => '/usr/local/bin/fastacmd';
use constant SEQUENCE_STORE_PATH => '/usr/local/projects/DB/MGX/mgx-metarep/seq-stor';
use constant SED_PATH => '/usr/local/bin/sed';

## constructor
sub new {
	
	my $class = shift;
	my $self = bless({}, $class);
	$self->{DBH} = DBI->connect("DBI:mysql:ifx_metagenomics_reports;host=mysql51-dmz-pro.jcvi.org",
		"repneomgx", "g+k2_p*LPn", { 'RaiseError' => 1 });
		
	## track user statistics	
	&insertUserStats($self->{DBH});
	
	return $self;
}

## takes query and returns number of hits
sub count() {
	my ($self,$dataset,$query) = @_;
	my $solr = undef;
	
	my %solrArgs = ('fq'	=> $query);

	## handle mutliple datasets via sharding
	if(ref($dataset) eq 'ARRAY'){			
		$solrArgs{'shards'} = $self->getSolrShardArgs($dataset);
		$solr = WebService::Solr->new(&getSolrService."/$dataset->[0]");	
	}
	else {
		$solr = WebService::Solr->new(&getSolrService."/$dataset");	
	}
	
	my $response = $solr->search('*:*',{%solrArgs});

	my $hits = 0;

	## check if valid response
	eval {                          
	   my $test =  $response->ok();	
	   
	} or do {   	                   
	   	$self->error('COUNT',$dataset,$query,$response->raw_response);
	};
	
	if(defined $response->pager) {
		$hits = $response->pager->total_entries;
	}
	
	## clean up
	undef $solr;
	undef $response;
	
	return $hits;
}

##format error
sub error() {
	my ($self,$method,$dataset,$query,$rawResponse) = @_;
	
	if(ref($dataset) eq 'ARRAY'){			
		$dataset = join(',',@{$dataset});	
	}
	
	die("\nMETAREP Programmatic Access Exception\nPlease check the method, dataset and query:\n\n".
		"------------------------------------------------\n".
		"REQUEST\n".
		"METHOD: \t".$method."\n".
		"DATASET(S):\t".$dataset."\n".
		"QUERY:  \t".$query."\n".
		"------------------------------------------------\n".
		"RESPONSE\n".
		"STATUS: \t".$rawResponse->code."\n".
		"MESSAGE:\t".$rawResponse->message."\n".		
		"STATUS: \t".$rawResponse->code."\n".
		"MESSAGE:\t".$rawResponse->message."\n".
		"CONTENT:\n\n".$rawResponse->content."\n");
}

## selects rows based on query
sub select() {
	my ($self,$dataset,$query,$start,$rows,$fields) = @_;
	my @rows = ();
	my $solr = undef;
	my $hits = undef;
	my @fields = ();
	my @defaultFields = qw/peptide_id com_name com_name_src go_id go_src ec_id ko_id ec_src hmm_id
						 blast_species blast_evalue cluster_id filter/;
	
	## handle field specification	
	if($fields) {
		@fields = @$fields;
	}
	else {
		@fields = @defaultFields;
	}
	
	## get all rows if $rows is -1
	if($rows && $rows == -1) {
		$rows  = $self->count($dataset,$query)
	}

	## select all results if no limit is specified
	if(!defined $start && !defined $rows) {
		$start = 0;
		$rows  = $self->count($dataset,$query)
	};
	
	my %solrArgs = ('fq'	=> $query,
                    'fl'	=> join(',',@fields),
                    'start'	=> $start,
                    'rows'	=> $rows
                    );
                    
	## handle mutliple datasets via sharding
	if(ref($dataset) eq 'ARRAY'){			
		$solrArgs{'shards'} = $self->getSolrShardArgs($dataset);
		$solr = WebService::Solr->new(&getSolrService."/$dataset->[0]");	
	}
	else {
		$solr = WebService::Solr->new(&getSolrService."/$dataset");	
	}	
		   	
	my	$response = $solr->search('*:*', {%solrArgs});
	
	## check if valid response
	eval {                          
	   my $test =  $response->ok();	
	   
	} or do {                      
	   	$self->error('SELECT',$dataset,$query,$response->raw_response);
	};

	
	## mapp results to hash
	for my $doc( $response->docs) {
		my $row = undef;	
		foreach my $field(@fields) {
			if($field eq 'peptide_id' || $field eq 'blast_species' || $field eq 'blast_evalue') {
				$row->{$field} 	= $doc->value_for($field);	
			}	
			else {
				$row->{$field} 	= [$doc->values_for($field)];	
			}		
		}	
		push(@rows,$row);
	}
	
	## clean up
	undef $solr;
	undef $response;
	
	return \@rows;
}

## group by query
sub groupBy() {
	my ($self,$dataset,$query,$field,$minCount,$limit,$prefix) = @_;
	my @rows = ();
	my $solr = undef;
	my $hits = undef;

	if(!defined $minCount) {$minCount=1};
	if(!defined $limit) {$limit=20};	

	## specify solr arguments	
	my %solrArgs = ('fq' 				=> $query,
					'facet' 			=> 'true',
                    'facet.field'    	=> $field,
                    'facet.mincount' 	=> $minCount,
                    'facet.limit'		=> $limit);
	
	## set facet prefix if defined 
	if(defined $prefix) {
		$solrArgs{'facet.prefix'} = $prefix;
	} 	

	## handle mutliple datasets via sharding
	if(ref($dataset) eq 'ARRAY'){		
		$solrArgs{'shards'} = $self->getSolrShardArgs($dataset);
		$solr = WebService::Solr->new(&getSolrService."/$dataset->[0]");	
	}
	else {
		$solr = WebService::Solr->new(&getSolrService."/$dataset");	
	}
				
	my	$response = $solr->search('*:*',{%solrArgs});
	
	## check if valid response
	eval {                          
	   my $test =  $response->ok();	
	   
	} or do {                      
	   	$self->error('GROUP BY',$dataset,$query,$response->raw_response);
	};
	
	return &get_facets( $response->facet_counts->{ facet_fields }->{ $field} );
	
	## clean up
	undef $solr;
	undef $response;
}

sub getTmpFile() {
	my $tmp = File::Temp->new( TEMPLATE => 'tempXXXXX',DIR => SCRATCH,SUFFIX => '.ids');
	return $tmp->filename;
}

## pull sequences by query
sub sequence(){
	my ($self,$dataset,$query,$start,$rows) = @_;			
	my $peptideIds = undef;
	my @datasets = ();
	my @selectedDatasets = ();
	my $solr = undef;
	
	

	## select all results if no limit is specified
	if(!defined $start && !defined $rows) {
		$start = 0;
		$rows  = $self->count($dataset,$query)
	};
	
	## init start, rows selection
	my $rowCounter=0;
	my $rowStart  = $start;
	my $rowStop   = ($start+$rows)-1;
	my $resCounter=0;
	
	my %solrArgs = ('fq'	=> $query,
		            'fl' => 'peptide_id',
    );

	## handle multiple datasets
	if(ref($dataset) eq 'ARRAY'){		
		@datasets = @$dataset;
	}
	else {
		@datasets = ($dataset);
	}	
		
	## split populations into libraries
	foreach my $dataset(@datasets) {
		if($self->isPopulation($dataset)) {
			my $libraries = $self->getPopulationLibraries($dataset);
			
			foreach my$library(@$libraries) {
				push(@selectedDatasets,$library->{id});
			} 
		}
		else {
			push(@selectedDatasets,$dataset);
		}
	}
	
	foreach my $selectedDataset (@selectedDatasets) {		
		## throw an error id sequences are missing
		unless($self->hasSequence($selectedDataset)) {
			die("\nMETAREP Missing Sequence Exception\nPlease check if your sequences were added to the METAREP Sequence Store:\n\n".
					"------------------------------------------------\n".
				"METHOD: \tSEQUENCE\n".
				"DATASET(S):\t".$selectedDataset."\n".
				"QUERY:  \t".$query."\n");
		}
		
		if($rowCounter > $rowStop) {
			last;
		}
			
		my $idFile = &getTmpFile();
		my $datasetResCounter=0;
		my $projectId = $self->getLibraryProjectId($selectedDataset);
		$solr = WebService::Solr->new(&getSolrService."/$selectedDataset");	
		open(OUTFILE,">$idFile");
		
		## get row count before the select
		my $count = $self->count($selectedDataset,$query);
		$solrArgs{'start'} = 0;
		$solrArgs{'rows'}  = $count;
		
		my	$response = $solr->search('*:*', {%solrArgs});
		## check if valid response
		eval {                          
		   my $test =  $response->ok();	
		   
		} or do {                      
		   	$self->error('SEQUENCE',$dataset,$query,$response->raw_response);
		};
			
		
		## get peptide IDs
		for my $doc( $response->docs) {
			if($rowCounter >= $rowStart && $rowCounter <= $rowStop) {
				print OUTFILE $doc->value_for('peptide_id')."\n";	
				$datasetResCounter++;
			}
			$rowCounter++;			
		}
		close OUTFILE;
		
		if($datasetResCounter) {
			my $cmd = FASTACMD_PATH." -d ".SEQUENCE_STORE_PATH."/$projectId/$selectedDataset/$selectedDataset -i $idFile | ".SED_PATH." 's/^>lcl\|/>/'";
			print `$cmd`;
		}
	}	
}

##FIXME generic solr query
sub query(){
	my ($self,$dataset,$query,$args) = @_;
	my $result = undef;
	
	my $solr = WebService::Solr->new(&getSolrService."/$dataset");	
	my $response = $solr->search($query,$args);
	$result->{hits} = $response->pager->total_entries;
	
	## handle docs
	if(defined $response->docs) {
		$result->{documents} = $response->docs;
	}
	## handle facets
	if(defined $response->facet_counts) {
		$result->{facets} = $response->facet_counts->{facet_fields};
	}
	return $result;
}

## load balances requests
sub getSolrService() {

	## return only slave for now
	return 'http://'.(SOLR_SERVICES)[0];

#	my $rand = rand();
#	
#	if($rand < 0.5) {
#		return  (SOLR_SERVICES)[0];
#	} 
#	else {
#		return  (SOLR_SERVICES)[1];
#	}
}

sub getSolrShardArgs() {
	my ($self,$datasetsArrayRef) = @_;
		
	my @shardIps = ();
	
	foreach my $dataset (@$datasetsArrayRef) {
		my $shardIp =(SOLR_SERVICES)[0]."/$dataset";
		push(@shardIps,$shardIp);
	}
	return join(',',@shardIps);
}

## returns facets as hash
sub get_facets {
  # convert array of facet/hit-count pairs into a hash; obtuse
  my $array_ref = shift;
  my %facet;
  my $i = 0;
  foreach ( @$array_ref ) {

    my $k = $array_ref->[ $i ]; $i++;
    my $v = $array_ref->[ $i ]; $i++;
    next if ( ! $v );
    $facet{ $k } = $v;

  }
  return \%facet;
}


## returns list of projects
sub getProjects() {
	my $self = shift;
	my @projects =();
		
	my $query ="select id,name from projects order by id asc";
	
	## execute query
	my $sth = $self->{DBH}->prepare($query);
	
	$sth->execute();

	my ($id,$name);
	
	$sth->bind_col(1, \$id);
	$sth->bind_col(2, \$name);

	while ($sth->fetch) {
		my $entry = undef;
		$entry->{id}   = $id; 
		$entry->{name} = $name; 
		
		push(@projects,$entry);
	}
	return \@projects;	
}


## takes project_id and returns a
## list of libary names
sub getProjectLibraries() {
	my ($self,$id) = @_;
	
	my @libraries = ();
		
	my $query 	= undef;	
	my $sth 	= undef;
	
	if(defined $id) {	
		$query ="select name,label,description,sample_habitat,sample_filter,sample_depth,sample_altitude,sample_date,sample_latitude,
		sample_longitude,is_viral,project_id from libraries WHERE project_id = ?";
		$sth = $self->{DBH}->prepare($query);
		$sth->execute($id);
	}

	my ($name,$label,$description,$habitat,$depth,$filter,$altitude,$date,$latitude,$longitude,$isViral,$projectId)=undef;
	
	## assign database columns to variables
	$sth->bind_col(1, \$id);
	$sth->bind_col(2, \$label);
	$sth->bind_col(3, \$description);
	$sth->bind_col(4, \$habitat);
	$sth->bind_col(5, \$filter);
	$sth->bind_col(6, \$depth);
	$sth->bind_col(7, \$altitude);
	$sth->bind_col(8, \$date);
	$sth->bind_col(9, \$latitude);
	$sth->bind_col(10, \$longitude);
	$sth->bind_col(11, \$isViral);
	$sth->bind_col(12, \$projectId);

	while ($sth->fetch) {
		my $entry = undef;
		
		## set hash keys and values
		$entry->{id} 			= $id; 
		$entry->{label}		 	= $label || "NA";
		$entry->{description} 	= $description || "NA";
		$entry->{habitat} 		= $habitat || "NA"; 
		$entry->{filter} 		= $filter || "NA"; 
		$entry->{depth} 		= $depth || "NA"; 
		$entry->{date} 			= $date || "NA"; 
		$entry->{altitude} 		= $altitude || "NA"; 
		$entry->{longitude} 	= $longitude || "NA";
		$entry->{latitude} 		= $latitude || "NA";
		
		if($isViral) {
			$entry->{pipeline} = 'viral' || "NA";
		}
		else {
			$entry->{pipeline} = 'prok' || "NA";
		}
		$entry->{projectId} 		= $projectId || "NA";
		
		push(@libraries,$entry);
	}
	
	return \@libraries;	
}

## takes population name and returns a
## list of libary names
sub getPopulationLibraries() {
	my ($self,$id) = @_;
	
	my @libraries = ();
		
	my $query 	= undef;	
	my $sth 	= undef;
	
	if(defined $id) {	
		$query ="select l.name as name,label,l.description as description,sample_habitat,sample_filter,sample_depth,
		sample_altitude,sample_date,sample_latitude,sample_longitude,l.is_viral as is_viral,l.project_id
		from libraries as l inner join libraries_populations on(l.id=library_id) inner join populations as p
		 on(p.id=population_id) WHERE p.name = ?";
		$sth = $self->{DBH}->prepare($query);
		$sth->execute($id);
	}

	my ($name,$label,$description,$habitat,$depth,$filter,$altitude,$date,$latitude,$longitude,$isViral,$projectId)=undef;
	
	## assign database columns to variables
	$sth->bind_col(1, \$id);
	$sth->bind_col(2, \$label);
	$sth->bind_col(3, \$description);
	$sth->bind_col(4, \$habitat);
	$sth->bind_col(5, \$filter);
	$sth->bind_col(6, \$depth);
	$sth->bind_col(7, \$altitude);
	$sth->bind_col(8, \$date);
	$sth->bind_col(9, \$latitude);
	$sth->bind_col(10, \$longitude);
	$sth->bind_col(11, \$isViral);
	$sth->bind_col(12, \$projectId);

	while ($sth->fetch) {
		my $entry = undef;
		
		## set hash keys and values
		$entry->{id} 			= $id; 
		$entry->{label}		 	= $label || "NA";
		$entry->{description} 	= $description || "NA";
		$entry->{habitat} 		= $habitat || "NA"; 
		$entry->{filter} 		= $filter || "NA"; 
		$entry->{depth} 		= $depth || "NA"; 
		$entry->{date} 			= $date || "NA"; 
		$entry->{altitude} 		= $altitude || "NA"; 
		$entry->{longitude} 	= $longitude || "NA";
		$entry->{latitude} 		= $latitude || "NA";
		
		if($isViral) {
			$entry->{pipeline} = 'viral' || "NA";
		}
		else {
			$entry->{pipeline} = 'prok' || "NA";
		}
		$entry->{projectId} = $projectId || "NA";
		
		push(@libraries,$entry);
	}
	
	return \@libraries;	
}

sub isPopulation() {
	my ($self,$id) = @_;
	my $query 	= undef;	
	my $sth 	= undef;
	my $count = 0;
	
	if(defined $id) {	
		$query ="select count(*) from populations WHERE name = ?";
		$sth = $self->{DBH}->prepare($query);
		$sth->execute($id);	
		$sth->bind_col(1, \$count);
		$sth->fetch;
	}	
	return $count;
}

sub hasSequence() {
	my ($self,$id) = @_;
	my $query 	= undef;	
	my $sth 	= undef;
	my $hasSequence = 0;
	
	if(defined $id) {	
		$query ="select has_sequence from libraries WHERE name = ?";
		$sth = $self->{DBH}->prepare($query);
		$sth->execute($id);	
		$sth->bind_col(1, \$hasSequence);
		$sth->fetch;
	}	
	return $hasSequence;
}

## get library project id
sub getLibraryProjectId() {
	my ($self,$id) = @_;
	my $query 	= undef;	
	my $sth 	= undef;
	my $projectId = 0;
	$query ="select project_id from libraries WHERE name = ?";
	$sth = $self->{DBH}->prepare($query);
	$sth->execute($id);	
	$sth->bind_col(1, \$projectId);
	$sth->fetch;		
	return $projectId;
}

## search tree data using tree id parent_id name level ext_id
sub getTreeMeta() {
	my ($self,$args) = @_;
	my @supportedArgs = qw/tree id parent_id name level ext_id/;
	
	my @tree 	= ();
	my $query 	= undef;	
	my $sth 	= undef;	
	
	my @keys = keys %$args;
	
	## check if arguments are valid
	foreach my $key(@keys) {
		unless(grep $_ eq $key ,@supportedArgs) {
			die("\nMETAREP Meta Data Exception\nArgument '$key' is not supported. 
			The following trees are supported:\n".join(", ",@supportedArgs)."\n".
					"------------------------------------------------\n");			
		}
	}
	
	## specify database table
	switch($args->{tree}) {
		case 'taxonomyApis' {  
			$query = "select taxon_id,parent_tax_id,name,rank,'' as external_id from taxonomy_apis";
		}
		case 'taxonomyBlast' {
			$query = "select taxon_id,parent_tax_id,name,rank,'' as external_id from taxonomy";
		}
		case 'pathwayKeggEc' {
			$query = "select id,parent_id,name,level,external_id,ec_id from kegg_pathways_ec";
		}	
		case 'pathwayKeggKo' {
			$query = "select id,parent_id,name,level,external_id,ko_id from kegg_pathways_ko";
		}				
		case 'pathwayMcycEc' {
			$query = "select id,parent_id,name,level,external_id,ec_id from metacyc_pathways";
		}
		else {
			die("\nMETAREP Meta Data Exception\nMode '$args->{tree}' is not supported. The following trees are supported:\ntaxonomyBlast, taxonomyApis, pathwayKeggEc, pathwayKeggKo, pathwayMcycEc\n".
					"------------------------------------------------\n");	
		}
	}
	
	## build where clause
	unless( exists $args->{tree} && keys %$args == 1) {
		$query .= " WHERE ";
		
		if(exists $args->{id}) {
			if($args->{tree} =~ m/taxonomy/) {	
				$query .= "taxon_id= $args->{id} AND ";
			}
			else {
				$query .= "id= $args->{id} AND ";
			}					
		}
		if(exists $args->{parent_id}) {
			if($args->{tree} =~ m/taxonomy/) {	
				$query .= "parent_tax_id= $args->{parent_id} AND ";
			}
			else {
				$query .= "parent_id= $args->{parent_id} AND ";
			}					
		}
		if(exists $args->{name}) {
			$query .= "name = '$args->{name}' AND ";				
		}	
		if(exists $args->{level}) {
			if($args->{tree} =~ m/taxonomy/) {	
				$query .= "rank = '$args->{level}' AND ";
			}
			else {
				$query .= "level = '$args->{level}' AND ";
			}			
		}	
		if(exists $args->{ext_id}) {
			unless($args->{tree} =~ m/taxonomy/) {	
				$query .= "external_id = '$args->{ext_id}'";
			}			
		}				
		$query =~ s/AND $//;
	}

	$sth = $self->{DBH}->prepare($query);
	$sth->execute();			
	
	my ($id,$parentId,$name,$levelId,$extId,$ecId,$koId) = undef;
	$sth->bind_col(1, \$id);
	$sth->bind_col(2, \$parentId);
	$sth->bind_col(3, \$name);
	$sth->bind_col(4, \$levelId);	
	$sth->bind_col(5, \$extId);	
	
	if($args->{tree} =~ m/Ec$/) {
		$sth->bind_col(6, \$ecId);	
	}
	elsif($args->{tree} =~ m/Ko$/) {
		$sth->bind_col(6, \$koId);	
	}		
		
	while ($sth->fetch) {
		my $treeEntry = undef;
		
		$treeEntry->{id} = $id;
		$treeEntry->{parent_id} = $parentId;
		$treeEntry->{name} = $name;
		$treeEntry->{level} = $levelId;
		$treeEntry->{ext_id} = $extId;
		
		if($args->{tree} =~ m/Ec$/) {
			$treeEntry->{ec_id} = $ecId;
		}
		elsif($args->{tree} =~ m/Ko$/) {
			$treeEntry->{ko_id} = $koId;
		}
					
		push(@tree,$treeEntry);
	}
	return \@tree;			
}

## takes project_id and returns a
## list of population names
sub getProjectPopulations() {
	my ($self,$id) = @_;
	
	my @populations = ();
		
	my $query 	= undef;	
	my $sth 	= undef;
	
	if(defined $id) {	
		$query ="select name as id,description,is_viral from populations WHERE project_id = ?";
		$sth = $self->{DBH}->prepare($query);
		$sth->execute($id);
	}

	my ($name,$description,$isViral);
	
	$sth->bind_col(1, \$id);
	$sth->bind_col(2, \$description);

	while ($sth->fetch) {
		my $entry = undef;
		$entry->{id} = $id; 
		$entry->{description} = $description;

		if($isViral) {
			$entry->{pipeline} = 'viral';
		}
		else {
			$entry->{pipeline} = 'prok';
		}
				
		push(@populations,$entry);
	}
	
	return \@populations;	
}

sub getPathsByProject() {
	my ($self,$projectId) = @_;
	my %paths = ();

	my $query ="select name,annotation_file from libraries where project_id=? and annotation_file !=''";

	#execute query
	my $sth = $self->{DBH}->prepare($query);
	$sth->execute($projectId);

	my (
		$path,
		$name
	);
	$sth->bind_col(1, \$name);
	$sth->bind_col(2, \$path);
	
	while ($sth->fetch) {
		$path =~ s/\/prok-annotation\/annotation_rules.combined.out.gz//; 	
		$path =~ s/\/prok-annotation\/annotation_rules.combined.out//; 		

		
		chomp $path;
		$paths{$name}=$path;
	}
	return %paths;
}



## logs users statistic (username and date)
sub insertUserStats() {
	my $db = shift;
	
	## get user of script
	my $user = $ENV{LOGNAME} || $ENV{USER} || getpwuid($<);
	
	## include user name in category field	
	my $category = "programmatic access | $user";
	my $query ="INSERT INTO user_stats (user_id,category,created) VALUES (315,'$category',CURDATE())";
	my $sth = $db->prepare($query);
	$sth->execute();
}
1;