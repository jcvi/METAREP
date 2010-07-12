#*****************************************************************************************************
#*****************************************************************************************************
#  Last modified: 4/14/2009 
#  
#  Author: james robert white, whitej@umd.edu, Center for Bioinformatics and Computational Biology.
#  University of Maryland - College Park, MD 20740
#
#  This software is designed to identify differentially abundant features between two groups
#  Input is a matrix of frequency data. Several thresholding options are available.
#  See documentation for details.
#*****************************************************************************************************
#*****************************************************************************************************

#*****************************************************************************************************
#  detect_differentially_abundant_features:
#  the major function - inputs an R object "jobj" containing a list of feature names and the 
#  corresponding frequency matrix, the argument g is the first column of the second group. 
#  
#  -> set the pflag to be TRUE or FALSE to threshold by p or q values, respectively
#  -> threshold is the significance level to reject hypotheses by.
#  -> B is the number of bootstrapping permutations to use in estimating the null t-stat distribution.
#*****************************************************************************************************
#*****************************************************************************************************
detect_differentially_abundant_features <- function(jobj, g, output, pflag = NULL, threshold = NULL, B = NULL){

#**********************************************************************************
# ************************ INITIALIZE COMMAND-LINE ********************************
# ************************        PARAMETERS       ********************************
#**********************************************************************************
qflag = FALSE;
if (is.null(B)){
  B = 1000;
}
if (is.null(threshold)){
  threshold = 0.05;
}
if (is.null(pflag)){
  pflag = TRUE;
  qflag = FALSE;
}
if (pflag == TRUE){
  qflag = FALSE;
}
if (pflag == FALSE){
  qflag = TRUE;
}

#********************************************************************************
# ************************ INITIALIZE PARAMETERS ********************************
#********************************************************************************

#*************************************
Fmatrix <- jobj$matrix;                   # the feature abundance matrix
taxa <- jobj$taxa;                        # the taxa/(feature) labels of the TAM
nrows = nrow(Fmatrix);                   
ncols = ncol(Fmatrix);
Pmatrix <- array(0, dim=c(nrows,ncols));  # the relative proportion matrix
C1 <- array(0, dim=c(nrows,3));           # statistic profiles for class1 and class 2
C2 <- array(0, dim=c(nrows,3));           # mean[1], variance[2], standard error[3]   
T_statistics <- array(0, dim=c(nrows,1)); # a place to store the true t-statistics 
pvalues <- array(0, dim=c(nrows,1));      # place to store pvalues
qvalues <- array(0, dim=c(nrows,1));      # stores qvalues
#*************************************

#*************************************
#  convert to proportions
#  generate Pmatrix
#*************************************
totals <- array(0, dim=c(ncol(Fmatrix)));
for (i in 1:ncol(Fmatrix)) { 
  # sum the ith column 
  totals[i] = sum(Fmatrix[,i]);
}

for (i in 1:ncols) {   # for each subject
  for (j in 1:nrows) { # for each row
    Pmatrix[j,i] = Fmatrix[j,i]/totals[i];
  }
}


#********************************************************************************
# ************************** STATISTICAL TESTING ********************************
#********************************************************************************

if (ncols == 2){  # then we have a two sample comparison
  #************************************************************
  #  generate p values using chisquared or fisher's exact test
  #************************************************************
  for (i in 1:nrows){           # for each feature
    f11 = sum(Fmatrix[i,1]);
    f12 = sum(Fmatrix[i,2]);
    f21 = totals[1] - f11;
    f22 = totals[2] - f12;
    C1[i,1] = f11/totals[1];                       # proportion estimate
    C1[i,2] = (C1[i,1]*(1-C1[i,1]))/(totals[1]-1); # sample variance
    C1[i,3] = sqrt(C1[i,2]);                       # sample standard error
    C2[i,1] = f12/totals[2];
    C2[i,2] = (C2[i,1]*(1-C2[i,1]))/(totals[2]-1);
    C2[i,3] = sqrt(C2[i,2]); 

    #  f11  f12
    #  f21  f22  <- contigency table format
    contingencytable <- array(0, dim=c(2,2));
    contingencytable[1,1] = f11;
    contingencytable[1,2] = f12;
    contingencytable[2,1] = f21;
    contingencytable[2,2] = f22;

    if (f11 > 20 && f22 > 20){
      csqt <- chisq.test(contingencytable);
      pvalues[i] = csqt$p.value;
    }else{
      ft <- fisher.test(contingencytable, workspace = 8e6, alternative = "two.sided", conf.int = FALSE);
      pvalues[i] = ft$p.value;
    }
    
  }
  
  #*************************************
  #  calculate q values from p values
  #*************************************
  qvalues <- calc_qvalues(pvalues);

}else{ # we have multiple subjects per population

  #*************************************
  #  generate statistics mean, var, stderr    
  #*************************************
  for (i in 1:nrows){ # for each taxa
    # find the mean of each group
    C1[i,1] = mean(Pmatrix[i, 1:g-1]);  
    C1[i,2] = var(Pmatrix[i, 1:g-1]); # variance
    C1[i,3] = C1[i,2]/(g-1);    # std err^2 (will change to std err at end)
  
    C2[i,1] = mean(Pmatrix[i, g:ncols]);  
    C2[i,2] = var(Pmatrix[i, g:ncols]);  # variance
    C2[i,3] = C2[i,2]/(ncols-g+1); # std err^2 (will change to std err at end)
  }

  #*************************************
  #  two sample t-statistics
  #*************************************
  for (i in 1:nrows){                   # for each taxa
    xbar_diff = C1[i,1] - C2[i,1]; 
    denom = sqrt(C1[i,3] + C2[i,3]);
    T_statistics[i] = xbar_diff/denom;  # calculate two sample t-statistic
  }

  #*************************************
  # generate initial permuted p-values
  #*************************************
  pvalues <- permuted_pvalues(Pmatrix, T_statistics, B, g, Fmatrix);

  #*************************************
  #  generate p values for sparse data 
  #  using fisher's exact test
  #*************************************
  for (i in 1:nrows){                   # for each taxa
    if (sum(Fmatrix[i,1:(g-1)]) < (g-1) && sum(Fmatrix[i,g:ncols]) < (ncols-g+1)){
      # then this is a candidate for fisher's exact test
      f11 = sum(Fmatrix[i,1:(g-1)]);
      f12 = sum(Fmatrix[i,g:ncols]);
      f21 = sum(totals[1:(g-1)]) - f11;
      f22 = sum(totals[g:ncols]) - f12;
      #  f11  f12
      #  f21  f22  <- contigency table format
      contingencytable <- array(0, dim=c(2,2));
      contingencytable[1,1] = f11;
      contingencytable[1,2] = f12;
      contingencytable[2,1] = f21;
      contingencytable[2,2] = f22;
      ft <- fisher.test(contingencytable, workspace = 8e6, alternative = "two.sided", conf.int = FALSE);
      pvalues[i] = ft$p.value; 
    }  
  }

  #*************************************
  #  calculate q values from p values
  #*************************************
  qvalues <- calc_qvalues(pvalues);

  #*************************************
  #  convert stderr^2 to std error
  #*************************************
  for (i in 1:nrows){
    C1[i,3] = sqrt(C1[i,3]);
    C2[i,3] = sqrt(C2[i,3]);
  }
}



#*************************************
#  threshold sigvalues and print
#*************************************
sigvalues <- array(0, dim=c(nrows,1));
if (pflag == TRUE){  # which are you thresholding by?
  sigvalues <- pvalues;
}else{
  sigvalues <- qvalues;
}
s = sum(sigvalues <= threshold);
Differential_matrix <- array(0, dim=c(s,9));

dex = 1;
for (i in 1:nrows){
  if (sigvalues[i] <= threshold){
    Differential_matrix[dex,1]   = jobj$taxa[i];
    Differential_matrix[dex,2:4] = C1[i,];
    Differential_matrix[dex,5:7] = C2[i,];
    Differential_matrix[dex,8]   = pvalues[i];  
    Differential_matrix[dex,9]   = qvalues[i];
    dex = dex+1;  
  }
}

#show(Differential_matrix);

Total_matrix <- array(0, dim=c(nrows,9));
for (i in 1:nrows){
  Total_matrix[i,1]   = jobj$taxa[i];
  Total_matrix[i,2:4] = C1[i,];
  Total_matrix[i,5:7] = C2[i,];
  Total_matrix[i,8]   = pvalues[i];
  Total_matrix[i,9]   = qvalues[i];
}

write(t(Total_matrix), output, ncolumns = 9, sep = "\t");

}



#************************************************************************
# ************************** SUBROUTINES ********************************
#************************************************************************

#*****************************************************************************************************
#  calc two sample two statistics
#  g is the first column in the matrix representing the second condition
#*****************************************************************************************************
calc_twosample_ts <- function(Pmatrix, g, nrows, ncols)
{
C1 <- array(0, dim=c(nrows,3));  # statistic profiles
C2 <- array(0, dim=c(nrows,3)); 
Ts <- array(0, dim=c(nrows,1));

if (nrows == 1){
  C1[1,1] = mean(Pmatrix[1:g-1]);
  C1[1,2] = var(Pmatrix[1:g-1]); # variance
  C1[1,3] = C1[1,2]/(g-1);    # std err^2

  C2[1,1] = mean(Pmatrix[g:ncols]);
  C2[1,2] = var(Pmatrix[g:ncols]);  # variance
  C2[1,3] = C2[1,2]/(ncols-g+1); # std err^2
}else{
  # generate statistic profiles for both groups
  # mean, var, stderr
  for (i in 1:nrows){ # for each taxa
    # find the mean of each group
    C1[i,1] = mean(Pmatrix[i, 1:g-1]);  
    C1[i,2] = var(Pmatrix[i, 1:g-1]); # variance
    C1[i,3] = C1[i,2]/(g-1);    # std err^2

    C2[i,1] = mean(Pmatrix[i, g:ncols]);  
    C2[i,2] = var(Pmatrix[i, g:ncols]);  # variance
    C2[i,3] = C2[i,2]/(ncols-g+1); # std err^2
  }
}

# permutation based t-statistics
for (i in 1:nrows){ # for each taxa
  xbar_diff = C1[i,1] - C2[i,1]; 
  denom = sqrt(C1[i,3] + C2[i,3]);
  Ts[i] = xbar_diff/denom;  # calculate two sample t-statistic 
}

return (Ts);

}


#*****************************************************************************************************
#  function to calculate qvalues.
#  takes an unordered set of pvalues corresponding the rows of the matrix
#*****************************************************************************************************
calc_qvalues <- function(pvalues)
{
nrows = length(pvalues);

# create lambda vector
lambdas <- seq(0,0.95,0.01);
pi0_hat <- array(0, dim=c(length(lambdas)));

# calculate pi0_hat
for (l in 1:length(lambdas)){ # for each lambda value
  count = 0;
  for (i in 1:nrows){ # for each p-value in order
    if (pvalues[i] > lambdas[l]){
	  count = count + 1; 	
    }
    pi0_hat[l] = count/(nrows*(1-lambdas[l]));
  }
}

f <- unclass(smooth.spline(lambdas,pi0_hat,df=3));
f_spline <- f$y;
pi0 = f_spline[length(lambdas)];   # this is the essential pi0_hat value

# order p-values
ordered_ps <- order(pvalues);
pvalues <- pvalues;
qvalues <- array(0, dim=c(nrows));
ordered_qs <- array(0, dim=c(nrows));

ordered_qs[nrows] <- min(pvalues[ordered_ps[nrows]]*pi0, 1);
for(i in (nrows-1):1) {
  p = pvalues[ordered_ps[i]];
  new = p*nrows*pi0/i;
  
  ordered_qs[i] <- min(new,ordered_qs[i+1],1);
}

# re-distribute calculated qvalues to appropriate rows
for (i in 1:nrows){
  qvalues[ordered_ps[i]] = ordered_qs[i];
}

################################
# plotting pi_hat vs. lambda
################################
# plot(lambdas,pi0_hat,xlab=expression(lambda),ylab=expression(hat(pi)[0](lambda)),type="p");
# lines(f);

return (qvalues);
}


#*****************************************************************************************************
#  function to calculate permuted pvalues from Storey and Tibshirani(2003)
#  B is the number of permutation cycles
#  g is the first column in the matrix of the second condition 
#*****************************************************************************************************
permuted_pvalues <- function(Imatrix, tstats, B, g, Fmatrix)
{
# B is the number of permutations were going to use!
# g is the first column of the second sample
# matrix stores tstats for each taxa(row) for each permuted trial(column)

M = nrow(Imatrix);
ps <- array(0, dim=c(M)); # to store the pvalues
if (is.null(M) || M == 0){
  return (ps);
}
permuted_ttests <- array(0, dim=c(M, B));
ncols = ncol(Fmatrix);
# calculate null version of tstats using B permutations.
for (j in 1:B){  
  trial_ts <- permute_and_calc_ts(Imatrix, sample(1:ncol(Imatrix)), g);
  permuted_ttests[,j] <- abs(trial_ts); 
}

# calculate each pvalue using the null ts
if ((g-1) < 8 || (ncols-g+1) < 8){
  # then pool the t's together!
  # count how many high freq taxa there are
  hfc = 0;
  for (i in 1:M){                   # for each taxa
    if (sum(Fmatrix[i,1:(g-1)]) >= (g-1) || sum(Fmatrix[i,g:ncols]) >= (ncols-g+1)){
      hfc = hfc + 1;
    }
  }
  # the array pooling just the frequently observed ts  
  cleanedpermuted_ttests <- array(0, dim=c(hfc,B));
  hfc = 1;
  for (i in 1:M){
    if (sum(Fmatrix[i,1:(g-1)]) >= (g-1) || sum(Fmatrix[i,g:ncols]) >= (ncols-g+1)){
      cleanedpermuted_ttests[hfc,] = permuted_ttests[i,];
      hfc = hfc + 1;
    }
  }
  #now for each taxa
  for (i in 1:M){  
    ps[i] = (1/(B*hfc))*sum(cleanedpermuted_ttests > abs(tstats[i]));
  }
}else{
  for (i in 1:M){
    ps[i] = (1/(B+1))*(sum(permuted_ttests[i,] > abs(tstats[i]))+1);
  }
}

return (ps);
}


#*****************************************************************************************************
# takes a matrix, a permutation vector, and a group division g.
# returns a set of ts based on the permutation.
#*****************************************************************************************************
permute_and_calc_ts <- function(Imatrix, y, g)
{
nr = nrow(Imatrix);
nc = ncol(Imatrix);
# first permute the rows in the matrix
Pmatrix <- Imatrix[,y[1:length(y)]];
Ts <- calc_twosample_ts(Pmatrix, g, nr, nc);

return (Ts);
}


#*****************************************************************************************************
#  load up the frequency matrix from a file
#*****************************************************************************************************
load_frequency_matrix <- function(file)
{
  dat2 <- read.table(file,header=FALSE,sep="\t");
  # load names 
  subjects <- array(0,dim=c(ncol(dat2)-1));
  for(i in 1:length(subjects)) {
    subjects[i] <- as.character(dat2[1,i+1]);
  }
  # load taxa
  taxa <- array(0,dim=c(nrow(dat2)-1));
  for(i in 1:length(taxa)) {
    taxa[i] <- as.character(dat2[i+1,1]);
  }

  dat2 <- read.table(file,header=TRUE,sep="\t");
  # load remaining counts
  matrix <- array(0, dim=c(length(taxa),length(subjects)));
  for(i in 1:length(taxa)){
    for(j in 1:length(subjects)){ 
      matrix[i,j] <- as.numeric(dat2[i,j+1]);
    }
  }    
  
  jobj <- list(matrix=matrix, taxa=taxa)
	
  return(jobj);
}

