#!/usr/local/bin/Rscript
source("/opt/wwww/metarep/htdocs/metarep/app/webroot/files/r/metastats/detect_DA_features.r")
jobj <- load_frequency_matrix("/opt/wwww/metarep/tmp/jrw.manmouse.class.matrix")
detect_differentially_abundant_features(jobj, 8,"/opt/wwww/metarep/htodocs/metarep/app/webroot/files/r/metastats/Routput.diffAb")