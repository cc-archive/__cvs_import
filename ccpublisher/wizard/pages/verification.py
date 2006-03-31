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

class VerificationUrlWizPage(xrcwiz.XrcWizPage):
    def onChanging(self, event):
        if XRCCTRL(self, "TXT_VERIFICATION").GetValue().strip() == '' and \
            event.direction:
            wx.MessageBox("Please enter the verification URL.",
                         caption="%s: Error." % wx.GetApp.GetAppName(),
                         style=wx.OK|wx.ICON_ERROR, parent=self.parent)
            event.Veto()
        else:
            self.publisher.setVUrl(XRCCTRL(self, "TXT_VERIFICATION").GetValue())
        
    def onChanged(self, event):
        pass
        