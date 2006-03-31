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

import urllib2
import string
import re
from urlgatherer import URLGatherer

class AllTheWeb_URLGatherer(URLGatherer):
    def __init__(self, age="1 day", chunk_size=10):
        """
        set up the AllTheWeb URL gatherer with a given chunk size
        """
        self.chunk_size = chunk_size

        # The categories for this gatherer are the URLs for each license
        # We need to store them and set the starting category

        URLGatherer.__init__(self,age)

    def _get_page(self, start, limit):
        url_string = "http://alltheweb.com/search?q=link:%s&o=%s" % (self.category,str(start))

        # Prepare the request
        req= urllib2.Request(url_string)
        # add header for cookie
        req.add_header("Cookie","PREF=h=%s;" % limit)

        # Get page and read content
        url= urllib2.urlopen(req)
        content= url.read()

        return content

    def _get_links(self, content):
        # compile the regexp for matching a link
        link_re= re.compile('<span class="resURL">([^<]*)</span>')
        matches= link_re.findall(content)
        return matches

    def initCategory(self):
        # The first category is the first license URL
        self.category = self.getLicenseURLs()[0]
        print "initializing category to %s" % self.category
        URLGatherer.initCategory(self)

    def nextCategory(self):
        # We check where we are
        cat_num = self.getLicenseURLs().index(self.category)

        # If we're at the end
        if cat_num + 1 >= len(self.getLicenseURLs()):
            return 0

        # Increment
        self.category = self.getLicenseURLs()[cat_num + 1]
        print "new category is %s" % self.category
        URLGatherer.nextCategory(self)
        return 1

    def getChunkOfURLs(self, offset=0):
        # If we are bove 1100, we are done
        if offset >= 1100:
            return []

        html_page = self._get_page(offset, self.chunk_size)
        links = self._get_links(html_page)
        return links


urlg = AllTheWeb_URLGatherer(chunk_size=100)
urlg.gatherURLs()
print urlg.__class__.__name__
