
__id__ = "$Id$"
__version__ = "$Revision$"
__copyright__ = '(c) 2004-2005, Creative Commons, Nathan R. Yergler'
__license__ = 'licensed under the GNU GPL2'

import sys
import os
import pickle
import webbrowser
import ftplib
import shelve
import socket
import platform
import copy

import wx
import wx.xrc as xrc
from wx.xrc import XRCCTRL, XRCID

import ccwx.stext
import ccwx.xrcwiz
from ccwx.xrcwiz import XrcWizPage
from wizard.pages import ProgressWizPage, FileFormatWizPage, VerificationRdfWizPage
from wizard.pages import FilesWizPage, LoginWizPage, ReadyWizPage, VerificationUrlWizPage
from wizard.pages import WorkMetaWizPage, MetadataWizPage

import cctagutils
import cctagutils.rdf as rdf
from cctagutils.metadata import metadata

import pyarchive
import html

class dropFileTarget(wx.FileDropTarget):
    def __init__(self, window):
        wx.FileDropTarget.__init__(self)
        self._window = window

    def OnDropFiles(self, x, y, filenames):
        self._window.selectFiles(filenames)

class CcP8Wizard(ccwx.xrcwiz.XrcWiz):
    def __init__(self, app, publisher):
        self.app = app
        self.publisher = publisher
        ccwx.xrcwiz.XrcWiz.__init__(self, app, filename=self.app.paths.XRC_SOURCE, id='FRM_MAIN') 

        # set the application icon
        _icon = wx.Icon(self.app.paths.ICON_FILE, wx.BITMAP_TYPE_ICO)
        self.SetIcon(_icon)

        # set the header image from the appropriate location
        publish_guy = wx.Bitmap(os.path.join(self.app.paths.IMAGES_DIR, 'publishguy_small.gif'))
        XRCCTRL(self, "IMG_PUBLISHGUY").SetBitmap(publish_guy)

        self.__images = wx.ImageList(33,33)
        self.Hide()
        
        wx.CallAfter(self.__stage2init)
        
    def __stage2init(self):
        self.initPageList()
        
        #self.chainPages(start=selfhost_start)
        self.cur_page = 0
        self.addCurrent(None)

        self.pages[self.cur_page].Show()

        self.initBindings()
        
        self.SetAutoLayout(True)
        self.__platformLayout()
        self._initImages()
        self.Show()
        self.Layout()
        
    def initPageList(self):
        """Intialize self.pages with a list of wizard pages."""
        
        # load the wizard pages from XRC
        self.pages.append(XrcWizPage(self, self.xrc, 'CCTAG_WELCOME',
                                   'Welcome to ccPublisher'))
        self.pages.append(FilesWizPage(self, self.xrc, 'DROPFILES',
                                     'Select Your File'))
                                     
        # query the publisher for a list of metadata sections 
        # and create a page for each
        for s in self.publisher.storage:
            for section in s.getSections():
                # check if this section has user visible UI
                if not(section.isVisible()):
                    continue
                    
                # check if the storage provides a user interface
                if section.getInterface():
                    self.pages.append(section.getInterface())
                else:
                    # create a generic section interface
                    self.pages.append(MetadataWizPage(self, section))
                
        self.pages.append(LoginWizPage(self, self.xrc, 'ARCHIVE_LOGIN'))
        
        # create two copies for handling the fork
        self.__ia_pages = copy.copy(self.pages)
        
        self.__ia_pages.append(XrcWizPage(self, self.xrc, 'WORK_TYPE',
                        'Select Your Archive Collection'))
        self.__ia_pages.append(FileFormatWizPage(self, self.xrc, 'FILE_FORMAT'))
        self.__ia_pages.append(ReadyWizPage(self, self.xrc, 'READY',
                                   'Tag and Send Your File to the Web'))
        self.__ia_pages.append(ProgressWizPage(self, self.xrc, 'FTP_PROGRESS',
                                        'Uploading to the Internet Archive'))
        self.__ia_pages.append(None)

        # add the additional pages for self-hosting support
        self.__sh_pages = copy.copy(self.pages)
        self.__sh_pages.append(VerificationUrlWizPage(self, self.xrc, 'VERIFICATION',
                                   'Where Will Your Host Your File?'))
        self.__sh_pages.append(ReadyWizPage(self, self.xrc, 'READY',
                                   'Tag and Send Your File to the Web'))
        self.__sh_pages.append(VerificationRdfWizPage(self, self.xrc, 'VRDF',
                                   'Get Code For Your Web Page'))

        # this is the "primary" track
        self.pages = self.__ia_pages
        
    def initBindings(self):
        # connect event handlers
        self.Bind(wx.EVT_BUTTON, self.onHelp, XRCCTRL(self, "HELP_WHAT_IS_IA"))
        self.Bind(wx.EVT_BUTTON, self.onHelp, XRCCTRL(self, "HELP_NO_IA_ACCOUNT"))
        self.Bind(wx.EVT_BUTTON, self.onHelp, XRCCTRL(self, "HELP_WHAT_TYPES"))
        self.Bind(wx.EVT_BUTTON, self.onHelp, XRCCTRL(self, "HELP_EMBEDDING"))

        # XXX: why the hell are these lines needed?
        self.Bind(wx.EVT_BUTTON, self.onNext, XRCCTRL(self, "CMD_NEXT"))
        self.Bind(wx.EVT_BUTTON, self.onPrev, XRCCTRL(self, "CMD_PREV"))

        # set up drag and drop support
        self.SetDropTarget(dropFileTarget(self))

        # explicitly attach the drop target to the listview (OSX)
        XRCCTRL(self, "LST_FILES").SetDropTarget(dropFileTarget(self)) 

    def __platformLayout(self):
        self.SetSize(self.GetMinSize())

        # check the background color
        if sys.platform != 'darwin':
            html.BGCOLOR = "%X" % \
                     XRCCTRL(self, "PNL_BUTTONS").GetBackgroundColour().GetRGB()
            # reset the background color
            self.SetBackgroundColour(XRCCTRL(self, "PNL_BUTTONS").GetBackgroundColour())

    def _initImages(self):
        """Loads the image list with the necessary objects"""
        self.__images.Add(wx.Bitmap(os.path.join(self.app.paths.IMAGES_DIR, "cc_33.png")))

        XRCCTRL(self, "LST_FILES").SetImageList(self.__images,
                                               wx.IMAGE_LIST_NORMAL)
       
    def selectFiles(self, files):
        for filename in files:
            self.publisher.selectFile(filename)
            
        self.resetFileList()
        
    def deleteFiles(self, files):
        for filename in files:
            self.publisher.deleteFile(filename)
            
        self.resetFileList()

    def OnPageChanging(self, event):
        if not(event.GetPage().validate(event)):
            event.Veto()
            return

        # dispatch the event to the page
        event.GetPage().onChanging(event)
       
        # choose the correct path
        if event.GetPage().xrcid == 'ARCHIVE_LOGIN' and \
            event.direction:

            if self.publisher.uploadingToArchive:
                # reset the page sequencing
                wr_page = self.pages[self.cur_page]
                
                self.pages = self.__ia_pages
                self.cur_page = self.pages.index(wr_page)
            else:
                # reset the page sequencing
                wr_page = self.pages[self.cur_page]
                
                self.pages = self.__sh_pages
                self.cur_page = self.pages.index(wr_page)                   
       
        # additional dispatch
        # control functionality that spans multiple pages goes here
        if event.GetPage().xrcid == 'ARCHIVE_LOGIN' and \
            self.publisher.uploadingToArchive and \
            event.direction:
            # see if the user selected a work format;
            # if so, set the appropriate value on the
            # work format page and skip it.

            if self.publisher.getField('format').getValue() in ('Audio', 'Video'):
                # a collection can be determined from this value, move on
                self.__pick_collection = self.pages[self.pages.index(self.getPage('WORK_TYPE'))]
                del self.pages[self.pages.index(self.__pick_collection)]
            else:
                # see if the user went back and forth, and if so, reset the
                # next/prev pages if necessary
                if hasattr(self, '__pick_collection'):
                    self.pages.insert(self.cur_page + 1, self.__pick_collection)
                    
        #if event.GetPage().GetNext() == self.getPage('READY'):
        #    # set Ready's previous page appropriately
        #    event.GetPage().GetNext().SetPrev(event.GetPage())

    def OnPageChanged(self, event):
        # dispatch the event to the page
        event.GetPage().onChanged(event)

        if event.GetPage().xrcid == 'WORK_METADATA':
            # see if we can preset the work format
            if self.getPage('FILE_FORMAT').allFormatted() or \
                  ('ogg' in [os.path.split(n)[-1].split('.')[-1]
                             for n in self._files]):
               XRCCTRL(self, "CMB_WORK_FORMAT").SetValue('Audio')
            else:
               # assume video
               XRCCTRL(self, "CMB_WORK_FORMAT").SetValue('Video')
           
        self.Layout()
               
    def onHelp(self, event):
       if event.GetId() == XRCID('HELP_WHAT_IS_IA'):
           webbrowser.open('http://www.archive.org/about/about.php',
                           True, True)
       elif event.GetId() == XRCID('HELP_NO_IA_ACCOUNT'):
           webbrowser.open('http://www.archive.org/account/login.createaccount.php',
                           True, True)
       elif event.GetId() == XRCID('HELP_WHAT_TYPES'):
           help = html.HtmlHelp(self, 'Creative Commons Publisher',
                               html.MORE_INFO % (self.app.CCT_VERSION, 
                   os.path.join(self.app.paths.IMAGES_DIR,'publishguy_small.gif')))
           help.Show()
       elif event.GetId() == XRCID('HELP_EMBEDDING'):
           webbrowser.open('http://creativecommons.org/technology/embedding')

    def resetFileList(self):
        # reset the file view
        XRCCTRL(self, "LST_FILES").ClearAll()
        
        for fn in self.publisher.files:
           XRCCTRL(self, "LST_FILES").\
                         InsertImageStringItem(0, fn.split(os.sep)[-1], 0)
                         
    def getMetadata(self, filename=None):
       ### TODO: use storage metadata classes.
       meta = {}

       formats = {'Other':None,
                  'Audio':'Sound',
                  'Video':'MovingImage',
                  'Image':'StillImage',
                  'Text':'Text',
                  'Interactive':'InteractiveResource'
                  }
       
       controls = {'TXT_WORK_TITLE':'title',
                   'TXT_DESCRIPTION':'description',
                   'TXT_CREATOR':'creator',
                   'TXT_SOURCE_URL':'source',
                   'TXT_KEYWORDS':'subjects'
                   }

       # get the text control values
       for c in controls:
           if XRCCTRL(self, c).GetValue():
               meta[controls[c]] = XRCCTRL(self, c).GetValue()

       # get the work format
       meta['format'] = formats[XRCCTRL(self, "CMB_WORK_FORMAT").GetValue()]
       
       if filename is None:
           return meta

       try:
            if 'creator' not in meta:
                meta['creator'] = metadata(filename).getArtist()

            if 'title' not in meta:
                meta['title'] = metadata(filename).getTitle()
       except NotImplementedError, e:
            pass
       
       return meta
               
                         