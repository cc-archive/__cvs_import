#!/usr/local/python233/bin/python
#
# AllTheWeb URL Gatherer
# part of the CC RDF Search Project
#
# Copyright Creative Commons
# written by Ben Adida (ben@mit.edu).
# 11/18/2003
#
# GPL 2.0
#

import sys
from urlgatherer import URLGatherer

class LineURLGatherer5(URLGatherer):
    def __init__(self, urlfile, age="1 day", chunk_size=10):
        """
        set up the URL gatherer with a given chunk size
        """
        self.chunk_size = chunk_size
        self.urlfile = urlfile

	uf = file(urlfile)
	self.links = []
	for line in uf:
	    myline = line.rstrip()
	    if len(myline) < 250:
	        self.links.append(myline)

        print len(self.links)
        URLGatherer.__init__(self,age)

    def initCategory(self):
        self.category = self.urlfile
        URLGatherer.initCategory(self)

    def getChunkOfURLs(self, offset=0):
        end = offset+self.chunk_size
        return self.links[offset:end]

if __name__ == "__main__":
    urlfile = sys.argv[1]
    urlg = LineURLGatherer5(chunk_size=100,urlfile=urlfile)
    urlg.gatherURLs()
    print urlg.__class__.__name__
