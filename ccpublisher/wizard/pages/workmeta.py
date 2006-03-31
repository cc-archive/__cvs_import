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

class WorkMetaWizPage(xrcwiz.XrcWizPage):
    def __init__(self, parent, xrc, xrcid, headline):
       xrcwiz.XrcWizPage.__init__(self, parent, xrc, xrcid, headline)
       
       self.Bind(wx.EVT_BUTTON, self.onShowAdvWork, XRCCTRL(self, "CMD_ADV_WORKMETA"))       
    
    def onShowAdvWork(self, event):
        XRCCTRL(self, "PNL_ADVANCED").Show(not(XRCCTRL(self, "PNL_ADVANCED").IsShown()))
        
    def onChanging(self, event):
        # store the copyright holder
        self.publisher.setCopyrightHolder(XRCCTRL(self, "TXT_HOLDER"), XRCCTRL(self, "TXT_YEAR"))
        
        ### TODO: store the work metadata

    def onChanged(self, event):
        # check if the holder is pre-populated and select the first item if it is.
        if XRCCTRL(self, "TXT_HOLDER").GetCount() > 0:
            XRCCTRL(self, "TXT_HOLDER").SetSelection(0)

        
