#!/usr/bin/python

#
# Document Grabber
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
import DB
import ccrdf
import html
import sys
from aaronrdf import cc,dc

class Document:
    db= DB.db()
    method_id = db.oneval("select rdfs_get_method_id('%s')" % "DocumentDownloader")
    db.close()

    def __init__(self, url):
        self.url = url

    def grab(self):
        """
        Grab the page from the web
        """
        req = urllib2.Request(self.url)

        # We open the URL
        url_pointer= urllib2.urlopen(req)

        # We correct the actual URL is, in case there's been 302s
        self.url = url_pointer.geturl()

        # We get the content
        self.content = url_pointer.read()

    def parse(self):
        # Before we parse the RDF here, maybe we have a link to some
        # other page via a link rel?

        # Okay, parse the RDF
        self._extractRdf()

        # We go through all the RDF chunks, looking for the one
        # that is about this local document, and we get all the information
        # from there.
        found = 0
        for rdf_chunk in self.rdf_chunks:
            cc_rdf= ccrdf.ccRdf()
            try:
                cc_rdf.parse(rdf_chunk)
            except Exception, (strerror):
                print "BAD PARSING: %s" % strerror
                continue

            # We check all the works
            for work in cc_rdf.works():
                # Is it about the right document?
                if work.subject != "" and work.subject != self.url:
                    # Special case the situation where the subject is a prefix of the URL
                    if self.url.find(work.subject) == -1:
                        print "BAD SUBJECT: %s" % work.subject
                        continue

                # Okay, we are dealing with the right thing
                found = 1

                # Store the cc work data structure
                self.ccWork = work

        # Have we found it?
        if found == 0:
            raise RuntimeError, 'No CC Work found'

        # Do the raw text
        self._extractRawText()

    def _fallbackExtract(self):
        """
        Extracts RDF segments from a block of text using simple string
        methods; for fallback only. (taken from Nathan Yergler's)
        """

        START_TAG = '<rdf:rdf'
        END_TAG = '</rdf:rdf>'

        lower_text = self.content.lower()

        matches = []
        startpos = 0

        startpos = lower_text.find(START_TAG, startpos)
        while startpos > -1:
            endpos = lower_text.find(END_TAG, startpos)
            if endpos > -1:
                matches.append(text[startpos:endpos+len(END_TAG)])
            startpos = lower_text.find(START_TAG, endpos)

        self.rdf_chunks = matches


    def _extractRdf(self):
        """
        Extracts RDF segments from a textblock; returns a list of strings.
        (Taken from Nathan Yergler)
        """
        # compile the RegEx for extracting RDF
        rdf_regex = re.compile("<rdf:rdf.*?>.*?</rdf:rdf>",
                           re.IGNORECASE|re.DOTALL|re.MULTILINE)

        # extract the RDF bits from the incoming text
        matches = []
        text = self.content.strip()

        try:
            self.rdf_chunks = rdf_regex.findall(text)
        except:
            self._fallbackExtract()

    def _extractRawText(self):
        self.rawVars = {}
        self.raw_text = html.strip(self.content)

        # see if we can get the title
        # We get anything until the first tag, cause there aren't supposed to be other tags inside the title
        title_regex = re.compile("<title>([^<]*)<",
                      re.IGNORECASE|re.DOTALL|re.MULTILINE)

        match = title_regex.search(self.content)
        self.rawVars[dc.title] = match.group(1)

    def _prepareString(self, str):
        # This should probably be reviewed, it's a bit confusing right now
        return str.decode('utf-8','ignore')

    def store(self, db):
        work = self.ccWork

        # extract the fields
        try:
            title = work[dc.title]
        except:
            title = ""
        if str(title) == "":
            title = self.rawVars[dc.title]
	db_title = db.dbstr(title)

        try:
            date = work[dc.date]
        except:
            date = ""
	db_date = db.dbstr(date)

        try:
            description = work[dc.description]
        except:
            description = ""
	db_description = db.dbstr(description)

        try:
            creator = work[dc.creator]
	    print 'creator='+creator
	    sys.stdout.flush()
        except:
            creator = ""
	db_creator = db.dbstr(creator)

        try:
            type = work[dc.type]
        except:
            type = ""
	db_type = db.dbstr(type)

	if len(self.raw_text) > 50000:
	    self.raw_text = self.raw_text[0:50000]
        raw_text = unicode(self.raw_text,'iso-8859-1')
	db_raw_text = db.dbstr(raw_text)

        url = self.url
	db_url = db.dbstr(url)

        # Fix the license_url if it has no end slash (old licenses!)
        # TODO: change this to query the license from CC and use the built-in 302 redirect to do the right thing
        license_url = work.licenses()[0]
        if license_url[-1] != "/":
            license_url += "/"
	db_license_url = db.dbstr(license_url)

        # First make sure the URL is in there (in case it's changed)
        db.perform("select rdfs_url_new(%s, %d)" % (db_url, Document.method_id))

        query = "select rdfs_store_document(%s, rdfs_get_license_id(%s), %s, %s, %s, %s, %s, %s)" % (db_url, db_license_url, db_title, db_date, db_description, db_creator, db_type, db_raw_text)

        # do the update in the DB
        db.perform(query.encode('utf-8','ignore'))


# Test
if __name__ == "__main__":
    #doc= Document('http://shyflower.com/art/Themes/AllAmerican/amer.htm')
    #doc= Document('http://commoncontent.org/catalog/images/clipart/505/xml')
    #doc = Document('http://thecrankyone.diaryland.com/lotsanothin.html')
    #doc = Document('http://thejugglers.org/archives/2003_05.php')
    #doc = Document('http://gondwanaland.com/ml/')
    doc = Document(sys.argv[1])
    doc.grab()
    doc.parse()
    doc.store(DB.db())
