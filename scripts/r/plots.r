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
# version METAREP v 1.3.4
# author Kelvin Li et al.
# lastmodified 2010-07-09
# license http://www.opensource.org/licenses/mit-license.php The MIT License
###############################################################################

## install package vegan if not already installed
list.of.packages <- c("vegan");
new.packages <- list.of.packages[!(list.of.packages %in% installed.packages()[,"Package"])]
if(length(new.packages)) install.packages(new.packages,dependencies = T)

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
	Option 			= as.numeric(args[2])
	Title 			= as.character(args[3])
	Subtitle 		= as.character(args[4])
	DistanceMethod 	= as.character(args[5])
	ClusterMethod  	= as.character(args[6])
	HeatmapColor 	= as.character(args[7])
	ClusterFile    	= as.character(args[8])
	
	
	CompleteLinkagePlotPDF = paste(InputFileName, "_hclust_plot.pdf", sep="")
	MDSPlotPDF = paste(InputFileName, "_mds_plot.pdf", sep="")
	HeatMapPDF = paste(InputFileName, "_heat_map.pdf", sep="")
	MosaicPlotPDF = paste(InputFileName, "_mosaic_plot.pdf", sep="")
	EliminatedSamplesTXT = paste(InputFileName, ".eliminated.txt", sep="")
	
	cat("\n")
	cat("             Input File Name: ", InputFileName, "\n")
	cat("    CompleteLinkage Plot PDF: ", CompleteLinkagePlotPDF, "\n")
	cat("                MDS Plot PDF: ", MDSPlotPDF, "\n")
	cat("    Mosaic Plot PDF: ", MosaicPlotPDF, "\n")
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
	
	## Load data [last argument]
	A<-as.matrix(read.table(InputFileName))
	
	## Sum up the number of members in each column by doing an outer product
	A.j<-rep(1,nrow(A))%*%A
	
	## Compute sum across each column now
	total_members <- sum(A)
	
	## Compute absolute noise threshold based on fraction
	noise_threshold <- total_members * FRACT_NOISE_THRESHOLD
	cat("Percentage Noise Threshold: ")
	cat(FRACT_NOISE_THRESHOLD)
	cat("\nComputed Absolute Noise Threshold: ")
	cat(noise_threshold)
	cat("\n")
	
	## build a new matrix that excludes any columns with count less than the noise_threshold
	M<-A[,A.j>=noise_threshold]
	
	## Sum of members (columns) across each sample (row)
	Mi.<-M%*%rep(1,ncol(M))
	
	Mi_original <- Mi.
	
	## Try to remove rows that have zero count
	samples_to_keep <- Mi.>0
	samples_to_keep <- samples_to_keep[,1]
	Mi. <- Mi.[samples_to_keep,]
	M <- M[samples_to_keep,]
	
	## Report eliminated samples
	samples_eliminated <- Mi_original[Mi_original==0,]
	num_samples_eliminated <- (length(samples_eliminated))
	elimination_message <- paste("Note: ", num_samples_eliminated, " samples were eliminated.", sep="")
	cat(paste("\n", elimination_message, "\n\n"))
	
	## Normalize the counts by converting to percentages, build new matrix with rbind 
	## (stick rows into Z, b/c Z has to be initialized for rbind to work)
	Z<-M[1,]*100/Mi.[1]
	for (i in 2:dim(M)[1]) {
		Z<-rbind(Z,M[i,]*100/Mi.[i])
	}
	
	## Scale the labels sizes so they don't overlap each other.
	## But limit the scaling so it's not too large.
	numRows<-nrow(A);
	numCols<-ncol(A);
	
	label_scale=42/numRows
	col_scale = 42/numCols
	
	row.names(Z) <- row.names(M)
	
	if(label_scale > 1){
		label_scale = 1
	}
	if(col_scale > 1){
		col_scale = 1
	}
	
	
	###############################################################################
	#
	# Hierarchical Cluster Plot
	#
	###############################################################################	
	if(Option == 7) {
		
		library(vegan)
		
		## draw Hierarchical Clustering Plot
		pdf(CompleteLinkagePlotPDF,width=11,height=(5+(1*numRows/3)))
		
		## calculate pairwise distances
		Zdist<-vegdist(Z, method=DistanceMethod);	
		
		hclustout = hclust(Zdist,method=ClusterMethod)
		
		op <- par(mar=c(5,2,5,17),oma =c(5,2,2,5))
		
		## hierarchical Clustering
		plot(as.dendrogram(hclustout), horiz = TRUE,  cexRow=label_scale* 0.80, sub="",main=Title,xlab=paste(DistanceMethod,' distance'))
		mtext(Subtitle,line= 1,cex= 0.75)
		
		Margins <- capture.output( par()$mar )
		
		## If there were samples eliminated, throw a message on the plot
		if(num_samples_eliminated > 0){
			# Get the plot boundaries and draw elimination message
			cur_parameters=par()
			ranges <- cur_parameters$usr
			text(0,ranges[3]+1.4, elimination_message, pos=4, col="red")
		}
		
		## write distance matrix to file
		write.table(as.matrix(Zdist),ClusterFile, sep = "\t",append=TRUE)
		
		dev.off()
	}
	
	
	##############################################################################
	# Draw MDS Plot
	#
	# isoMDS: non-metric multidimensional scaling 
	#
	# Multidimensional scaling (MDS) is a set of related statistical techniques often used in information visualization for exploring similarities or 
	# dissimilarities in data. MDS is a special case of ordination. An MDS algorithm starts with a matrix of item.item similarities, then assigns a 
	# location of each item in a low-dimensional space, suitable for graphing or 3D visualisation.	
	###############################################################################
	
	if(Option == 8) {
		
		library(MASS)
		library(vegan)
		
		## calculate pairwise distances
		Zdist<-vegdist(Z, method=DistanceMethod);	
		
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
		text(mds$points,labels=names(M[,1]), cex=0.8*label_scale)
		mtext(Subtitle,line= 1,cex= 0.75)
		
		# If there were samples eliminated, throw a message on the plot
		if(num_samples_eliminated > 0){
			# Get the plot boundaries and draw elimination message
			cur_parameters=par()
			ranges <- cur_parameters$usr
			text(ranges[1],ranges[4]-2, elimination_message, pos=4, col="red")
		}
		dev.off()
		
		#write distance matrix to file
		write.table(as.matrix(Zdist),ClusterFile, sep = "\t",append=TRUE)
	}
	
	###############################################################################
	#
	# Heatmap Plot
	#
	###############################################################################	
	
	if(Option == 9) {
		library(gplots)
		
		library(vegan)
		
		pdf(HeatMapPDF, width=11,height=8.5)
		
		if(HeatmapColor == 1) {
			heatcolor = heat.colors(256);
		}
		else if (HeatmapColor == 2) {
			heatcolor = c("#f7feb0","#ecf8b1","#e2f1b1","#d8ebb1","#cee4b2","#c4deb2","#bad8b2","#b0d1b3","#a5cbb3","#9bc5b3","#91beb4","#87b8b4","#7db1b5","#73abb5","#68a5b5","#5e9eb6","#5498b6","#4a92b6","#408bb7","#3685b7");
		}
		else if (HeatmapColor == 3) {
			heatcolor = c("#e7f0fa","#deebf7","#d4e5f3","#cbdff0","#c2daed","#b9d4ea","#b0cfe7","#a7c9e4","#9ec4e1","#95bede","#8cb9db","#82b3d8","#79aed5","#70a8d2","#67a3cf","#5e9dcc","#5598c9","#4c92c6","#438dc3","#3a87c0")
		}		
		else if (HeatmapColor == 4) {
			heatcolor = c("#eef9e7","#e5f5e0","#dbf0d8","#d2ecd1","#c8e8c9","#bfe3c2","#b5dfbb","#acdbb3","#a2d6ac","#99d2a5","#8fce9d","#86c996","#7cc58e","#73c187","#69bc80","#60b878","#56b471","#4daf6a","#43ab62","#3aa75b")
		}		
		
		hclust2  <- function(x, method=ClusterMethod) hclust(x, method=method)
		vegdist2 <- function(x, method=DistanceMethod) vegdist(x, method=method)
		
		## heatmap colors
		## colorset = rev(rainbow(2^8, start=0, end=0.65)
		## redgreen(75)
		## heat.colors(256)
		## ol=rev(heat.colors(256))
		
		heatmap.2(Z,trace="none",col=heatcolor, distfun = vegdist2,
				hclustfun = hclust2,cexRow=label_scale* 0.80,keysize = 1.2,scale = c("none"),
				cexCol=col_scale * 0.95,main=args[3], density.info=c('none'),margins=c(18,18))
		
		mtext(Subtitle,line= 0,cex= 0.75)
		#heatmap(Z, cexRow=label_scale* 0.80,  cexCol=label_scale * 0.63,	
		#main=args[3],key=TRUE,
		#col=rev(rainbow(2^16, start=0, end=0.65)),
		#margins=c(18,8)
		#)
		
		dev.off()
		
		## write distance matrix to file
		## calculate pairwise distances
		Zdist<-vegdist(Z, method=DistanceMethod);	
		
		write.table(as.matrix(Zdist),ClusterFile, sep = "\t",append=TRUE)
		write.table(NULL,ClusterFile, sep = "\t",append=TRUE)
		write.table(as.matrix(t(Z)),ClusterFile, sep = "\t",append=TRUE)
	}	

	###############################################################################
	#
	# Mosaic Plot
	#
	###############################################################################	
	
	if(Option == 10) {
		height = 8+log(numCols)*1.3;
		width = 6+log(numRows)*1.3;
		pdf(MosaicPlotPDF, width=width,height=height);
		
		par(pin=c(width*0.90, height*0.75))
		mosaicplot(A, las = 2,ann=FALSE,main=Title,color=terrain.colors(numCols),adj=1,cex.axis=(0.70-numCols/200))
		
		mtext(Subtitle,line= 1,cex= 0.75)
		par(mar=c(2, 2, 5, 1)) 
		
		Margins <- capture.output( par()$mar )
		Margins <- substr(Margins, 5, nchar(Margins))
		Margins <-
				paste("mar = c(", gsub(" ",",",Margins), ")", sep="")
		
		shortname <- "" # or maybe a filename
		mtext(paste(shortname, " ",
						format(Sys.time(), "%Y-%m-%d %H:%M")),
				cex=0.6, line=0, side=SOUTH<-1, adj=0, outer=TRUE)
		dev.off()
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
