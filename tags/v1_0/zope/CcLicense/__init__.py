from Globals import package_home
from Products.Archetypes.public import process_types, listTypes
from Products.CMFCore import utils
from Products.CMFCore.DirectoryView import registerDirectory
import os, os.path

from config import SKINS_DIR, GLOBALS, PROJECTNAME

from Globals import InitializeClass

registerDirectory(SKINS_DIR, GLOBALS)

def initialize(context):
    pass
    
