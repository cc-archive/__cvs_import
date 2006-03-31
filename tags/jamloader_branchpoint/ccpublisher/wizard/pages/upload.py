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
import ftplib
import socket
import os
import sys

import pyarchive.const
import pyarchive.utils

from wx.xrc import XRCCTRL, XRCID

import ccwx.xrcwiz as xrcwiz
import ccwx.stext as stext
import html


class ProgressCallback:
    def __init__(self, progress_page):
        self.pp = progress_page
        self.__delta = 1
            
    def reset(self, filename=None, steps=None):
        self.__filename = filename
        self.__steps = steps
        self.__bytes = 0
        
        if self.__filename is not None:
            # find the size of the file and set the total number of bytes
            self.__size = os.path.getsize(filename)
            XRCCTRL(self.pp, "WXG_PROGRESS").SetRange(self.__size)
        else:
            self.__size = 1
            XRCCTRL(self.pp, "WXG_PROGRESS").SetRange(steps)

        XRCCTRL(self.pp, "WXG_PROGRESS").SetValue(0)
        
    def increment(self, bytes=None, status=None):
        if bytes is None:
            bytes = self.__delta
        else:
            self.__bytes += bytes
            
        XRCCTRL(self.pp, "WXG_PROGRESS").SetValue(
            XRCCTRL(self.pp, "WXG_PROGRESS").GetValue() + bytes)
        wx.Yield()

        if status is not None:
            self.status(status)
        else:
            self.status('uploading %s (%s %%)...' % (self.__filename,
                             (self.__bytes * 100 / self.__size) )
                        )

    __call__ = increment

    def finish(self):
        self.status('done.')
        XRCCTRL(self.pp, "WXG_PROGRESS").SetValue(
            XRCCTRL(self.pp, "WXG_PROGRESS").GetRange()
            )
        
    def status(self, status):
        XRCCTRL(self.pp, "LBL_CURRENTLY").SetLabel(status)
        wx.Yield()
    
class ProgressWizPage(xrcwiz.XrcWizPage):
    HTML_FINISHED = """<html><body bgcolor="#%s"><p>Your file has been sent
    to the Internet Archive for free hosting.  After it's approved by the
    Archive curator (usually within 24 hours), you will be able to download
    your file from the URL:<br>
    <a href="%s">%s</a>.</p>
    <p>Your file is also ready to be file-shared; just drop it in your
    shared folder.</p>
    </body></html>"""
    
    def __init__(self, parent, xrc, xrcid, headline):
        xrcwiz.XrcWizPage.__init__(self, parent, xrc, xrcid, headline)

        self.callback = ProgressCallback(self)

        self.html = html.WebbrowserHtml(self, -1)
        self.setUrl('foo', False)

        ir = self.html.GetInternalRepresentation()
        self.html.SetSize( (ir.GetWidth()+25, ir.GetHeight()+25) )

        self.GetSizer().Add(self.html, (4,0), (1,1), flag=wx.EXPAND)
        
        self.Layout()

    def setUrl(self, url, show=True):
        self.html.SetPage(self.HTML_FINISHED % (html.BGCOLOR, url, "<br>".join(url.split('?')) ) )
        self.html.Show(show)

    def onChanging(self, event):
        pass
       
    def onChanged(self, event):

        # store the current cursor and set to "wait"
        __cur_cursor = self.GetCursor()
        self.SetCursor(wx.StockCursor(wx.CURSOR_WAIT))
           
        # disable the finish button
        XRCCTRL(self.parent, "CMD_NEXT").Disable()

        # upload to the archive
        try:
            url = self.publisher.store(event)

            # display final information
            event.GetPage().setUrl(url)
        except ftplib.error_perm, e:
            # invalid username and password
            wx.MessageBox("Error logging into the Internet Archive;\n"
                             "invalid username or password.",
                         caption="publisher: Error.",
                         style=wx.OK|wx.ICON_ERROR, parent=self)
            self.setPage('ARCHIVE_LOGIN')
        except pyarchive.exceptions.SubmissionError, e:
            wx.MessageBox("An error occurred while submitting your file;\n"
                             "%s" % (e.args),
                             caption="publisher: Error.",
                             style=wx.OK|wx.ICON_ERROR, parent=self)

            return
        except socket.error, e:
            retry = \
                  wx.MessageBox("An error occurred while uploading your file. "
                             "The connection may have timed out or "
                             "disconnected.\n"
                             "Would you like to retry?",
                             caption="publisher: Error.",
                             style=wx.YES_NO|wx.ICON_ERROR, parent=self)
            if retry == wx.ID_YES:
                   self.OnChanged(event)

            return
            
        # reset the cursor
        self.SetCursor(__cur_cursor)
           
        XRCCTRL(self.parent, "CMD_NEXT").Enable()

            