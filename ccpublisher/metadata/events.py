import zope.interface
import zope.component as component

class IFieldChangedEvent(zope.interface.Interface):
    field_id = zope.interface.Attribute("The field updated.")
    new_value = zope.interface.Attribute("The new field value.")
    
class FieldChanged:
    zope.interface.implements(IFieldChangedEvent)
    
    def __init__(self, field, value):
        self.field_id = field
        self.new_value = value
        
@component.adapter(IFieldChangedEvent)
def updateField(event):
    print 'no op handler for %s' % event.field_id
    
# zope.component.provideHandler(updateField)
