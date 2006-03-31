#!/usr/bin/env python
"""
cctag-wiz.py

Provides a wizard for embedding license claims in media files and
generating the corresponding verification RDF.

Requires wxPython 2.5.3 and Python 2.4.
"""

__id__ = "$Id$"
__version__ = "$Revision$"
__copyright__ = '(c) 2004, Creative Commons, Nathan R. Yergler'
__license__ = 'licensed under the GNU GPL2'

import sys
import os
import pickle
import webbrowser
import ftplib
import shelve
import socket
import platform

import wx
import wx.xrc as xrc
import ccwx.xpapp
from wx.xrc import XRCCTRL, XRCID

import wizard
import zope.interface
from wizard.gui import CcP8Wizard

import cctagutils
import cctagutils.rdf as rdf
from cctagutils.metadata import metadata

import storage
import storage.events
import publisher
import publisher.events

import pyarchive
import html
from support import attrdict

def deinstify(func):
    def foo(*args, **kwargs):
        func(*args, **kwargs)
        
    return foo
    
class CcPublisher(publisher.Publisher):
    def __init__(self, settings, storage_providers=()):
        publisher.Publisher.__init__(self, settings, storage_providers)

        self.uploadingToArchive = True
        self.verificationUrl = ''

        # register application-specific event handlers
        zope.component.provideHandler(
            zope.component.adapter(publisher.events.IUpdateItemList)(
                deinstify(self.selectFile))
            )
                       
    def close(self):
        publisher.Publisher.close(self)

    #----------------------------------------------------------------        
    # File Handling methods
    
    @zope.component.adapter(publisher.events.IUpdateItemList)
    @deinstify
    def selectFile(self, event):
        print 'in selectfile'
        
        print event
        print event.item_id

        if not(event.removed):
            # set the value of copyright holder (artist) and copyright year
            file_info = metadata(event.item_id)
    
            try:
                artist = str(file_info.getArtist())
        
                self.getField('copyright_holder').setValue(artist)
                self.getField('copyright_year').setValue(str(file_info.getYear()))
                self.getField('title').setValue(str(file_info.getTitle()))
                
            except NotImplementedError:
                # the file type is not supported by the metadata framework
                pass                 
    
    #----------------------------------------------------------------        
    # Metadata Convenience methods
    def getField(self, key):
        """Return the first metadata field with the given key from any
        storage provider."""
        for s in self.storage:
            for section in s.getSections():
                try:
                    return section.getField(key)
                except KeyError:
                    pass
                    
        raise KeyError
                    
    #----------------------------------------------------------------        
    
    def setLicense(self, license_url, license_name):
        self.license_url = license_url
        self.license_name = license_name
        
    def setVUrl(self, vurl):
        self.verificationUrl = vurl
        
    def setCopyrightHolder(self, holder, year):
        self.copyrightHolder = holder
        self.copyrightYear = year
        
    def setUserInterface(self, ui):
        self.__ui = ui
            
    def embed (self, event=None):
        # get form values
        license = self.getField('licenseurl').getValue()
        verify_url = self.verificationUrl
        year = self.getField('copyrightyear').getValue()
        holder = self.getField('copyrightholder').getValue()

        for filename in self.files:
            try:
                 metadata(filename).embed(license, verify_url, year, holder)
            except NotImplementedError, e:
                 pass

        # generate the verification RDF
        self.verificationRdf = rdf.generate(self.files, verify_url, 
                                    license, year, holder,
                                    work_meta=self.allMeta())
                                    
        #print self.verificationRdf
       
    def store(self):
        """Embed the license and upload files to archive.org"""
        for store in self.storage:
            store.clearFiles()
            
            for f in self.files:
                # TODO: Only collect metadata that applies to a file
                store.addFile(f, self.allMeta())
                
            store.store()
            
    def allMeta(self):
        """Returns a dictionary containing all metadata values provided
        by the associated storage(s)"""
        metadict = {}
        
        for store in self.storage:
            for section in store.getSections():
                for field in section.getFields():
                    metadict[field.key] = field.getValue()
                    
        return metadict
        
    def claimString(self, license, verification, year, holder):
        return "%s %s. Licensed to the public under %s verify at %s" % (
            year, holder, license, verification )
        
class CcWizApp(ccwx.xpapp.XpApp):
    zope.interface.implements(wizard.IPublisherApp)
    
    def initPublisher(self):
        """Perform initialization of the publisher object and return it."""
        return CcPublisher(self.paths, storage_providers=(storage.ia.ArchiveStorage,))
        
    def OnInitXpApp(self):
        # TODO: redirect output to newly defined ERR_LOG
        
        # initialize Publisher model
        self.publisher = self.initPublisher()
      
        print self.paths.XRC_SOURCE
        
        # create the main window and set it as the top level window
        self.main = CcP8Wizard(self, self.publisher)
        self.publisher.setUserInterface(self.main)
        
        self.main.Show(True)

        self.SetTopWindow(self.main)

        return True
        
    def getPageParent(self):
        """Returns the WX object custom UI panels should use as parent."""
        return XRCCTRL(self.main, "PNL_BODY")
        
    def getPublisher(self):
        """Returns a reference to the apps Publisher object."""
        return self.publisher
        
    def OnExit(self):
        # close down the Publisher
        self.publisher.close()
       
    def MacOpenFile(self, filename):
        # pass the filename into the main form
        if self.main:
            self.main.selectFiles([filename])
            
    def InitPaths(self):
        print 'foo'
        self.paths.CCT_VERSION = self.CCT_VERSION = cctagutils.const.version()

        self.paths.XRC_SOURCE = 'wizard.xrc'
        self.paths.PREFS_FILE = '.publisher.prefs'
        self.paths.IMAGES_DIR = 'resources'
        self.paths.ICON_FILE = 'cc.ico'
        self.paths.ERR_LOG = 'err.log'

        self.paths.LICENSE_URLS = {}
        
    def SetPlatformPaths(self):
        # set any platform-specific parameters
        ccwx.xpapp.XpApp.SetPlatformPaths(self)
        
        self.paths.XRC_SOURCE = os.path.join(self.paths.RESOURCE_DIR, self.paths.XRC_SOURCE)
        self.paths.IMAGES_DIR = self.paths.RESOURCE_DIR
        self.paths.ICON_FILE = os.path.join(self.paths.RESOURCE_DIR, self.paths.ICON_FILE)
        
        self.paths.PREFS_FILE = os.path.join(self.paths.APP_LIB_DIR, self.paths.PREFS_FILE)
        self.paths.ERR_LOG = os.path.join(self.paths.APP_LIB_DIR, self.paths.ERR_LOG)

def main(argv=[]):
   
   # create the application and execute it
   #import wxsupportwiz
   #wxsupportwiz.wxAddExceptHook('http://api.creativecommons.org/traceback.py',
   #                             cctagutils.const.version())

   app = CcWizApp('ccPublisher', filename='err.log')

   if len(argv) > 1:
       app.main.selectFiles(argv[1:])

   app.MainLoop()
   
if __name__ == '__main__':           
    main(sys.argv)

