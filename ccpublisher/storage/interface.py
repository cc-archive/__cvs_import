import zope.interface

class IItem(zope.interface.Interface):
    pass
    
class IRootItem(IItem):
    pass
    
class ISubItem(IItem):
    pass
                
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
        