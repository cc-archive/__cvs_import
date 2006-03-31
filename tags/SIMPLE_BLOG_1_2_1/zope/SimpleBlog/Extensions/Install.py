from Products.Archetypes.public import listTypes
from Products.Archetypes.Extensions.utils import installTypes, install_subskin
from Products.CMFCore.utils import getToolByName
from StringIO import StringIO
from Products.CMFCore.CMFCorePermissions import setDefaultRoles

from Products.SimpleBlog.config import *

portlets=['here/portlet_simpleblog/macros/portletBlogFull_local', 
          'here/portlet_simpleblog/macros/portletBlogFull_global',
          'here/portlet_simpleblog/macros/portletBlogRecent_local',
          'here/portlet_simpleblog/macros/portletBlogRecent_global']




def install(self):

    out = StringIO()

    installTypes(self, out,
                 listTypes(PROJECTNAME),
                 PROJECTNAME)

    install_subskin(self, out, GLOBALS)

    portal = getToolByName(self,'portal_url').getPortalObject()

    if not hasattr(self, 'simpleblog_tool'):
        addTool = portal.manage_addProduct['SimpleBlog'].manage_addTool
        addTool(type='SimpleBlog manager')    
    
    #register the folderish items in portal_properties/site_properties for folder-contents views etc
    site_props = getToolByName(self, 'portal_properties').site_properties
    use_folder_tabs = site_props.getProperty('use_folder_tabs')
    if not 'Blog' in use_folder_tabs:
        site_props._updateProperty('use_folder_tabs', tuple(use_folder_tabs) + ('Blog','BlogFolder','BlogEntry'))
    
    # check for property use_folder_contents. This property seems not to be always there:
    if not site_props.hasProperty('use_folder_contents'):
        site_props.manage_addProperty('use_folder_contents', '', 'lines')
        
    use_folder_contents = site_props.getProperty('use_folder_contents')
    if not 'Blog' in use_folder_contents:
        site_props._updateProperty('use_folder_contents', tuple(use_folder_contents) + ('Blog','BlogFolder', 'BlogEntry'))
        
    # discussion enabled by default for BlogEntries
    getToolByName(self, 'portal_types').BlogEntry.allow_discussion=1

    # register 2.0 control panel
    try:
        cp=self.portal_controlpanel
        cp.addAction(id='SimpleBlogSetup',
                     name='SimpleBlog Setup',
                     action='string:${portal_url}/prefs_simpleblog_form',
                     permission='Manage portal',
                     category='Products',
                     appId='SimpleBlog',
                     imageUrl='SimpleBlog/simpleblog_icon.gif',
                     description='Configure SimpleBlog global settings')
    except:
        pass

    from Products.SimpleBlog import SimpleBlogWorkflow
    reload(SimpleBlogWorkflow)

    # Remove workflows for BlogEntry and BlogFolder
    wf_tool = getToolByName(self, 'portal_workflow')
    wf_tool.setChainForPortalTypes( ('Blog', 'BlogEntry','BlogFolder'), []) 
    
    add_workflow(self, 'simpleblog_workflow', 'simpleblog_workflow (Workflow for Blog Entries)', ('BlogEntry',), out)
    wf_tool.setChainForPortalTypes(('Blog','BlogFolder'), 'folder_workflow')        
        
    print >> out, "Successfully installed %s." % PROJECTNAME
    return out.getvalue()

def add_workflow(self, wf_id, wf_factory_id, types, out):
    """ add a workflow definition to the portal_workflow tool """
    wf_tool = self.portal_workflow
    try:
        wf_tool.manage_delObjects([wf_id])
        out.write('Removed existant workflow "%s"\n'%wf_id)
    except:
        pass
    wf_tool.manage_addWorkflow(wf_factory_id, wf_id)
    if types:
        wf_tool.setChainForPortalTypes(types, wf_id)
    out.write('Added workflow "%s"\n'%wf_id)


def uninstall(self):
    # remove the references in each folder to our portlet:
    
    # this gotta be effective some time
    # someone's gotta test if this works like it should
    # commented out for the time being:
    
    #portal = getToolByName(self,'portal_url').getPortalObject()
    #processPortletOld(portal)
    #processPortletNew(portal)
    #removeReferences(portal)
    
    #remove configlet
    try:
        self.portal_controlpanel.unregisterApplication('SimpleBlog')
    except:
        pass
                     
def removeReferences(obj):
    for o in obj.contentValues():
        if o.isPrincipiaFolderish:
            processPortletOld(o)
            processPortletNew(o)
            removeReferences(o)

def processPortletOld(o):
    if not o.portal_type in ['Blog', 'BlogFolder', 'BlogEntry']:
        if hasattr(o.aq_base, 'left_slots') or hasattr(o.aq_base, 'right_slots'):
            new=[]
            if hasattr(o.aq_base,'left_slots'):
                for l in o.aq_base.left_slots:
                    if not l in portlets:
                        new.append(l)
                o.left_slots=new
            new=[]
            if hasattr(o.aq_base, 'right_slots'):
                for l in o.aq_base.right_slots:
                    if not l in portlets:
                        new.append(l)
                o.right_slots=new

def processPortletNew(o):
    if not o.portal_type in ['Blog', 'BlogFolder', 'BlogEntry']:    
        if hasattr(o.aq_base, 'column_one_portlets') or hasattr(o.aq_base, 'column_two_portlets'):
            new=[]
            if hasattr(o.aq_base,'column_one_portlets'):
                for l in o.aq_base.column_one_portlets:
                    if not l in portlets:
                        new.append(l)
                o.column_one_portlets=new
            new=[]
            if hasattr(o.aq_base, 'column_two_portlets'):
                for l in o.aq_base.column_two_portlets:
                    if not l in portlets:
                        new.append(l)
                o.column_two_portlets=new
        
            