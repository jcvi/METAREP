#!/usr/local/packages/R/bin/Rscript

###############################################################################

FRACT_NOISE_THRESHOLD <- 0.00025

progname <- commandArgs(FALSE)[4]
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

	library(MASS)
	row.names(Z) <- row.names(M)
	#Zdist<-dist(Z)
	Zdist<-dist(Z)

	# Scale the labels sizes so they don't overlap each other.
	# But limit the scaling so it's not ridiculously large.
	numRows<-nrow(A);
	label_scale=42/numRows
	if(label_scale > 1){
		label_scale = 1
	}

	if(args[4] > 1) {
		###############################################################################
		###############################################################################
		# Draw Hierarchical Clustering Plot
		pdf(CompleteLinkagePlotPDF,width=8.5,height=11)
	
		# hclust: "Complete" Hierarchical Clustering
		plot(hclust(Zdist,method=args[3]), cex=label_scale, main=args[2])
	
		# If there were samples eliminated, throw a message on the plot
		if(num_samples_eliminated > 0){
			# Get the plot boundaries and draw elimination message
			cur_parameters=par()
			ranges <- cur_parameters$usr
			text(0,ranges[3]+1.4, elimination_message, pos=4, col="red")
		}
	
		dev.off()
	}
	if(args[4] == 0) {
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
	
		plot(mds$points,type="n", main=args[2], xlim=c(min-margin, max+margin))
		text(mds$points,labels=names(M[,1]), cex=label_scale)
	
		# If there were samples eliminated, throw a message on the plot
		if(num_samples_eliminated > 0){
			# Get the plot boundaries and draw elimination message
			cur_parameters=par()
			ranges <- cur_parameters$usr
			text(ranges[1],ranges[4]-2, elimination_message, pos=4, col="red")
		}
	
		dev.off()
	}

	if(args[4] == 1) {
		###############################################################################
		###############################################################################
		# Draw Dendrogram Heatmap Plot
		pdf(HeatMapPDF, width=11,height=8.5)
	
		heatmap(Z, cexRow=label_scale,  cexCol=label_scale * 0.70,
			main=args[2],
			#xlab="Taxonomies", ylab="Samples",
			col=rev(rainbow(2^16, start=0, end=0.65)),
			margins=c(7,0.5)
			)
	
		dev.off()
	
		##############################################################################
		##############################################################################
		# Generate list of samples that were eliminated
		#
		elimination_list<-names(samples_eliminated)
		cat("\nElimination List: ")
		print(cat(elimination_list), sep="\n")
		write(elimination_list, file=EliminatedSamplesTXT, sep="\n")
	
		##############################################################################
	}
	
		arg_count=arg_count+1;
	
}

writeLines("Done.\n")

q(status=0)
