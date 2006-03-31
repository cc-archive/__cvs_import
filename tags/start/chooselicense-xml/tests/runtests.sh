#!/bin/sh

for testfile in *.xml
do
  xsltproc ../chooselicense.xsl $testfile
done
