from Products.Archetypes.public import BaseSchema, BaseFolderSchema, Schema
from Products.Archetypes.public import StringField, TextField, LinesField, BooleanField
from Products.Archetypes.public import TextAreaWidget, VisualWidget,  MultiSelectionWidget, StringWidget, IdWidget
from Products.Archetypes.public import RichWidget, BooleanWidget
from Products.Archetypes.public import BaseContent, registerType, BaseFolder
from Products.CMFCore import CMFCorePermissions
from DateTime import DateTime
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
     visible={'view' : 'visible', 'edit':'visible'},
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
                searchable=1,
                isMetadata=1,
                accessor='Description',
                widget=TextAreaWidget(label='Description', description='Give a description for this SimpleBlog.'),),
    TextField('body',
              searchable=1,
              required=0,
              primary=1,
              default_content_type='text/html',
              default_output_type='text/html',
              allowable_content_types=('text/plain','text/structured', 'text/html',),
              widget=RichWidget(label='Body')),
    LinesField('categories',
                    accessor='EntryCategory', 
                    edit_accessor='EntryCategory', 
                    index='KeywordIndex', 
                    vocabulary='listCategories',
                    widget=MultiSelectionWidget(format='select', label='Categories', description='Select to which categories this Entry belongs to')),

    BooleanField('alwaysOnTop', 
             default=0,
             index='FieldIndex:schema',
             widget=BooleanWidget(label='Entry is always listed on top.', description='Controls if the Entry (when published) shown as the first Entry. If not checked, the effective date is used.')),
              ),)

class BlogEntry(BaseFolder):
    """
    A BlogEntry can exist inside a SimpleBlog Folder or an EntryFolder
    """

    schema = schema

    global_allow=0
    
    content_icon='entry_icon.gif'
    
    filter_content_types=1
    allowed_content_types=('Link', 'Image', 'File')
    
    actions = ({
       'id': 'view',
        'name': 'View',
        'action': 'string:${object_url}/blogentry_view',
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


    

    def getAlwaysOnTop(self):
        if hasattr(self, 'alwaysOnTop'):
            if self.alwaysOnTop==None or self.alwaysOnTop==0:
                return 0
            else:
                return 1
        else:
            return 0
            
    def getIcon(self, relative_to_portal=0):
        try:
            if self.getAlwaysOnTop()==0:
                return 'entry_icon.gif'
            else:
                return 'entry_pin.gif'
        except:
            return 'entry_icon.gif'
        
    def listCategories(self):
        # traverse upwards in the tree to collect all the available categories
        # stop collecting when a SimpleBlog object is reached
        
        cats=[]
        parent=self.aq_parent
        portal=self.portal_url.getPortalObject()
        
        while parent!=portal:
           if parent.portal_type=='Blog' or parent.portal_type=='BlogFolder':
               # add cats
               pcats=parent.categories
               for c in pcats:
                   if c not in cats:
                       cats.append(c)
               if parent.portal_type=='Blog':
                   break
           parent=parent.aq_parent
           
        # add the global categories
        for c in self.simpleblog_tool.getGlobalCategories():
            if not c in cats:
                cats.append(c)           
        cats.sort()
        return tuple(cats)

    def start(self):
        return self.getEffectiveDate()
        
    def end(self):
        """ 
        return the same data as start() since an entry is not an event but an item that is published on a specific
        date. We want the entries in the calendar to appear on only one day.
        """
        return self.getEffectiveDate()

registerType(BlogEntry)
