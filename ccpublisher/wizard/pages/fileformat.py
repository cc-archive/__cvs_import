__id__ = "$Id$"
__version__ = "$Revision$"
__copyright__ = '(c) 2004, Creative Commons, Nathan R. Yergler'
__license__ = 'licensed under the GNU GPL2'

import wx
import wx.wizard
import wx.grid
import wx.lib.dialogs

import urllib2
import libxml2
import libxslt
import os
import sys

import pyarchive.const
import pyarchive.utils

from wx.xrc import XRCCTRL, XRCID

import ccwx.xrcwiz as xrcwiz
import ccwx.stext as stext
import html

class FileFormatWizPage(xrcwiz.XrcWizPage):
    def __init__(self, parent, xrc, xrcid):
        xrcwiz.XrcWizPage.__init__(self, parent, xrc, xrcid,
                                   'Add Format Information')

        self.files = {}
        self.formats = {}
        self.filenames = []

        self.panel = XRCCTRL(self, "PNL_FILELIST")

    def __detachAll(self):
        for filename in self.files.keys():
            self.panel.GetSizer().Detach(self.files[filename][0])
            self.panel.GetSizer().Detach(self.files[filename][1])

        self.files = {}
        self.filenames = []
        
    def setFiles(self, files):
        self.__detachAll()

        self.filenames = files

        for fn in self.filenames:
            # add the label
            label = wx.StaticText(self.panel,
                                  label = os.path.split(fn)[1])
            self.panel.GetSizer().Add(label, flag=wx.EXPAND)

            # add the combo box
            cmb = wx.ComboBox(self.panel,
                         style=wx.CB_DROPDOWN|wx.CB_READONLY,
                         choices = pyarchive.const.VALID_FORMATS
                         )
            cmb._filename = fn
            self.panel.GetSizer().Add(cmb, flag=wx.EXPAND)

            self.files[fn] = (label, cmb)
            self.formats[fn] = None
            
            # bind to the change event
            self.Bind(wx.EVT_COMBOBOX, self.onChooseFormat, cmb)

        # try to detect file formats
        self.__detect()
        
        self.Layout()

    def __detect(self):
        # attempt to detect the file type for each file
        for fn in self.filenames:
            # see if the user has already set a format for this file
            if self.formats[fn] is not None:
                continue
            
            # attempt to detect the fileformat
            fileinfo = pyarchive.utils.getFileInfo(os.path.split(fn)[1], fn)

            if fileinfo[2]:
                if fileinfo[2][1]:
                    # non-vbr
                    try:
                        self.formats[fn] = pyarchive.const.MP3[fileinfo[2][0]]
                    except KeyError, e:
                        # not a known bit rate; probably VBR
                        self.formats[fn] = pyarchive.const.MP3_VBR
                        
                else:
                    self.formats[fn] = pyarchive.const.MP3_VBR

                # set the combo box value
                self.files[fn][1].SetValue(self.formats[fn])
    
    def onChooseFormat(self, event):
        # find the appropriate file
        self.formats[self.FindWindowById(event.GetId())._filename] = self.FindWindowById(event.GetId()).GetValue()

    def allFormatted(self):
        """Return True if all files have a format assigned."""
        for k in self.formats:
            if self.formats[k] is None:
                return False

        return True

    def getFormat(self, filename):
        return self.formats[filename]
        
    def onChanging(self, event):
        if event.direction:
            if not (event.GetPage().allFormatted()):
                wx.MessageBox("Please select a format for each file.", caption="publisher: Error.", 
                              style=wx.OK|wx.ICON_ERROR, parent=self)
                event.Veto()

    def onChanged(self, event):
        # see if we know the format for all files
        if self.allFormatted():
            if event.direction:
                self.parent.onNext(None)
            else:
                self.parent.onPrev(None)
           
