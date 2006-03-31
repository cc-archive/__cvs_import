import config, os, fnmatch, codecs
from Products.Archetypes.public import *
from AccessControl import ClassSecurityInfo

from Products.CMFCore.utils import UniqueObject, getToolByName

schema =  BaseSchema + Schema(
    StringField('sidebar',
                required=False,
                accessor='Sidebar',
                mutator='setSidebar',
                widget=RichWidget(label='Sidebar'),
                ),
    StringField('bodyText',
                required=False,
                accessor='BodyText',
                mutator='setBodyText',
                widget=RichWidget(label='Body Text'),
                ),
    StringField('category',
                required=False,
                accessor='Category',
                vocabulary='_pageVocabulary',
                enforceVocabulary=True,
                widget=SelectionWidget(label='Category'),
                ),
    StringField('categoryDescription',
                required=False,
                accessor='CategoryDescription',
                mode='r',
                ),
    )


class TwoColDocument(BaseContent):
    schema = schema
    security = ClassSecurityInfo()

    actions=(
        {'id':'view',
         'name':'View',
         'action':'string:${object_url}/twocol_view',
         'permissions':'',
         },
        )
    
    def _pageVocabulary(self):
        cc_tool = getToolByName(self, 'cc_tool')
        
        return DisplayList([(n[0], n[0]) for n in cc_tool.localCategories()])

    def CategoryDescription(self):
        cc_tool = getToolByName(self, 'cc_tool')
        
        return [n[1] for n in cc_tool.localCategories()
                if n[0] == self.Category()][0]

registerType(TwoColDocument)
