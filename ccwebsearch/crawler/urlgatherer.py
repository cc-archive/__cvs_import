
#
# Generic URL Gatherer
# part of the CC RDF Search Project
#
# Copyright Creative Commons
# written by Ben Adida (ben@mit.edu).
# 11/18/2003
#
# GPL 2.0
#

import DB

class URLGatherer:
    # Load up some stuff that needs to be done only once

    # the licenses
    db= DB.db()
    url_rows = db.multirow("select * from rdfs_licenses order by license_url")
    license_urls = []
    for url_row in url_rows:
        license_urls.append(url_row["license_url"])
    db.close()

    def __init__(self,age):
        # store the method_id
        db= DB.db()
        self.method_id = db.oneval("select rdfs_get_method_id('%s')" % self.__class__.__name__)
        db.close()
        # the age is how long ago we consider a URL stale
        self.age= age

    def _log(self, category, offset, range):
        """
        log progress in URL gathering
        """
        db= DB.db()
        db.perform("select rdfs_gather_log(%d, '%s', %d, %d)" % (self.method_id, category, offset, range))
        db.close()

    def getChunkOfURLs(self, offset=0):
        """
        Gather some URLs (as many as chunk_size), and return them as a list.
        This method is meant to be overriden.
        """
        return None

    def getBaseURL(self):
        """
        the base URL used for search engine gatherers
        """
        return "http://creativecommons.org/licenses/"

    def getLicenseURLs(self):
        """
        the license URLs for all CC licenses
        """
        return URLGatherer.license_urls

    def nextCategory(self):
        """
        Go to next category. This needs to be overriden.
        """
        self.offset=0

    def initCategory(self):
        """
        Initialize the category stuff. This needs to be overriden.
        """
        self.offset=0

    def checkProgress(self):
        """
        load up information from the database as to what progress has been made by this gatherer
        in recent times.
        """
        db= DB.db()
        last_log= db.onerow("select * from rdfs_gather_log where gather_date = (select max(gather_date) from rdfs_gather_log where method_id=%d) and method_id=%d" % (self.method_id, self.method_id))
        db.close()

        # If we have no progress logged, we initialize the state of the category situation.
        if last_log == None:
            self.initCategory()
        else:
            self.category= last_log["gather_category"]
            self.offset= int(last_log["start"]) + int(last_log["range"])

        print "Progress is at category %s and offset %d" % (self.category,self.offset)

    def gatherURLs(self, max=None):
        """
        Gather a bunch of URLs and insert them into the database
        Returns 1 if it completed the work but there is more to do
        Returns 0 if there is no more work to do.
        """
        # We get the method ID
        method_id = self.method_id

        # Look up where we are in terms of progress
        # We want to find out what the latest category and offset is from things that are recent
        self.checkProgress()

        # We loop in chunks of size chunk_size
        while 1:
            # If we have a max
            if max != None and self.offset > max:
                break

            print "getting chunk from %d to %d" % (self.offset, self.offset + self.chunk_size)
            urls= self.getChunkOfURLs(offset=self.offset)
            self._log(self.category, self.offset, len(urls))

            # Immediately increment offset
            self.offset += len(urls)

            # Connect to the DB
            db= DB.db()

	    #print db
	    #print urls

            # Loop through the URLs and insert into DB
            for url in urls:
	        print url
                db.perform("select rdfs_url_new(%s,%d)" % (db.dbstr(url),method_id))

            # Close DB
            db.close()

            # If we got fewer URLs than the chunk_size, then that means
            # we're done with this category!
            if len(urls) < self.chunk_size:
                if self.nextCategory():
                    pass
                else:
                    # We have no next category to go to!
                    return 0

        # We're done looping
        return 1
