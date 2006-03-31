from Permissions import *
from Products.Archetypes.public import DisplayList

PROJECTNAME = "SimpleBlog"
SKINS_DIR = 'skins'

GLOBALS = globals()

DISPLAY_MODE = DisplayList((
    ('full', 'Full'), ('descriptionOnly', 'Description only'), ('titleOnly', 'Title only') ))
