import zope.interface

class IArchiveWorkItem(zope.interface.Interface):
    """An interface for an item within an archive.org work."""
    def fileNode():
        """Returns the block of XML representing this item in _files.xml"""

    def sanityCheck():
        """Perform simple sanity checks before proceeding with upload."""

    def archiveFilename():
        """Return the archive.org filename for this item."""
        
class IArchiveFile(IArchiveWorkItem):
    """A generic interface for a File in an archive.org submission."""


