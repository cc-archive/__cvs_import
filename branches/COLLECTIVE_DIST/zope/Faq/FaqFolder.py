from Products.Archetypes.public import *
from Products.CMFCore import CMFCorePermissions

schema = BaseFolderSchema + Schema((
    StringField('description',
                widget=TextAreaWidget(description_msgid="desc_folder",
                                      description="The description of the FAQ category.",
                                      label_msgid="label_folder",
                                      label="Description",
                                      i18n_domain = "faq",
                                      rows=6)),
    ))
 

class FaqFolder(BaseFolder):
    """A simple folderish archetype"""
    schema = schema
    archetype_name = 'Faq Folder'
    meta_type = 'FaqFolder'
    filter_content_types = 1
    allowed_content_types = ('FaqFolder', 'FaqEntry')

    actions = ({
        'id': 'view',
        'name': 'View',
        'action': 'faqfolder_view',
        'permissions': (CMFCorePermissions.View,)
        },)


registerType(FaqFolder)
