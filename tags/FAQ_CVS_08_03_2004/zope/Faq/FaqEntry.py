from Products.Archetypes.public import *
from Products.CMFCore import CMFCorePermissions

schema = BaseSchema + Schema((
    StringField('question',
              required = 1,
              searchable = 1,
              widget=StringWidget(label_msgid = "label_question",
                                  description_msgid = "desc_question",
                                  i18n_domain = "faq")
              ),

    TextField('answer',
              required = 1,
              searchable = 1,
              allowable_content_types = ("text/html", "text/structured"),
              widget=TextAreaWidget(description_msgid = "desc_answer",
                                    label_msgid = "label_answer",
                                    i18n_domain = "faq",
                                    rows=6),
              ),
    ))


class FaqEntry(BaseContent):
    """A simple archetype"""
    schema = schema
    archetype_name = 'Faq Entry'
    meta_type = 'FaqEntry'

    actions = ({
        'id': 'view',
        'name': 'View',
        'action': 'faqentry_view',
        'permissions': (CMFCorePermissions.View,)
        },)


registerType(FaqEntry)
