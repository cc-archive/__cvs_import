from Products.Archetypes.public import BaseFolderSchema, Schema
from Products.Archetypes.public import StringField, LinesField, IntegerField
from Products.Archetypes.public import SelectionWidget, LinesWidget, TextAreaWidget, MultiSelectionWidget, IntegerWidget, RichWidget, IdWidget, StringWidget
from Products.Archetypes.public import BaseFolder, registerType
from Products.CMFCore import CMFCorePermissions
from DateTime import DateTime
from config import DISPLAY_MODE
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
                searchable=1,                
                widget=TextAreaWidget(label='Description', description='Give a description for this SimpleBlog.'),),
    StringField('displayMode',
                vocabulary=DISPLAY_MODE,
                widget=SelectionWidget(label='Display Mode', description='Choose the display mode.'),
                default='descriptionOnly'),
    IntegerField('displayItems', widget=IntegerWidget(label='BlogEntries to display', description='Set the maximum number of BlogEntries to display.'), 
                 default=20),
    LinesField('categories', widget=LinesWidget(label='Possible Categories', description='Supply the list of possible categories that can be used in SimpleBlog Entries.'),
               )
        ))


class Blog(BaseFolder):
    """
Blog
    """
    allowed_content_types=('BlogEntry', 'BlogFolder', 'Link', 'Image', 'File', 'Portlet')
    filter_content_types=1    
    global_allow=1
    schema=schema
    
    content_icon='simpleblog_icon.gif'

    actions = ({'id': 'view',
                'name': 'View',
                'action': 'string:${object_url}/simpleblog_view',
                'permissions': (CMFCorePermissions.View,) },
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

    def manage_afterAdd(self, item, container):
        BaseFolder.manage_afterAdd(self, item, container)
        if self.simpleblog_tool.getCreatePortletOnBlogCreation():
            if not hasattr(item.aq_base, 'right_slots'):
                item._setProperty('right_slots', ['here/portlet_simpleblog/macros/portletBlogFull_local'], 'lines')
            if not hasattr(item.aq_base, 'column_two_portlets'):
                item._setProperty('column_two_portlets', ['here/portlet_simpleblog/macros/portletBlogFull_local'], 'lines')

    def synContentValues(self):
        # get brains for items that are published within the context of this blog.
        entries = self.simpleblog_tool.searchForEntries(self, maxResults=0)
        
        # convert to objects
        objs = [e.getObject() for e in entries]
        return objs
        
                
                
        
registerType(Blog)

