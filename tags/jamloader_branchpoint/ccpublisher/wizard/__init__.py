import pages

import zope.interface

class IPublisherApp(zope.interface.Interface):
    def getPageParent():
        """Returns a handle to the WX object custom UI 
        elements should use as a parent."""
        
    def getPublisher():
        """Returns a reference to the apps Publisher object."""
        