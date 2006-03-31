import zope.interface

COMBO = 'combo'
TEXT = 'text'
ELEMENT_TYPES = [COMBO, TEXT]

class IMetaelement(zope.interface.Interface):
    key = zope.interface.Attribute('The key used to reference this element')
    value = zope.interface.Attribute('The current element value')
    tip = zope.interface.Attribute('A more verbose description which may be used for user interface generation.')
    default = zope.interface.Attribute('The default value.')
    label = zope.interface.Attribute('A short description used to label the user interface element.')
    
    elementType = zope.interface.Attribute('An optional type identifier used to direct interface generation.')
    choices = zope.interface.Attribute('An optional list of possible value choices')
    
    def getValue():
        """Return the current value."""
        
    def setValue(newValue):
        """Update the current value to [newValue]."""
    
class ISection(zope.interface.Interface):
    name = zope.interface.Attribute('The section name.')
    label = zope.interface.Attribute('')

    def getInterface():
        """Return the user interface panel for this section, if available."""
        
    def getFields():
        """Return the list of fields for this section."""
        
    def getField(key):
        """Return a particular field from this section; raise a KeyError
        if the key is not a valid field identifier or does not exist."""
        
    def isVisible():
        """Returns True if the Publisher framework should create or display
        a UI for this section."""
