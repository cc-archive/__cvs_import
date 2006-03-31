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

class LoginWizPage(xrcwiz.XrcWizPage):
    def __init__(self, parent, xrc, xrcid):
        xrcwiz.XrcWizPage.__init__(self, parent, xrc, xrcid,
                                   "Login to the Archive")

        prefs = self.publisher.prefs
        if prefs.has_key('USERNAME'):
            XRCCTRL(self, "TXT_USERNAME").SetValue(prefs['USERNAME'])

        if prefs.has_key('PASSWORD'):
            XRCCTRL(self, "TXT_PASSWORD").SetValue(prefs['PASSWORD'])

        # bind events
        self.Bind(wx.EVT_BUTTON, self.onSelfHost,
                XRCCTRL(self, "CMD_HOST_MYSELF"))
                
    def onSelfHost(self, event):
       """User chose to self-host; reset the next page and
       fire a "next" event to show verification url entry.
       """

       self.publisher.uploadingToArchive = False
       self.parent.onNext(None)
            
    def validate(self, event):
        if XRCCTRL(self, "CHK_SAVEPASSWD").GetValue():
            # save the username and password
            self.publisher.prefs['USERNAME'] = XRCCTRL(self, "TXT_USERNAME").GetValue()
            self.publisher.prefs['PASSWORD'] = XRCCTRL(self, "TXT_PASSWORD").GetValue()
        else:
            self.publisher.prefs['USERNAME'] = ""
            self.publisher.prefs['PASSWORD'] = ""
            
            
        # store the values into the metadata framework
        self.publisher.getField('iausername').setValue(XRCCTRL(self, "TXT_USERNAME"))
        self.publisher.getField('iapasswd').setValue(XRCCTRL(self, "TXT_PASSWORD"))
        
        return True

    def onChanging(self, event):
        # make sure the user entered a username and password
        if self.publisher.uploadingToArchive and event.direction:
           if XRCCTRL(self, "TXT_USERNAME").GetValue().strip() == '' or \
              XRCCTRL(self, "TXT_PASSWORD").GetValue().strip() == '':
               wx.MessageBox("Please enter your archive.org username and password.",
                         caption="ccPublisher: Error.",
                         style=wx.OK|wx.ICON_ERROR, parent=self)
               event.Veto()
       
    def onChanged(self, event):
        # always default to uploading to Archive
        self.publisher.uploadingToArchive = True
        pass
    