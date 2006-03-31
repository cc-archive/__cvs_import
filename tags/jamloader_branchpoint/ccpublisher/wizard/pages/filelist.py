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

class FilesWizPage(xrcwiz.XrcWizPage):
    def __init__(self, parent, xrc, xrcid, headline):
        xrcwiz.XrcWizPage.__init__(self, parent, xrc, xrcid, headline)

        if isinstance(xrc, str):
            res = wx.xrc.XmlResource(xrcfile)
        elif isinstance(xrc, wx.xrc.XmlResource):
            res = xrc

        # load the pop up menu and attach
        self.__ctx_menu = res.LoadMenu("MNU_FL_POPUP")

        # bind event handlers
        self.Bind(wx.EVT_LIST_ITEM_RIGHT_CLICK, self.onRightClick,
                  XRCCTRL(self, "LST_FILES"))
        self.Bind(wx.EVT_MENU, self.__menuDispatch,
                  XRCCTRL(self, "MNU_FL_DELETE"))
        self.Bind(wx.EVT_MENU, self.__menuDispatch,
                  XRCCTRL(self, "MNU_FL_BROWSE"))
        self.Bind(wx.EVT_BUTTON, self.onBrowse,
                  XRCCTRL(self, "CMD_BROWSE"))
        self.Bind(wx.EVT_BUTTON, self.onDeleteItem,
                  XRCCTRL(self, "CMD_DELETE"))
        
        self.Fit()

    def onRightClick(self, event):
        XRCCTRL(self, "LST_FILES").Select(event.GetIndex())
        self.PopupMenu(self.__ctx_menu, event.GetPosition())

    def __menuDispatch(self, event):
        if event.GetId() == XRCID("MNU_FL_BROWSE"):
            self.onBrowse(event)
        elif event.GetId() == XRCID("MNU_FL_DELETE"):
            self.onDeleteItem(event)
            
    def onDeleteItem(self, event):
        items = []

        selected = XRCCTRL(self, "LST_FILES").GetFirstSelected()

        while selected >= 0:
            items.append(XRCCTRL(self, "LST_FILES").GetItemText(selected))
            selected = XRCCTRL(self, "LST_FILES").GetNextSelected(selected)

        self.parent.deleteFiles(items)

    def onBrowse(self, event):
        # user clicked the Browse button; show a file selector
        fileBrowser = wx.FileDialog(self, wildcard="*.*",
                                    style= wx.OPEN|wx.MULTIPLE)

        # reset the default directory
        if sys.platform == 'win32':
            # Desktop
            fileBrowser.SetPath(os.environ['USERPROFILE'] +
                                os.path.sep + 'Desktop')
            
        if fileBrowser.ShowModal() == wx.ID_OK:
            # handle the newly selected file(s)
            self.parent.selectFiles(fileBrowser.GetPaths())

    def validate(self, event):
       if event.direction:
           if len(self.publisher.files) == 0:
               # user must select at least one file
               wx.MessageBox("You must select at least one file to license.",
                         caption="%s: Error." % wx.GetApp().GetAppName(),
                         style=wx.OK|wx.ICON_ERROR, parent=self)
               event.Veto()
               return False

       return True
       
    def onChanging(self, event):
        pass
       
    def onChanged(self, event):
        pass
       