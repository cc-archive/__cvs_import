"""
pyarchive.submission

A Python library which provides an interface for uploading files to the
Internet Archive.

copyright 2004, Creative Commons, Nathan R. Yergler
"""

__id__ = "$Id$"
__version__ = "$Revision$"
__copyright__ = '(c) 2004, Creative Commons, Nathan R. Yergler'
__license__ = 'licensed under the GNU GPL2'

import cStringIO as StringIO
import ftplib

from pyarchive.exceptions import MissingParameterException

class ArchiveItem:
    """
    <metadata>
      <collection>opensource_movies</collection>
      <mediatype>movies</mediatype>
      <title>My Home Movie</title>
      <runtime>2:30</runtime>
      <director>Joe Producer</director>
    </metadata>    
    """

    def __init__(self, identifier, collection, mediatype,
                 title, runtime=None, adder=None, license=None):
        self.files = []
        self.identifier = identifier
        self.collection = collection
        self.mediatype = mediatype
        self.title = title

        self.metadata = {}

        self.metadata['runtime'] = runtime
        self.metadata['adder'] = adder
        self.metadata['license'] = license
        
        self.server = 'audio-uploads.archive.org'

    def __setitem__(self, key, value):
        self.metadata[key] = value

    def __getitem__(self, key):
        return self.metadata[key]
    
    def addFile(self, filename):
        self.files.append(ArchiveFile(filename))

        # set the running time to defaults
        self.files[-1].runtime = self.metadata['runtime']

        # return the added file object
        return self.files[-1]
    
    def metaxml(self):
        """Generates _meta.xml to use in submission;
        returns a file-like object."""

        result = StringIO.StringIO()

        result.write('<metadata>')

        # write the required keys
        result.write("""
        <title>%s</title>
        <collection>%s</collection>
        <mediatype>%s</mediatype>
        """ % (self.title, self.collection, self.mediatype) )
        
        # write any additional metadata
        for key in self.metadata:
            if self.metadata[key] is not None:
                result.write('<%s>%s</%s>\n' % (key, self.metadata[key], key) )
        
        result.write('</metadata>')

        result.seek(0)
        return result
        
    def filesxml(self):
        """Generates _files.xml to use in submission;
        returns a file-like object."""
        
        result = StringIO.StringIO()

        result.write('<files>\n')
        for archivefile in self.files:
            result.write(archivefile.fileNode())
        result.write('</files>\n')

        result.seek(0)
        return result

    def sanityCheck(self):
        """Perform sanity checks before submitting to archive.org"""
        # do some simple sanity checks
        if None in (self.identifier, self.collection, self.mediatype):
            raise MissingParameterException

        if len(self.files) < 1:
            raise MissingParameterException

        for archivefile in self.files:
            archivefile.sanityCheck()
        
        
    def submit(self, username, password, server=None):
        """Submit the files to archive.org"""

        # set the server/adder (if necessary)
        if server is not None:
            self.server = server

        if self.metadata['adder'] is None:
            self.metadata['adder'] = username

        # make sure we're ready to submit
        self.sanityCheck()
        
        # connect to the FTP server
        ftp = ftplib.FTP(self.server)
        ftp.login(username, password)

        # create a new folder for the submission
        ftp.mkd(self.identifier)
        ftp.cwd(self.identifier)

        # upload the XML files
        ftp.storlines("STOR %s_meta.xml" % self.identifier,
                      self.metaxml())
        ftp.storlines("STOR %s_files.xml" % self.identifier,
                      self.filesxml())

        # upload each file
        for archivefile in self.files:
            ftp.storbinary("STOR %s" % archivefile.filename,
                           file(archivefile.filename, 'rb'))

        ftp.quit()
        
        # call the import url, check the return result

class ArchiveFile:
    def __init__(self, filename):
        self.filename = filename
        self.runtime = None
        self.source = None
        self.format = None

    def fileNode(self):
        return """
        <file name="%s" source="%s">
          <runtime>%s</runtime>
          <format>%s</format>
        </file>
        """ % (self.filename, self.source, self.runtime, self.format)

    def sanityCheck(self):
        """Perform simple sanity checks before uploading."""
        if None in (self.filename, self.runtime, self.source, self.format):
            raise MissingParameterException
        
        
        

