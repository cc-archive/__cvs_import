import zope.interface
import interface

class MetaElement:
    zope.interface.implements(interface.IMetaelement)

    def __init__(self, elementType, key, default='', tip=None, label='', choices = []):
        self.elementType = elementType
        self.key = key
        self.default = self.value = default
        self.tip = tip
        self.label = label
        self.choices = choices
        
    def getValue(self):
        return self.value
        
    def setValue(self, newValue):
        self.value = newValue
        
class MetadataSection:
    """A basic section which takes a list of MetaElements and
    provides no specialized user interface."""
    
    zope.interface.implements(interface.ISection)
    
    visible = True
    
    def __init__(self, name, label, fieldlist):
        self.name = name
        self.label = label
        self.fields = fieldlist
                
    def getInterface(self):
        return None

    def getFields(self):
        return self.fields
        
    def getField(self, key):
        fields = [n for n in self.fields if n.key == key]
        
        if len(fields) > 0:
            return fields[0]
        else:
            raise KeyError
            
    def isVisible(self):
        return self.visible
            
class StorageFileMetadata:
    """A wrapper class for standardizing file-specific 
    metadata available to storage providers."""
    filename = ''
    fileFormat = None

        