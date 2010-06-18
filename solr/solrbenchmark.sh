#!/bin/bash
echo "" > solr_results_bigip.log
for C in 2 4 8 16 32 64 128 256 512
do
N=$(($C*1000))
echo "ab -n$N -c$C" >> solr_results_bigip.log
ab -n$N -c$C 'http://172.20.12.25:8989/solr/Manangatang-Managed/select?q=com_name_txt%3A%2Aepimerase+AND+blast_evalue_exp%3A%7B20+TO+%2A%7D+AND+-blast_tree%3A28211&start=0&rows=20' >> solr_results_bigip.log
done