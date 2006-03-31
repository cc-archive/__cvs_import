#!/usr/local/python233/bin/python
#
# Downloading documents in threads
# Ben Adida (ben@mit.edu)
# for Creative Commons
# 11/24/2003
#
# GPL 2.0
#

import threading
import DB
import document
import sys
import os

class Downloader(threading.Thread):
    def __init__(self, n_docs=100):
        self.n_docs= n_docs
        threading.Thread.__init__(self)

    def log(self, str):
        print "[%s][%s] %s" % (self.getName().upper(),os.getpid(),str)
	sys.stdout.flush()

    def touchURL(self, db, url, state='alive'):
        db.perform("select rdfs_url_touch(%s, %s)" % (db.dbstr(url),db.dbstr(state)))

    def run(self):
        """
        Do the actual downloading of documents
        """
        # Grab the URLs to download
        db = DB.db()

	i = 0

        # Loop through them and actually download and save
        while 1:
            urls = db.multirow("select url from rdfs_grab_urls(%d, %s, '90 days'::reltime)" % (self.n_docs, db.dbstr(self.getName())))
            self.log("urls to dl (run %s): %s" % (i,len(urls)))

	    i += 1

            if len(urls) == 0:
                break

            for url_row in urls:
                url= url_row["url"]
                self.log("grabbing %s" % url)
                try:
                    doc = document.Document(url)
                    doc.grab()
                except:
                    self.log("couldn't download URL %s" % url)
                    self.touchURL(db, url, 'comatose')
                    continue
                self.log("done grabbing %s" % url)

                # If we get here, then the URL has been successfully downloaded and,
                # even if we fail later, we don't want to have to deal with reloading it again
                self.touchURL(db, url)

                try:
                    doc.parse()
                except RuntimeError, (strerror):
                    self.log("couldn't parse URL %s: %s" % (url,strerror))
                    continue
                except:
                    self.log("couldn't parse URL %s" % url)
                    continue

                self.log("done parsing %s" % url)

                try:
                    doc.store(db)
                    self.log("done storing %s" % url)
                except UnicodeError, (strerror):
                    self.log("Unicode Problem for %s: %s" % (url,strerror))
                    continue
                #except:
                #    self.log("couldn't store URL %s" % url)
                #    continue

        # (each URL should be marked done when it's saved)
        print 'finished'
        db.close()
        self.log('finished')

downloaders=[]
n_threads = int(sys.argv[1])
print "running %d threads" % n_threads
for i in range(n_threads):
    dl = Downloader(n_docs=1)
    downloaders.append(dl)
    dl.start()
