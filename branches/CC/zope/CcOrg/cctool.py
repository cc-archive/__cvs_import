from Globals import InitializeClass
from OFS.SimpleItem import SimpleItem
from AccessControl import ClassSecurityInfo
from Products.PageTemplates.PageTemplateFile import PageTemplateFile

from Products.CMFCore.utils import UniqueObject, getToolByName
from Products.CMFCore.CMFCorePermissions import View

import config

class CcTool(UniqueObject, SimpleItem):
    """A tool which provides some simple convenience functionality
    for creativecommons.org templates and content."""
    meta_type = config.METATYPE
    id = config.unique_id

    security = ClassSecurityInfo()
    
    manage_options = (
        {'label':'blarf', 'action':'outputPage'},
        ) + SimpleItem.manage_options

    
    security.declareProtected(View, 'getContentTypes')
    def localCategories(self):
        CATEGORIES = [('Features', 'Featured artists, tools, and works'),
                      ('Publish', 'license your educational materials'),
                      ]

        return CATEGORIES
        
    outputPage = PageTemplateFile('www/info.pt', globals())
    security.declareProtected(config.TOOL_VIEW_PERMISSION, 'outputPage')

InitializeClass(CcTool)
