
import sys
import wx
import wx.xrc as xrc
from wx.xrc import XRCCTRL, XRCID

XRC_SOURCE = 'cctag.xrc'

class frameMain(wx.Frame):
    
    def __init__(self, parent):
        # create the frame's skeleton
        pre = wx.PreFrame()

        # load the actual definition from the XRC
        res = xrc.XmlResource(XRC_SOURCE)
        res.LoadOnFrame(pre, parent, "MAINFRAME")

        # finish creation
        self.PostCreate(pre)

        # set up the menu bar and status bar
        menubar = res.LoadMenuBar("MAINMENU")
        self.SetMenuBar(menubar)

        # bind events to methods
        self._initEvents()
        
        # initialize internals
        self._files = []

    def _initEvents(self):
        """Initialize event handling."""
        
        self.Bind(wx.EVT_BUTTON, self.onBrowseClick,
                  XRCCTRL(self, "CMD_BROWSE"))

        # hook up the menu dispatch
        self.Bind(wx.EVT_MENU, self.onMenuClick,
                  XRCCTRL(self, "MNU_FILE_QUIT"))
        
    def onBrowseClick(self, event):
        # user clicked the Browse button; show a file selector
        fileBrowser = wx.FileDialog(self, wildcard="*.*", style= wx.OPEN|wx.MULTIPLE)
        if fileBrowser.ShowModal() == wx.ID_OK:
            self._files = fileBrowser.GetPaths()
            XRCCTRL(self, "TXT_FILENAME").SetValue(", ".join(self._files))

    def onMenuClick(self, event):
        # dispatch menu events to the proper handler

        id = event.GetId()

        if id == XRCID("MNU_FILE_QUIT"):
            # destroy the top level window (self) to trigger quitting the app
            self.Destroy()
        else:
            print event.GetId()
        
class CCTagApp(wx.App):
    def OnInit(self):
        # perform wx intialization
        wx.InitAllImageHandlers()

        # take care of any custom settings here
        self.SetAppName('ccTag')
        
        # create the main window and set it as the top level window
        self.main = frameMain(None)
        self.main.Show()
        self.SetTopWindow(self.main)
        
        return True

    def MacOpenFile(self, filename):
        """Responds to file drops in Mac OS X"""
        
def main(argv):
    # create the application and execute it
    app = CCTagApp(0)
    app.MainLoop()

if __name__ == '__main__':
    main(sys.argv)
    
