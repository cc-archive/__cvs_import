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
import cb_ftp

import httplib
import urllib
import urllib2
import urlparse

import xml.dom.minidom
import xml.sax.saxutils
import os.path
import string
import types
import codecs

import zope.interface
import zope.component

from pyarchive.exceptions import MissingParameterException
from pyarchive.exceptions import SubmissionError
import pyarchive.utils
import pyarchive.const

import interfaces
import adapters

import p6
from p6.storage.interfaces import IInputStream

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

        if collection == pyarchive.const.OPENSOURCE_AUDIO:
            self.server = 'audio-uploads.archive.org'
        elif collection == pyarchive.const.OPENSOURCE_MOVIES:
            self.server = 'movies-uploads.archive.org'
        else:
            self.server = 'items-uploads.archive.org'
            
        self.archive_url = None

    def __setitem__(self, key, value):
        if key == 'subjects':
            subjects = [n.strip() for n in value.split(',')]
            self.metadata['subject'] = subjects
            
        else:
            self.metadata[key] = value

    def __getitem__(self, key):
        return self.metadata[key]
    
    def addFile(self, filename, source, format=None, claim=None):
        self.files.append(ArchiveFile(filename, source, format, claim))

        # set the running time to defaults
        self.files[-1].runtime = self.metadata['runtime']

        # return the added file object
        return self.files[-1]
    
    def addItem(self, item, source, format=None, claim=None):
        self.files.append(ArchiveWorkItem(item, source, format, claim))

        # set the running time to defaults
        self.files[-1].runtime = self.metadata['runtime']

        # return the added file object
        return self.files[-1]
    
    def metaxml(self, username=None):
        """Generates _meta.xml to use in submission;
        returns a file-like object."""

        meta_out = StringIO.StringIO()
        result = codecs.getwriter('UTF-8')(meta_out)

        result.write('<metadata>')

        # write the required keys
        result.write(u"""
        <title>%s</title>
        <collection>%s</collection>
        <mediatype>%s</mediatype>
        <upload_application appid="ccpublisher" version="1.0.6" />
        """ % (self.title, self.collection, self.mediatype) )

        if username is not None:
            result.write(u"<uploader>%s</uploader>\n" % username)
        
        # write any additional metadata
        for key in self.metadata:
            if self.metadata[key] is not None:
                value = self.metadata[key]

                # check if value is a list
                if type(value) in [types.ListType, types.TupleType]:
                    # this is a sequence
                    for n in value:
                        result.write(u'<%s>%s</%s>\n' % (
                                           key,
                                           xml.sax.saxutils.escape(n),
                                           key)
                                     )
                else:
                    result.write(u'<%s>%s</%s>\n' % (
                                           key,
                                           xml.sax.saxutils.escape(value),
                                           key) )

        result.write(u'</metadata>\n')

        result.seek(0)

        meta_out.seek(0)
        meta_out.seek(0)
        
        return meta_out
        
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
        

    def __ftpUrl(self, username, identifier):
        """Query archive.org for the appropriate FTP url to use.
        If successful returns a tuple containing (server, path)."""

        new_url = "/newitem.php"
        headers = {"Content-type": "application/x-www-form-urlencoded",
                   "Accept": "text/plain"}
        params = urllib.urlencode({'xml':1,
                                   'user':username,
                                   'identifier':identifier}
                                  )

        conn = httplib.HTTPConnection('www.archive.org')
        conn.request('POST', new_url, params, headers)

        try:
            resp = conn.getresponse()
        except httplib.BadStatusLine, e:
            # retry the query
            print 'retrying...'
            return self.__ftpUrl(username, identifier)

        response = resp.read() 
                    
        response_dom = xml.dom.minidom.parseString(response)
        result_type = response_dom.getElementsByTagName("result")[0].getAttribute("type")

        if result_type == "success":
            url = response_dom.getElementsByTagName("url")[0].childNodes[0].nodeValue
            pieces = urlparse.urlparse(url)
            return (pieces[1].split('@')[-1], pieces[2])
        else:
            # some error occured; throw an exception with the message
            raise Exception(response_dom.getElementsByTagName("message")[0])
        
    def submit(self, username, password, server=None):
        """Submit the files to archive.org"""

        # set the adder (if necessary)
        if self.metadata['adder'] is None:
            self.metadata['adder'] = username

        # make sure we're ready to submit
        self.sanityCheck()

        # reset the status
        zope.component.handle(
            p6.ui.events.ResetStatusEvent('', 10)
            )
        
        # get the FTP url
        zope.component.handle(
            p6.ui.events.UpdateStatusEvent(
            message='connecting to archive.org for upload information...')
            )
        
        ftp_server, ftp_path = self.__ftpUrl(username, self.identifier)

        # connect to the FTP server
        zope.component.handle(
            p6.ui.events.UpdateStatusEvent(
            message='connecting to the FTP server...')
            )

        ftp = cb_ftp.FTP(ftp_server)
        ftp.login(username, password)
        ftp.cwd(ftp_path)
        
        # upload the XML files
        zope.component.handle(
            p6.ui.events.UpdateStatusEvent(
            message='uploading metadata...')
            )

        ftp.storlines("STOR %s_meta.xml" % self.identifier,
                      self.metaxml(username))
        ftp.storlines("STOR %s_files.xml" % self.identifier,
                      self.filesxml())

        # upload each file
        zope.component.handle(
            p6.ui.events.UpdateStatusEvent(
            message='uploading files...')
            )

        for archivefile in self.files:
            # determine the local path name and switch directories
            localpath, fname = os.path.split(archivefile.archiveFilename())
            #os.chdir(localpath)

            # reset the gauge for this file
            zope.component.handle(
                p6.ui.events.ResetStatusEvent(fname, 10)
                )

            # get a handle to the input stream
            uploadFile = IInputStream(archivefile)()
            
            # perform the upload
            ftp.storbinary("STOR %s" % archivefile.archiveFilename(),
                           uploadFile) #, callback=callback)

        ftp.quit()
        
        # call the import url, check the return result
        zope.component.handle(
            p6.ui.events.ResetStatusEvent('', 3)
            )
        zope.component.handle(
            p6.ui.events.UpdateStatusEvent(
            message='finishing submission...')
            )

        importurl = "http://www.archive.org/done.php?" \
                    "xml=1&identifier=%s&user=%s" % (self.identifier, username)
        response = urllib2.urlopen(importurl)
                    
        zope.component.handle(
            p6.ui.events.UpdateStatusEvent(
            message='checking response...')
            )
        
        response_dom = xml.dom.minidom.parse(response)
        result_type = response_dom.getElementsByTagName("result")[0].getAttribute("type")

        if result_type == 'success':
           # extract the URL element and store it
           self.archive_url = "http://archive.org/details/%s" % self.identifier
        else:
           # an error occured; raise an exception
           raise SubmissionError("%s: %s" % (-1,
                                           response_dom.getElementsByTagName("message")[0].childNodes[0].nodeValue
                                ))

        zope.component.handle(
            p6.ui.events.UpdateStatusEvent(
            message='Done.')
            )
           
        return self.archive_url


class ArchiveWorkItem:
    zope.interface.implements(interfaces.IArchiveWorkItem)

    def __init__(self, item, source = None, format = None, claim = None):
        # set object properties from suppplied parameters
        self.item = item
        self.runtime = None
        self.source = source
        self.format = format
        self.__claim = claim

    def fileNode(self):
        """Generates the XML to represent this file in files.xml."""
        result = '<file name="%s" source="%s">\n' % (
            self.archiveFilename(), self.source)
        
        if self.runtime is not None:
            result = result + '<runtime>%s</runtime>\n' % self.runtime

        if self.__claim:
            result = result + '<license>%s</license>\n' % \
                     xml.sax.saxutils.escape(self.__claim)

        if not(self.format):
            self.format = ' '
        result = result + '<format>%s</format>\n</file>\n' % \
                 xml.sax.saxutils.escape(self.format)

        return result
    
    def sanityCheck(self):
        """Perform simple sanity checks before uploading."""
        
        # ensure necessary parameters have been supplied
        if None in (self.source, self.format):
            raise MissingParameterException

    def archiveFilename(self):
        fname = self.item.getIdentifier()
        
        fname = fname.replace(' ', '_')
        chars = [n for n in fname if n in
                 (string.ascii_letters + string.digits + '._')]
        
        result = "".join(chars)
        if result[0] == '.':
            # the first character is a dot,
            # indicating there's nothing before the extension.
            result = '%s%s' % (hash(result), result)

        return result
    
    
   
class ArchiveFile:
    zope.interface.implements(interfaces.IArchiveFile)
    
    def __init__(self, filename, source = None, format = None, claim = None):
        # make sure the file exists
        if not(os.path.exists(filename)):
            # can not find the file; raise an exception
            raise IOError
        
        # set object properties from suppplied parameters
        self.filename = filename
        self.runtime = None
        self.source = source
        self.format = format
        self.__claim = claim

        if self.format is None:
            self.__detectFormat()

    def __detectFormat(self):
        info = pyarchive.utils.getFileInfo(os.path.split(self.filename)[1],
                                           self.filename)

        bitrate = info[2]
        if bitrate is not None:
            if bitrate[1]:
                self.format = pyarchive.const.MP3['VBR']
            else:
                self.format = pyarchive.const.MP3[bitrate[0]]
                
    def fileNode(self):
        """Generates the XML to represent this file in files.xml."""
        result = '<file name="%s" source="%s">\n' % (
            self.archiveFilename(), self.source)
        
        if self.runtime is not None:
            result = result + '<runtime>%s</runtime>\n' % self.runtime

        # removing metadata dependency for stand-alone-ish-ness
        #if self.__claim is None:
        #    try:
        #        self.__claim = metadata(self.filename).getClaim()
        #    except NotImplementedError, e:
        #        pass
            
        if self.__claim:
            result = result + '<license>%s</license>\n' % \
                     xml.sax.saxutils.escape(self.__claim)

        print self.format
        print dir(self)
        if not(self.format):
            self.format = ' '
        result = result + '<format>%s</format>\n</file>\n' % \
                 xml.sax.saxutils.escape(self.format)

        return result
    
    def sanityCheck(self):
        """Perform simple sanity checks before uploading."""
        # make sure the file exists
        if not(os.path.exists(self.filename)):
            # can not find the file; raise an exception
            raise IOError

        # ensure necessary parameters have been supplied
        if None in (self.filename, self.source, self.format):
            raise MissingParameterException

    def archiveFilename(self):
        localpath, fname = os.path.split(self.filename)
        
        fname = fname.replace(' ', '_')
        chars = [n for n in fname if n in
                 (string.ascii_letters + string.digits + '._')]
        
        result = "".join(chars)
        if result[0] == '.':
            # the first character is a dot,
            # indicating there's nothing before the extension.
            result = '%s%s' % (hash(result), result)

        return result
    
