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

class ReadyWizPage(xrcwiz.XrcWizPage):
    def onChanging(self, event):
        if not(self.publisher.uploadingToArchive) and event.direction:
           __cur_cursor = self.GetCursor()
           wx.YieldIfNeeded()
           self.SetCursor(wx.StockCursor(wx.CURSOR_WAIT))
           
           self.publisher.embed(event)           
           #wx.YieldIfNeeded()

           self.SetCursor(__cur_cursor)
        
    def onChanged(self, event):
        # set up the summary window
        XRCCTRL(self, "LST_READY_FILES").Clear()
        XRCCTRL(self, "LST_READY_FILES").AppendItems(self.publisher.files)

        ready_msg = "Publisher has collected all the information " \
           "neccessary to license your files with the %s license." % (
               self.publisher.license_name)
           
        if self.publisher.uploadingToArchive:
            ready_msg = "%s\n%s" % (ready_msg, 
                   "Your files will be uploaded to the Internet Archive."
                   )
            XRCCTRL(self, "CHK_ADV_ARCHIVE").Show()
        else:
            ready_msg = "%s\n%s" % (ready_msg, 
                   "Publisher will generate HTML code which you should "
                   "copy into the code of your web page."
                   )
            XRCCTRL(self, "CHK_ADV_ARCHIVE").Hide()

            XRCCTRL(self, "LBL_READY_MSG").SetLabel(ready_msg)

    