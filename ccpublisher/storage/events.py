import zope.interface
import zope.component as component

class IItemSelected(zope.interface.Interface):
    item_id = zope.interface.Attribute("The unique identifier for this item.")

class IItemDeselected(zope.interface.Interface):
    item_id = zope.interface.Attribute("The unique identifier of the item deselected.")
    
class ItemSelected:
    zope.interface.implements(IItemSelected)
    def __init__(self, item_id):
        self.item_id = item_id
        
class ItemDeselected:
    zope.interface.implements(IItemDeselected)
    def __init__(self, item_id):
        self.item_id = item_id
        