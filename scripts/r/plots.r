#!/usr/local/bin/Rscript

###############################################################################
# METAREP : High-Performance Comparative Metagenomics Framework (http://www.jcvi.org/metarep)
# Copyright(c)  J. Craig Venter Institute (http://www.jcvi.org)
#
# Licensed under The MIT License
# Redistributions of files must retain the above copyright notice.
#
# link http://www.jcvi.org/metarep METAREP Project
# package metarep
# version METAREP v 1.0.1
# author Kelvin Li et al.
# lastmodified 2010-07-09
# license http://www.opensource.org/licenses/mit-license.php The MIT License
###############################################################################

###############################################################################

FRACT_NOISE_THRESHOLD <- 0.00025

progname <- commandArgs(FALSE)[5]
args <- commandArgs(TRUE)

arg_count=1

if(is.na(args[arg_count])){

	script_name <- unlist(strsplit(progname,"="))[2]
	usage <- paste (
		"\nUsage:\n\t", script_name, "\n\t\t<Input FileName>\n\n",
		"Reads input file and produces two plots:  complete linkage and MDS.\n",
		"Be sure to check for samples that were eliminated due to filtering because they\n",
		"because they failed the noise thresholding.\n",
		"\n")

	writeLines(usage)
	writeLines("Input FileName not defined.\n")
	quit(status=0)
}

###############################################################################
# Main program loop

while(!(is.na(args[arg_count]))){
	InputFileName=args[arg_count]
	Option = as.numeric(args[2])
	
	
	CompleteLinkagePlotPDF = paste(InputFileName, "_hclust_plot.pdf", sep="")
	MDSPlotPDF = paste(InputFileName, "_mds_plot.pdf", sep="")
	HeatMapPDF = paste(InputFileName, "_heat_map.pdf", sep="")
	EliminatedSamplesTXT = paste(InputFileName, ".eliminated.txt", sep="")

	cat("\n")
	cat("             Input File Name: ", InputFileName, "\n")
	cat("    CompleteLinkage Plot PDF: ", CompleteLinkagePlotPDF, "\n")
	cat("                MDS Plot PDF: ", MDSPlotPDF, "\n")
	cat("                Heat Map PDF: ", HeatMapPDF, "\n")
	cat("Eliminated Samples Text File: ", EliminatedSamplesTXT, "\n")

	# Example input:

	#  Grp1 Grp2 Grp3 Grp4 Grp5 Grp6 Grp7 Grp8 Grp9
	# TS1A-PCR-1 0 0 2 2 1 0 0 0 2
	# TS4A-PCR-1 0 0 1 4 3 0 0 0 2
	# TS25A-PCR-1 0 0 0 1 1 0 0 0 0
	# TS1A-PCR-2 2 1 3 0 1 1 0 1 5
	# TS4A-PCR-2 4 0 0 2 1 0 0 1 4
	# TS25A-PCR-2 0 0 3 0 0 0 0 0 0
	# TS1A-MDA-1 1 0 0 0 0 0 0 1 0
	# TS4A-MDA-1 0 1 0 5 0 0 0 0 7
	# TS25A-MDA-1 0 0 0 0 0 0 0 0 3
	# TS1A-MDA-2 0 0 0 0 0 0 1 0 0
	# TS4A-MDA-2 0 0 1 1 0 0 0 0 2
	# TS25A-MDA-2 0 0 0 0 1 0 0 0 1


	###############################################################################
	###############################################################################

	# Load data
	A<-as.matrix(read.table(InputFileName))
	#cat("Original Matrix:\n")
	#print(A)

	# Sum up the number of members in each column by doing an outer product
	A.j<-rep(1,nrow(A))%*%A

	# Compute sum across each column now
	total_members <- sum(A)
	cat("\nTotal Counts: ")
	cat(total_members)
	cat("\n")

	# Compute absolute noise threshold based on fraction
	noise_threshold <- total_members * FRACT_NOISE_THRESHOLD
	cat("Percentage Noise Threshold: ")
	cat(FRACT_NOISE_THRESHOLD)
	cat("\nComputed Absolute Noise Threshold: ")
	cat(noise_threshold)
	cat("\n")

	# build a new matrix that excludes any columns with count less than the noise_threshold
	M<-A[,A.j>=noise_threshold]
	#cat("Filtered (Low Noise) Matrix:\n")
	#print(M)
	#cat("\n\n")

	# Sum of members (columns) across each sample (row)
	Mi.<-M%*%rep(1,ncol(M))
	#cat("Counts per sample (Mi.):\n")
	#print(Mi.)
	#cat("\n\n")
	

	Mi_original <- Mi.

	# Try to remove rows that have zero count
	samples_to_keep <- Mi.>0
	samples_to_keep <- samples_to_keep[,1]
	#cat("samples to keep:")
	#print(samples_to_keep)
	Mi. <- Mi.[samples_to_keep,]
	M <- M[samples_to_keep,]

	# Report eliminated samples
	samples_eliminated <- Mi_original[Mi_original==0,]
	num_samples_eliminated <- (length(samples_eliminated))
	elimination_message <- paste("Note: ", num_samples_eliminated, " samples were eliminated.", sep="")
	cat(paste("\n", elimination_message, "\n\n"))

	# Normalize the counts by converting to percentages, build new matrix with rbind 
	# (stick rows into Z, b/c Z has to be initialized for rbind to work)
	Z<-M[1,]*100/Mi.[1]
	for (i in 2:dim(M)[1]) {
	    Z<-rbind(Z,M[i,]*100/Mi.[i])
	}

	#calculate euclidian distances
	library(MASS)
	row.names(Z) <- row.names(M)
	Zdist<-dist(Z)

	#Scale the labels sizes so they don't overlap each other.
	#But limit the scaling so it's not too large.
	numRows<-nrow(A);
	label_scale=42/numRows
	
	if(label_scale > 1){
		label_scale = 1
	}

	if(Option >= 7 & Option <= 13) {
	###############################################################################
	###############################################################################
	# Draw Hierarchical Clustering Plot
	pdf(CompleteLinkagePlotPDF,width=8.5,height=11)

	# hclust: "Complete" Hierarchical Clustering
	plot(hclust(Zdist,method=args[4]), cex=label_scale, main=args[3])

	# If there were samples eliminated, throw a message on the plot
	if(num_samples_eliminated > 0){
		# Get the plot boundaries and draw elimination message
		cur_parameters=par()
		ranges <- cur_parameters$usr
		text(0,ranges[3]+1.4, elimination_message, pos=4, col="red")
	}
	
	#write distance matrix to file
	write.table(as.matrix(Zdist),args[5], sep = "\t",append=TRUE)

	dev.off()
	}

	if(Option == 14) {
	###############################################################################
	##############################################################################
	# Draw MDS Plot
	#
	# isoMDS: non-metric multidimensional scaling 
	#
	# Multidimensional scaling (MDS) is a set of related statistical techniques often used in information visualization for exploring similarities or 
	# dissimilarities in data. MDS is a special case of ordination. An MDS algorithm starts with a matrix of item.item similarities, then assigns a 
	# location of each item in a low-dimensional space, suitable for graphing or 3D visualisation.

	# Get rid of errors when ismMDS doesn't like 0's in the distance matrix
	for(i in 1:length(Zdist)){
		if(Zdist[i]==0){
			Zdist[i]=1e-323;
		}
	}

	mds<-isoMDS(Zdist)

	# Draw IsoMDS Plot
	pdf(MDSPlotPDF,width=11,height=8.5)

	# Compute the padding inside of the graph so the text labels don't fall off the screen
	min<-min(mds$points[,1])
	max<-max(mds$points[,1])
	width=(max-min)
	margin=width*(0.1)

	plot(mds$points,type="n", main=args[3], xlim=c(min-margin, max+margin),xlab="Dimension 1", ylab="Dimension 2")
	text(mds$points,labels=names(M[,1]), cex=label_scale)

	# If there were samples eliminated, throw a message on the plot
	if(num_samples_eliminated > 0){
		# Get the plot boundaries and draw elimination message
		cur_parameters=par()
		ranges <- cur_parameters$usr
		text(ranges[1],ranges[4]-2, elimination_message, pos=4, col="red")
	}
	dev.off()
	
	#write distance matrix to file
	write.table(as.matrix(Zdist),args[5], sep = "\t",append=TRUE)
	}

	if(Option == 15) {
	###############################################################################
	###############################################################################
	# Draw Dendrogram Heatmap Plot
	pdf(HeatMapPDF, width=11,height=8.5)
	
	library(gplots)
	heatmap.2(Z,trace="none",col=rev(rainbow(2^8, start=0, end=0.65)), distfun = dist,
           hclustfun = hclust,cexRow=label_scale* 0.80,keysize = 1.2,scale = c("none"),
	cexCol=label_scale * 0.63,main=args[3],density.info=c('density'),margins=c(18,8))

	

	#heatmap(Z, cexRow=label_scale* 0.80,  cexCol=label_scale * 0.63,	
	#main=args[3],key=TRUE,
	#col=rev(rainbow(2^16, start=0, end=0.65)),
	#margins=c(18,8)
	#)

	dev.off()
	
	#write distance matrix to file
	write.table(as.matrix(Zdist),args[5], sep = "\t",append=TRUE)
	write.table(NULL,args[5], sep = "\t",append=TRUE)
	write.table(as.matrix(t(Z)),args[5], sep = "\t",append=TRUE)
	}	

	##############################################################################
	##############################################################################
	# Generate list of samples that were eliminated
	#
	elimination_list<-names(samples_eliminated)
	cat("\nElimination List: ")
	print(cat(elimination_list), sep="\n")
	write(elimination_list, file=EliminatedSamplesTXT, sep="\n")

	##############################################################################

	arg_count=arg_count+1;
}

writeLines("Done.\n")

q(status=0)

