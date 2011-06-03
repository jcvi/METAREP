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
# version METAREP v 1.3.0
# author Johannes Goll
# lastmodified 2011-06-03
# license http://www.opensource.org/licenses/mit-license.php The MIT License
###############################################################################

package METAREP::MetarepDao;

use WebService::Solr;
use strict;
use warnings;
use DBI();;

## specify list of solr servers 
use constant SOLR_SERVICES => qw(localhost:1234/solr);
use constant ROWS => 100;
use constant MIN  => 5;

## constructor
sub new {
	
	my $class = shift;
	my $self = bless({}, $class);
	##specify your database connection 
	$self->{DBH} = DBI->connect("DBI:mysql:metarep;host=localhost",
		"metarep_user", "metarep_password", { 'RaiseError' => 1 });
		
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
	my ($self,$dataset,$query,$start,$rows) = @_;
	my @rows = ();
	my $solr = undef;
	my $hits = undef;

	if(!defined $start) {$start=0};
	if(!defined $rows) {$rows=10};

	my %solrArgs = ('fq'	=> $query,
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
		$row->{peptide_id} 	= $doc->value_for('peptide_id');			
		$row->{com_name} 	= [$doc->values_for( 'com_name' )];
		$row->{com_name_src}= [$doc->values_for( 'com_name_src' )];
		$row->{go_id} = [$doc->values_for( 'go_id' )];
		$row->{go_src} = [$doc->values_for( 'go_src' )];
		$row->{ec_id} = [$doc->values_for( 'ec_id' )];
		$row->{ec_src} = [$doc->values_for( 'ec_src' )];
		$row->{hmm_id} = [$doc->values_for( 'hmm_id' )];
		$row->{blast_species} = $doc->value_for( 'blast_species' );
		$row->{blast_evalue} = $doc->value_for( 'blast_evalue' );
		$row->{cluster_id} = [$doc->values_for( 'cluster_id' )];	
		$row->{filter} = [$doc->values_for( 'filter' )];	
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
	return 'http://'.(SOLR_SERVICES)[1];

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
		my $shardIp =(SOLR_SERVICES)[1]."/$dataset";
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
		$query ="select name,label,description,sample_habitat,sample_filter,sample_depth,sample_altitude,sample_date,sample_latitude,sample_longitude,is_viral from libraries WHERE project_id = ?";
		$sth = $self->{DBH}->prepare($query);
		$sth->execute($id);
	}

	my ($name,$label,$description,$habitat,$depth,$filter,$altitude,$date,$latitude,$longitude,$isViral)=undef;
	
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
		
		push(@libraries,$entry);
	}
	
	return \@libraries;	
}

## takes project_id and returns a
## list of libary names
sub getPopulationLibraries() {
	my ($self,$id) = @_;
	
	my @libraries = ();
		
	my $query 	= undef;	
	my $sth 	= undef;
	
	if(defined $id) {	
		$query ="select l.name as name,label,l.description as description,sample_habitat,sample_filter,sample_depth,sample_altitude,sample_date,sample_latitude,sample_longitude,l.is_viral as is_viral
		from libraries as l inner join libraries_populations on(l.id=library_id) inner join populations as p
		 on(p.id=population_id) WHERE p.name = ?";
		$sth = $self->{DBH}->prepare($query);
		$sth->execute($id);
	}

	my ($name,$label,$description,$habitat,$depth,$filter,$altitude,$date,$latitude,$longitude,$isViral)=undef;
	
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
		
		push(@libraries,$entry);
	}
	
	return \@libraries;	
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
		$path =~ s/\/annotation\/annotation_rules.combined.out.gz//; 	
		$path =~ s/\/annotation\/annotation_rules.combined.out//; 		

		
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
