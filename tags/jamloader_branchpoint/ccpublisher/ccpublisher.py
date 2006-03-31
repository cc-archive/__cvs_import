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
from wx.xrc import XRCCTRL, XRCID

import wizard
import zope.interface
from wizard.gui import CcP8Wizard

import cctagutils
import cctagutils.rdf as rdf
from cctagutils.metadata import metadata

import storage

import pyarchive
import html

class Publisher:
    def __init__(self, settings, storage=()):
        self.settings = settings
        
        self.files = []
        self.uploadingToArchive = True
        self.verificationUrl = ''
                       
        self.storage = [n(self) for n in storage]

        # read preferences, if any
        self.__readPrefs()
        
    def close(self):
        # write out preferences
        self.__writePrefs()
        
    def __readPrefs(self):
        self.prefs = shelve.open(self.settings.PREFS_FILE)
       
    def __writePrefs(self):
        self.prefs.close()
        
    #----------------------------------------------------------------        
    # File Handling methods
    
    def deleteFiles(self, shortnames):
        items = [n for n in self.files if n.split(os.sep)[-1] in shortnames]

        for item in items:
             del self.files[self.files.index(item)]
             
    def selectFile(self, filename):
        if filename not in self.files:
            self.files.append(filename)
        else:
            return
        
        # set the value of copyright holder (artist) and copyright year
        file_info = metadata(filename)

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
        
class CcWizApp(wx.App):
    zope.interface.implements(wizard.IPublisherApp)
    
    def initPublisher(self):
        """Perform initialization of the publisher object and return it."""
        return Publisher(self.paths, storage=(storage.ia.ArchiveStorage,))
        
    def OnInit(self):
        wx.InitAllImageHandlers()
        self.InitPaths()
        self.SetPlatformPaths()
        
        # TODO: redirect output to newly defined ERR_LOG
        
        # initialize Publisher model
        self.publisher = self.initPublisher()
      
        # take care of any custom settings here
        self.SetAppName('ccPublisher')

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
        self.paths = attrdict()
        self.paths.CCT_VERSION = self.CCT_VERSION = cctagutils.const.version()

        self.paths.XRC_SOURCE = 'wizard.xrc'
        self.paths.PREFS_FILE = '.publisher.prefs'
        self.paths.IMAGES_DIR = 'resources'
        self.paths.ICON_FILE = 'cc.ico'
        self.paths.ERR_LOG = 'err.log'

        self.paths.LICENSE_URLS = {}
        
    def SetPlatformPaths(self):
        # set any platform-specific parameters
        if platform.system().lower() == 'darwin':
            # set the file path to the XRC resource file
            # to handle the app bundle properly
            self.paths.XRC_SOURCE = os.path.join(os.path.dirname(sys.argv[0]), self.paths.XRC_SOURCE)
            self.paths.IMAGES_DIR = os.path.dirname(sys.argv[0])
    
            # store the preferences and error log in ~/Library/Application Support/ccPublisher
            app_lib_dir = os.path.expanduser('~/Library/Application Support/ccPublisher')
            if not(os.path.exists(app_lib_dir)):
                   os.makedirs(app_lib_dir)
            self.paths.PREFS_FILE = os.path.join(app_lib_dir, self.paths.PREFS_FILE)
            self.paths.ERR_LOG = os.path.join(app_lib_dir, self.paths.ERR_LOG)
            
        elif platform.system().lower() == 'windows':
            self.paths.IMAGES_DIR = os.path.join(os.path.dirname(sys.argv[0]), self.paths.IMAGES_DIR)
    
            self.paths.XRC_SOURCE = os.path.join(os.path.dirname(sys.argv[0]),
                                      'resources', self.paths.XRC_SOURCE)
            self.paths.ICON_FILE = os.path.join(os.path.dirname(sys.argv[0]),
                                      'resources', self.paths.ICON_FILE)
            self.paths.PREFS_FILE = os.path.join(os.path.dirname(sys.argv[0]),
                                      self.paths.PREFS_FILE)
        elif platform.system().lower() == 'linux':
            # check if the resources directory exists;
            # if not check for /usr/share/ccpublisher
            NIX_RSC_DIR = os.path.join('usr','share','resources','ccpublisher')
                    
            if not(os.path.exists(self.paths.IMAGES_DIR) and os.path.isdir(self.paths.IMAGES_DIR)):
                if os.path.exists(NIX_RSC_DIR):
                    self.paths.IMAGES_DIR = NIX_RSC_DIR
                    self.paths.XRC_SOURCE = os.path.join(NIX_RSC_DIR, self.paths.XRC_SOURCE)
    
            # reset the folder paths for log file and prefs file
            NIX_PREF_DIR = os.path.join(os.path.expanduser('~'), '.ccpublisher')
            if not(os.path.exists(NIX_PREF_DIR)):
                os.makedirs(NIX_PREF_DIR)
    
            self.paths.PREFS_FILE = os.path.join(NIX_PREF_DIR, self.paths.PREFS_FILE)
            self.paths.ERR_LOG = os.path.join(NIX_PREF_DIR, self.paths.ERR_LOG)  
                  
def main(argv=[]):
   
   # create the application and execute it
   #import wxsupportwiz
   #wxsupportwiz.wxAddExceptHook('http://api.creativecommons.org/traceback.py',
   #                             cctagutils.const.version())

   app = CcWizApp(filename='err.log')

   if len(argv) > 1:
       app.main.selectFiles(argv[1:])

   app.MainLoop()
   
if __name__ == '__main__':           
    main(sys.argv)

