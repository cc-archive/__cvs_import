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

class VerificationRdfWizPage(xrcwiz.XrcWizPage):
    def __init__(self, parent, xrc, xrcid, headline):
       xrcwiz.XrcWizPage.__init__(self, parent, xrc, xrcid, headline)

       self.Bind(wx.EVT_BUTTON, self.saveRdf, XRCCTRL(self, "CMD_SAVE_RDF"))
    
    def onChanged(self, event):
        print 'in onpc\n',self.publisher.verificationRdf
        
        XRCCTRL(self, "TXT_RDF").SetValue(self.publisher.verificationRdf)
        
    def saveRdf(self, event):
        fileBrowser = wx.FileDialog(self, wildcard="*.txt",
                                   style= wx.SAVE)
        if fileBrowser.ShowModal() == wx.ID_OK:
           # save the RDF into the selected filename
           outfile = file(fileBrowser.GetPath(), 'w')
           outfile.write(XRCCTRL(self, "TXT_RDF").GetValue())
           outfile.close()
   
