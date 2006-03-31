from Globals import package_home
from Products.Archetypes.public import process_types, listTypes
from Products.CMFCore import utils
from Products.CMFCore.DirectoryView import registerDirectory
import os, os.path

from config import SKINS_DIR, GLOBALS, PROJECTNAME
from config import ADD_BLOGENTRY_PERMISSION,ADD_SIMPLEBLOG_PERMISSION

from Globals import InitializeClass


registerDirectory(SKINS_DIR, GLOBALS)


def initialize(context):
    ##Import Types here to register them
    import BlogEntry
    import Blog
    import BlogFolder

    content_types, constructors, ftis = process_types(
        listTypes(PROJECTNAME),
        PROJECTNAME)
    

    blogIndex = getTypeIndex(content_types, 'Blog')
    entryIndex=getTypeIndex(content_types, 'BlogEntry')
    folderIndex = getTypeIndex(content_types, 'BlogFolder')    
    
    blog_contstructor = []
    blog_contstructor.append(constructors[blogIndex])
    entry_contstructor = []
    entry_contstructor.append(constructors[entryIndex])
    entry_contstructor.append(constructors[folderIndex])

    blog_type = []
    blog_type.append(content_types[blogIndex])
    entry_type = []
    entry_type.append(content_types[entryIndex])
    entry_type.append(content_types[folderIndex])
    
    
    utils.ContentInit(
        PROJECTNAME + ' Content',
        content_types      = tuple(blog_type),
        permission         = ADD_SIMPLEBLOG_PERMISSION,
        extra_constructors = tuple(blog_contstructor),
        fti                = ftis,
        ).initialize(context)
    
    utils.ContentInit(
        PROJECTNAME + ' Content',
        content_types      = tuple(entry_type),
        permission         = ADD_BLOGENTRY_PERMISSION,
        extra_constructors = tuple(entry_contstructor),
        fti                = ftis,
        ).initialize(context)
    
        
    from SimpleBlogTool import SimpleBlogManager
    utils.ToolInit(
        'SimpleBlog manager', 
        tools=(SimpleBlogTool.SimpleBlogManager,),  
        product_name='SimpleBlog', 
        icon='tool.gif', ).initialize(context)
    
def getTypeIndex(content_types, meta_type):
    for i in range(len(content_types)):
        if content_types[i].meta_type==meta_type:
            return i
            
    
    
    
    
    