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
        
class IPublisherStorage(zope.interface.Interface):
    # A PublisherStorage instance is responsible for implementing 3
    # different tasks:
    #    0) Maintaining a list of files to be uploaded
    #    1) Defining a set of metadata fields available to the application
    #    2) Defining methods to store said metadata, along with files,
    #       to a backend server
    # it may also optionally:
    #    3) define a user interface for entering the metadata
    
    fileList = zope.interface.Attribute('a list of two tuples consisting of (filename, metadata)')
    
    def addFile(filename, metadata):
        """Add a file to the list for submission.  [filename] is the
        fully qualified pathname.  [metadata] is an instance of
        StorageFileMetadata."""
        pass
        
    def removeFile(filename):
        """Remove a file from the list."""
        
    def clearFiles():
        """Clear the file list."""
        
    def getSections():
        """Returns an optional list of metadata 'sections' which the
        UI framework will use to group fields.  If sections are not
        implemented, simply return an empty sequence."""
        return []

    def submit():
        """Upload the file(s) and metadata to the server."""
        pass
        