import zope.interface
import zope.component as component

class IUpdateItemList(zope.interface.Interface):
    item_id = zope.interface.Attribute("The unique identifier for this item.")
    removed = zope.interface.Attribute("True if the item_id was removed; false if added.")
    
class UpdateItemList:
    zope.interface.implements(IUpdateItemList)
    def __init__(self, item_id, removed=False):
        self.item_id = item_id
        self.removed = removed
        
