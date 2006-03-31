__id__ = "$Id$"
__version__ = "$Revision$"
__copyright__ = '(c) 2004, Creative Commons, Nathan R. Yergler'
__license__ = 'licensed under the GNU GPL2'

import wx

import os
import sys

import pyarchive.const
import pyarchive.utils

from wx.xrc import XRCCTRL, XRCID

import ccwx.xrcwiz as xrcwiz
import ccwx.stext as stext

from wizard.pages.interface import IWizardPage
import storage.interface
import zope.interface
import zope.component

import metadata.events
import metadata.interfaces

class MetadataWizPage(wx.PyPanel):
    """Implements a wizard page which generates a set 
    of fields for a metadata section."""
    zope.interface.implements(IWizardPage)
    
    def __init__(self, parent, section):
        wx.PyPanel.__init__(self, XRCCTRL(parent, "PNL_BODY"))
        self.publisher = parent.publisher
        self.section = section
        
        self.xrcid = self.section.name
        
        # initialize tracking attributes
        self.__fields = {}
        
        self.headline = self.section.label
                
        # create the sizer
        self.sizer = wx.FlexGridSizer(cols=2,hgap=5)
        self.sizer.AddGrowableCol(1)
        
        self.SetSizer(self.sizer)

        self.__initFields()
        self.Layout()
        self.Hide()

    def __initFields(self):
        """Create the user interface for this section."""
        for field in self.section.getFields():
            self.__fields[field.key] = {}
            
            self.__fields[field.key]['label'] = wx.StaticText(self, label=field.label)
            self.sizer.Add(self.__fields[field.key]['label'])
            
            # determine the type of widget to create
            if field.elementType == metadata.interfaces.COMBO:
                print field.choices
                self.__fields[field.key]['widget'] = wx.ComboBox(self, value=field.default,
                    choices = field.choices)
            elif field.elementType == metadata.interfaces.TEXT:
                self.__fields[field.key]['widget'] = wx.TextCtrl(self, value=field.default)
                
            else:
                # fall back to plain text entry
                self.__fields[field.key]['widget'] = wx.TextCtrl(self, value=field.default)
                
            self.sizer.Add(self.__fields[field.key]['widget'], flag = wx.EXPAND)

    def GetCBValue(self, radioSizer):
        for item in radioSizer.GetChildren():
            if item.GetWindow().GetValue():
                return item.GetWindow().GetLabel()
            
        return None
    
    def validate(self, event):
        return True

    def onChanging(self, event):
        # store the new values back to the metadata framework
        # TODO Replace this handler with event dispatch
        for field in self.section.getFields():
            zope.component.handle(metadata.events.FieldChanged( field.key, 
                self.__fields[field.key]['widget'].GetValue() )
                )
            
    def onChanged(self, event):
        pass

