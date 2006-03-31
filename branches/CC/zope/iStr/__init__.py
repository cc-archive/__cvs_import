from Products.Archetypes.public import process_types, listTypes
from Products.CMFCore import utils
from Products.iStr.config import PROJECTNAME, \
     ADD_CONTENT_PERMISSION, SKINS_DIR, GLOBALS
from Products.CMFCore.DirectoryView import registerDirectory

registerDirectory(SKINS_DIR, GLOBALS)

def initialize(context):
    ##Import Types here to register them
    import Hello
    content_types, constructors, ftis = process_types(
        listTypes(PROJECTNAME), PROJECTNAME)
    utils.ContentInit(
     PROJECTNAME + ' Content',
     content_types      = content_types,
     permission         = ADD_CONTENT_PERMISSION,
     extra_constructors = constructors,
     fti                = ftis,
     ).initialize(context)
