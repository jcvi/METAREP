#!/bin/bash
#benchmark your solr server set-up

echo "" > solr_benchmark.log
for C in 2 4 8 16 32 64 128 256 512
do
N=$(($C*1000))
echo "ab -n$N -c$C" >> solr_benchmark.log
ab -n$N -c$C 'http://<solr-host>:<solr-port>/solr/<core>/select?q=com_name_txt%3A%2Aepimerase+AND+blast_evalue_exp%3A%7B20+TO+%2A%7D+AND+-blast_tree%3A28211&start=0&rows=20' >> solr_benchmark.log
done
