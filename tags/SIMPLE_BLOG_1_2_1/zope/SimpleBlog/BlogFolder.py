from Products.Archetypes.public import BaseFolderSchema, Schema
from Products.Archetypes.public import StringField, LinesField
from Products.Archetypes.public import LinesWidget, TextAreaWidget, IdWidget, StringWidget
from Products.Archetypes.public import BaseFolder, registerType
from Products.CMFCore import CMFCorePermissions


import Permissions


schema = BaseFolderSchema +  Schema((
     StringField('id',
                 required=0, ## Still actually required, but
                             ## the widget will supply the missing value
                             ## on non-submits
                 mode="rw",
                 accessor="getId",
                 mutator="setId",
                 default=None,
                 widget=IdWidget(
     label="Short Name",
     label_msgid="label_short_name",
     description="Should not contain spaces, underscores or mixed case. "\
     "Short Name is part of the item's web address.",
     description_msgid="help_shortname",
     visible={'view' : 'visible'},
     i18n_domain="plone"),
                 ),
    StringField('title',
                required=1,
                searchable=1,
                default='',
                accessor='Title',
                widget=StringWidget(label_msgid="label_title",
                                    description_msgid="help_title",
                                    i18n_domain="plone"),
                ),    
    StringField('description',
                isMetadata=1,
                accessor='Description',
                widget=TextAreaWidget(label='Description', description='Give a description for this SimpleBlog.'),),
   LinesField('categories', widget=LinesWidget(label='Additional categories', description='Supply a list of possible additional categories that will be available to BlogEntries inside this folder or in sub folders.'),
               )
        ))


class BlogFolder(BaseFolder):
    """
    A folder object to store BlogEntries in
    """
    allowed_content_types=('BlogEntry', 'BlogFolder', 'Link', 'Image', 'File', 'Portlet')
    filter_content_types=1

    global_allow=0
    
    schema=schema
    
    content_icon='blogfolder_icon.gif'

    actions = ({
       'id': 'view',
        'name': 'View',
        'action': 'string:${object_url}/blogfolder_view',
        'permissions': (CMFCorePermissions.View,)
        },
        {'id': 'references',
          'name': 'References',
          'action': 'string:${object_url}/reference_edit',
          'permissions': (CMFCorePermissions.ModifyPortalContent,),
          'visible':0},
        {'id': 'metadata',
          'name': 'Properties',
          'action': 'string:${object_url}/base_metadata',
          'permissions': (CMFCorePermissions.ModifyPortalContent,),
          'visible':0})    
        
    def getInheritedCategories(self):
            # traverse upwards in the tree to collect all the available categories
            # stop collecting when a SimpleBlog object is reached
            
            cats=[]
            parent=self.aq_parent
            portal=self.portal_url.getPortalObject()
            
            while parent!=portal:
               if parent.portal_type=='SimpleBlog' or parent.portal_type=='BlogFolder':
                   # add cats
                   pcats=parent.categories
                   for c in pcats:
                       if c not in cats:
                           cats.append(c)
                   if parent.portal_type=='SimpleBlog':
                       break
               parent=parent.aq_parent
    
            return tuple(cats)                

    def synContentValues(self):
        # get brains for items that are published within the context of this blog.
        entries = self.simpleblog_tool.searchForEntries(self, fromHere=1, maxResults=0)
        
        # convert to objects
        objs = [e.getObject() for e in entries]
        return objs        
                
registerType(BlogFolder)
