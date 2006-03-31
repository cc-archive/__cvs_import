from Products.Archetypes.public import process_types, listTypes
from Products.CMFCore import utils
from Products.CcOrg.config import *
from Products.CMFCore.DirectoryView import registerDirectory
from Products.CMFCore import utils

registerDirectory(SKINS_DIR, GLOBALS)

def initialize(context):
    ##Import Types here to register them
    import TwoColDocument
    
    content_types, constructors, ftis = process_types(
        listTypes(PROJECTNAME), PROJECTNAME)
    utils.ContentInit(
     PROJECTNAME + ' Content',
     content_types      = content_types,
     permission         = ADD_CONTENT_PERMISSION,
     extra_constructors = constructors,
     fti                = ftis,
     ).initialize(context)

    # initialize the tool(s)

    import cctool
    tools = (cctool.CcTool,)
    init = utils.ToolInit( METATYPE,
                    tools = tools,
                    product_name = PROJECTNAME,
                    icon='tool.gif'
                    ).initialize(context)
