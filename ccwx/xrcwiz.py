__id__ = "$Id$"
__version__ = "$Revision$"
__copyright__ = '(c) 2004, Creative Commons, Nathan R. Yergler'
__license__ = 'licensed under the GNU GPL2'

import wx
#import wx.wizard
import wx.xrc
from wx.xrc import XRCCTRL

import ccwx.stext
ccEVT_XRCWIZ_PAGE_CHANGING = wx.NewEventType()
ccEVT_XRCWIZ_PAGE_CHANGED  = wx.NewEventType()

EVT_XRCWIZ_PAGE_CHANGING = wx.PyEventBinder(ccEVT_XRCWIZ_PAGE_CHANGING, 1)
EVT_XRCWIZ_PAGE_CHANGED  = wx.PyEventBinder(ccEVT_XRCWIZ_PAGE_CHANGED,  1)

class XrcWizardEvent(wx.PyCommandEvent):
   def __init__(self, evt_id, win_id, direction=True, page=None):
      wx.PyCommandEvent.__init__(self, evt_id, win_id)

      self.page = page
      self.direction = direction

      self.__allowed = True

   def GetPage(self):
      return self.page

   def Veto(self):
      self.__allowed = self.__allowed and False

   def Allow(self):
      self.__allowed = self.__allowed and True

   def IsAllowed(self):
      return self.__allowed

class XrcWiz(wx.Frame):
   def __init__(self, parent, filename='', id=None):
      self.app = parent
      self.xrcid = id

      self.pages = []
      self.cur_page = -1

      # create a handle to the XML resource file
      self.xrc = wx.xrc.EmptyXmlResource()
      self.xrc.InsertHandler(ccwx.stext.StaticWrapTextXmlHandler())
      self.xrc.Load(filename)
      
      # create the frame's skeleton
      pre = wx.PreFrame()

      # load the actual definition from the XRC
      self.xrc.LoadOnFrame(pre, None, id)

      # finish creation
      self.PostCreate(pre)
      self.SetMinSize((488,425))
      self.SetAutoLayout(True)

      self.Bind(wx.EVT_BUTTON, self.onNext, XRCCTRL(self, "CMD_NEXT"))
      self.Bind(wx.EVT_BUTTON, self.onPrev, XRCCTRL(self, "CMD_PREV"))

      self.Bind(EVT_XRCWIZ_PAGE_CHANGED,  self.OnPageChanged)
      self.Bind(EVT_XRCWIZ_PAGE_CHANGING, self.OnPageChanging)

   def __detachCurrent(self, event=None):
       # detach and hide the current page
       XRCCTRL(self, "PNL_BODY").GetSizer().Hide(self.pages[self.cur_page])
       XRCCTRL(self, "PNL_BODY").GetSizer().Detach(self.pages[self.cur_page])

   def __addCurrent(self, event=None):
       # add and show the new page
       XRCCTRL(self, "PNL_BODY").GetSizer().Insert(0,
                                                   self.pages[self.cur_page],
                                                   flag=wx.EXPAND)
       self.pages[self.cur_page].Show()
       self.pages[self.cur_page].Layout()

       # update the headline
       XRCCTRL(self, "LBL_HEADER_TEXT").SetLabel(
          self.pages[self.cur_page].headline)
       
       self.__updateNavBtns(event)
       self.Layout()

   addCurrent = __addCurrent

   def __updateNavBtns(self, event=None):
       
       if (len(self.pages) == self.cur_page + 1) or \
           (self.pages[self.cur_page + 1] is None):
            XRCCTRL(self, "CMD_NEXT").SetLabel('Quit')
       else:
            XRCCTRL(self, "CMD_NEXT").SetLabel('Next')

       if self.cur_page == 0 or self.pages[self.cur_page - 1] is None:
           XRCCTRL(self, "CMD_PREV").Disable()
       else:
	       XRCCTRL(self, "CMD_PREV").Enable()

       XRCCTRL(self, "CMD_NEXT").Enable()
       
   def onCancel(self, event):
      self.Close()
      
   def getPage(self, xrcid):
      """Returns the child page with the specified XRC ID.""" 
      return [n for n in self.pages if getattr(n, 'xrcid', None) == xrcid][0]

   def setPage(self, xrcid):
      """Sets the current page to the specified XRCID."""
      
      page = self.getPage(xrcid)

      self.__detachCurrent()

      self.cur_page = self.pages.index(page)
      self.__addCurrent()

      # refresh the window
      self.GetSizer().Layout()      
      self.Refresh()

   def onNext(self, event):
       change_event = XrcWizardEvent(ccEVT_XRCWIZ_PAGE_CHANGING,
                                     self.pages[self.cur_page].GetId(), 
                                     direction=True, 
                                     page=self.pages[self.cur_page])
       self.GetEventHandler().ProcessEvent(change_event)

       if not change_event.IsAllowed():
          return False

       # check for Finish instead of next
       if (self.cur_page == len(self.pages) - 1) or \
          (self.pages[self.cur_page + 1] is None):
          # either at the end of the list of pages, or we've hit a None 
          # (which flags for stop)
          self.Close()
          return

       self.setPage(self.pages[self.cur_page + 1].xrcid)
       
       change_event = XrcWizardEvent(ccEVT_XRCWIZ_PAGE_CHANGED,
                                     self.pages[self.cur_page].GetId(), 
                                     direction=True, 
                                     page=self.pages[self.cur_page])
       self.GetEventHandler().ProcessEvent(change_event)

       XRCCTRL(self, "PNL_BODY").Layout()
       self.Layout()

   def onPrev(self, event):
       change_event = XrcWizardEvent(ccEVT_XRCWIZ_PAGE_CHANGING,
                                     self.pages[self.cur_page].GetId(), 
                                     direction=False, 
                                     page=self.pages[self.cur_page])
       self.GetEventHandler().ProcessEvent(change_event)

       if not change_event.IsAllowed():
          return False

       self.setPage(self.pages[self.cur_page - 1].xrcid)

       change_event = XrcWizardEvent(ccEVT_XRCWIZ_PAGE_CHANGED,
                                     self.pages[self.cur_page].GetId(), 
                                     direction=False, 
                                     page=self.pages[self.cur_page])
       self.GetEventHandler().ProcessEvent(change_event)

   def OnPageChanged(self, event):
       pass

   def OnPageChanging(self, event):
       if not event.GetPage().validate(event):
           event.Veto()

class XrcWizPage(wx.PyPanel):
   def __init__(self, parent, xrc, xrcid, headline='__change_me__'):
      self.xrcid = xrcid
      self.parent = parent
      
      self.headline = headline
      
      # create the frame's skeleton
      pre = wx.PrePyPanel()

      # load the actual definition from the XRC
      # check if we were passed a filename, XRC fragment or XmlResource
      if isinstance(xrc, str):
         res = wx.xrc.XmlResource(xrcfile)
      elif isinstance(xrc, wx.xrc.XmlResource):
         res = xrc
         
      res.LoadOnPanel(pre, XRCCTRL(parent, "PNL_BODY"), xrcid)

      # finish creation
      self.PostCreate(pre)
      self.SetAutoLayout(True)
      
      self.Fit()
      self.Hide()
      
      self.publisher = self.parent.publisher

   def validate(self, event):
      return True
      
   def onChanging(self, event):
       pass
       
   def onChanged(self, event):
       pass
       
  
