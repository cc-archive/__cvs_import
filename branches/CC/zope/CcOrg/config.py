from Products.CMFCore import CMFCorePermissions
from Products.CMFCore.CMFCorePermissions import ModifyPortalContent
 
TOOL_VIEW_PERMISSION = CMFCorePermissions.ManagePortal
ADD_CONTENT_PERMISSION = ModifyPortalContent
 
PROJECTNAME = 'CcOrg'
METATYPE = 'CcTool'

SKINS_DIR = 'skins'
GLOBALS = globals()

unique_id = "cc_tool"
