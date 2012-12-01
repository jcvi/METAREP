#!/usr/local/bin/Rscript --vanilla

###############################################################################
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
# lastmodified 2010-07-09
# license http://www.opensource.org/licenses/mit-license.php The MIT License
###############################################################################

args 		= commandArgs(TRUE)
infile 		= args[1];
outfile 	= args[2];
test 		= args[3];
propround 	= as.numeric(args[4]);
pvalround 	= as.numeric(args[5]);

data = read.table(file=infile,sep="\t",header=F,colClasses=c("character","numeric","numeric","numeric","numeric"));
n = nrow(data);

## specify result columns
colums  = c("id","count1","count2","prop1","prop2","odds_ratio","relative_risk","p_value","b_value","q_value");

## init result data frame
result = data.frame(data[,1],matrix(cbind(data[,2],data[,4],matrix(rep(NA,n*(length(colums)-3)),ncol=length(colums)-3)),ncol=length(colums)-1,nrow=n),stringsAsFactors=FALSE);

## set column names for data frame
colnames(result) = colums;

## iterate through features
for(i in 1:n) {
	
	## generate 2-2 matrix
	m = matrix(as.numeric(data[i,2:5]),ncol=2,nrow=2);
	
	## proportion one
	result[i,4] = round((m[1,1]/sum(m[,1])),propround);	
	
	## proportion two
	result[i,5] = round((m[1,2]/sum(m[,2])),propround);	

	## log odds_ratio 		 
	result[i,6] = round(log((m[1,1]*m[2,2])/(m[2,1]*m[1,2])),3);
	
	## relative_risk
	result[i,7] = round((m[1,1]/sum(m[,1]))/((m[1,2]/sum(m[,2]))),4);	
	
	## execute test
	result[i,8] = do.call(test,list(m))$p.value;	
}

## correct for multiple testing and round adjusted p/q-values
result[,9]  = round(p.adjust(result$p_value, method = "bonferroni"),pvalround);
result[,10] = round(p.adjust(result$p_value, method = "fdr"),pvalround);

## round non adjusted p-value after using the non-rounded values for multi-testing correction
result[,8] = round(result$p_value,pvalround);
write.table(result, file=outfile, row.names=F,sep="\t", eol = "\n",append=F,quote=F)
q(status=0)